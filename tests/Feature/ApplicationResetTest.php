<?php

namespace Tests\Feature;

use App\Models\{ActivityLog, CashAccount, Document, Transaction, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApplicationResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_keeps_only_admin_and_clears_all_application_data(): void
    {
        config(['caisse.documents_disk' => null, 'filesystems.default' => 'documents_local']);
        Storage::fake('documents_local');

        $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'password', 'is_admin' => true]);
        $user = User::create(['name' => 'Utilisateur', 'email' => 'user@example.test', 'password' => 'password']);
        $transaction = Transaction::create([
            'request_key' => (string) Str::uuid(), 'user_id' => $user->id, 'type' => 'approvisionnement',
            'company' => 'fid', 'amount_minor' => 150000, 'payment_method' => 'especes',
            'description' => 'Ancienne opération', 'occurred_on' => today(),
            'attachment_path' => 'operations/test.bin', 'attachment_original_name' => 'preuve.pdf',
            'attachment_mime_type' => 'application/pdf', 'attachment_original_size' => 10,
            'attachment_stored_size' => 10, 'attachment_is_compressed' => false,
        ]);
        Document::create([
            'user_id' => $user->id, 'description' => 'Ancien document', 'original_name' => 'archive.pdf',
            'storage_path' => 'documents/test.bin', 'mime_type' => 'application/pdf',
            'original_size' => 10, 'stored_size' => 10, 'is_compressed' => false,
        ]);
        Storage::disk('documents_local')->put('operations/test.bin', 'operation');
        Storage::disk('documents_local')->put('documents/test.bin', 'document');
        ActivityLog::record($user, 'create_transaction', 'Test', Request::create('/'), $transaction);
        CashAccount::findOrFail(1)->update(['balance_minor' => 150000]);

        $this->artisan('caisse:reset', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'email' => 'admin@example.test', 'is_admin' => true, 'is_active' => true]);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertSame(0, CashAccount::findOrFail(1)->balance_minor);
        Storage::disk('documents_local')->assertMissing('operations/test.bin');
        Storage::disk('documents_local')->assertMissing('documents/test.bin');
    }
}
