<?php

namespace Tests\Feature;

use App\Models\{Document, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['caisse.documents_disk' => 'documents_local']);
        Storage::fake('documents_local');
    }

    private function user(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Utilisateur Documents',
            'email' => Str::uuid().'@example.test',
            'password' => 'mot-de-passe-test',
        ], $attributes));
    }

    public function test_document_is_compressed_outside_database_and_can_be_downloaded(): void
    {
        $user = $this->user();
        $contents = str_repeat("Procédure de caisse partagée.\n", 1000);

        $this->actingAs($user)->post(route('documents.store'), [
            'document' => UploadedFile::fake()->createWithContent('procedure-caisse.txt', $contents),
        ])->assertRedirect();

        $document = Document::firstOrFail();
        $this->assertSame('procedure-caisse.txt', $document->original_name);
        $this->assertTrue($document->is_compressed);
        $this->assertLessThan($document->original_size, $document->stored_size);
        $this->assertDatabaseMissing('documents', ['storage_path' => $contents]);
        Storage::disk('documents_local')->assertExists($document->storage_path);

        $this->actingAs($user)->get(route('documents.download', $document))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertContent($contents);
    }

    public function test_documents_are_shared_but_only_owner_or_admin_can_delete_them(): void
    {
        $owner = $this->user(['name' => 'Awa']);
        $otherUser = $this->user(['name' => 'Moussa']);
        $admin = $this->user(['name' => 'Ben', 'is_admin' => true]);

        $this->actingAs($owner)->post(route('documents.store'), [
            'document' => UploadedFile::fake()->createWithContent('budget.csv', "mois,montant\njanvier,100000"),
        ])->assertRedirect();
        $document = Document::firstOrFail();

        $this->actingAs($otherUser)->get(route('documents.index'))
            ->assertOk()
            ->assertSee('budget.csv')
            ->assertSee('Awa')
            ->assertDontSee('Supprimer');
        $this->actingAs($otherUser)->delete(route('documents.destroy', $document))->assertForbidden();
        Storage::disk('documents_local')->assertExists($document->storage_path);

        $this->actingAs($admin)->delete(route('documents.destroy', $document))->assertRedirect();
        Storage::disk('documents_local')->assertMissing($document->storage_path);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_document_size_and_type_are_restricted(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post(route('documents.store'), [
            'document' => UploadedFile::fake()->create('archive.exe', 20, 'application/octet-stream'),
        ])->assertSessionHasErrors('document');

        $this->actingAs($user)->post(route('documents.store'), [
            'document' => UploadedFile::fake()->create('trop-grand.pdf', 11000, 'application/pdf'),
        ])->assertSessionHasErrors('document');

        $this->assertDatabaseCount('documents', 0);
    }
}
