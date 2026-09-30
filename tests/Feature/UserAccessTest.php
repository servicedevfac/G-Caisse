<?php
namespace Tests\Feature;
use App\Models\User;
use App\Services\CashLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attributes = []): User
    {
        return User::create([...[
            'name' => 'Utilisateur Test',
            'email' => Str::uuid().'@example.test',
            'password' => 'mot-de-passe-test',
            'is_active' => true,
            'is_admin' => false,
        ], ...$attributes]);
    }

    public function test_employee_can_register_and_reaches_shared_dashboard(): void
    {
        $response = $this->post('/inscription', [
            'name' => 'Fatou Diallo',
            'email' => 'fatou@entreprise.test',
            'password' => 'Securite123456',
            'password_confirmation' => 'Securite123456',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'fatou@entreprise.test', 'is_admin' => false, 'is_active' => true]);
    }

    public function test_user_sees_another_users_operation_and_creator_name(): void
    {
        $creator = $this->user(['name' => 'Aïcha Koné']);
        $viewer = $this->user(['name' => 'Moussa Traoré']);
        app(CashLedger::class)->record([
            'request_key' => (string) Str::uuid(), 'type' => 'approvisionnement', 'amount' => '25000',
            'description' => 'Paiement du client partagé', 'payment_method' => 'especes',
            'occurred_on' => today()->toDateString(),
        ], $creator);

        $this->actingAs($viewer)->get('/')
            ->assertOk()
            ->assertSee('Paiement du client partagé')
            ->assertSee('Créée par Aïcha Koné')
            ->assertSee('MES INDICATEURS DE CAISSE')
            ->assertSee('Solde de mes opérations')
            ->assertSee('<div class="stat-value">0,00', false);
    }

    public function test_each_employee_has_personal_metrics_while_admin_has_global_metrics(): void
    {
        $aicha = $this->user(['name' => 'Aïcha Koné']);
        $moussa = $this->user(['name' => 'Moussa Traoré']);
        $admin = $this->user(['name' => 'Ben', 'is_admin' => true]);
        $ledger = app(CashLedger::class);
        $ledger->record([
            'request_key' => (string) Str::uuid(), 'type' => 'approvisionnement', 'amount' => '250',
            'description' => 'Apport Aïcha', 'payment_method' => 'especes', 'occurred_on' => today()->toDateString(),
        ], $aicha);
        $ledger->record([
            'request_key' => (string) Str::uuid(), 'type' => 'approvisionnement', 'amount' => '70',
            'description' => 'Apport Moussa', 'payment_method' => 'mobile_money', 'occurred_on' => today()->toDateString(),
        ], $moussa);

        $this->actingAs($aicha)->get('/')
            ->assertOk()
            ->assertSee('<div class="stat-value">250,00', false)
            ->assertSee('Apport Moussa')
            ->assertSee('Créée par Moussa Traoré');

        $this->actingAs($moussa)->get('/')
            ->assertOk()
            ->assertSee('<div class="stat-value">70,00', false)
            ->assertSee('Apport Aïcha')
            ->assertSee('Créée par Aïcha Koné');

        $this->actingAs($admin)->get('/')
            ->assertOk()
            ->assertSee('VUE GLOBALE DE L’ENTREPRISE')
            ->assertSee('Solde global')
            ->assertSee('<div class="stat-value">320,00', false);
    }

    public function test_admin_can_view_and_block_user_without_delete_action(): void
    {
        $admin = $this->user(['name' => 'Ben', 'is_admin' => true]);
        $employee = $this->user(['name' => 'Employé à bloquer']);

        $this->actingAs($admin)->get('/administration/utilisateurs')
            ->assertOk()->assertSee('Employé à bloquer')->assertSee('Bloquer')->assertDontSee('Supprimer');

        $this->actingAs($admin)->patch(route('admin.users.toggle-status', $employee))->assertRedirect();
        $this->assertFalse($employee->fresh()->is_active);
    }

    public function test_regular_user_cannot_access_administration(): void
    {
        $this->actingAs($this->user())->get('/administration/utilisateurs')->assertForbidden();
    }

    public function test_registered_user_can_be_promoted_from_console(): void
    {
        $user = $this->user(['email' => 'responsable@entreprise.test']);
        $this->artisan('caisse:admin', ['email' => $user->email])->assertSuccessful();
        $this->assertTrue($user->fresh()->is_admin);
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_blocked_user_cannot_log_in_or_keep_using_application(): void
    {
        $blocked = $this->user(['email' => 'blocked@example.test', 'password' => 'Securite123456', 'is_active' => false]);
        $this->post('/connexion', ['email' => $blocked->email, 'password' => 'Securite123456'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->actingAs($blocked)->get('/')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
