<?php
namespace Tests\Feature;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use App\Notifications\ResetPasswordNotification;
use App\Services\CashLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Hash;
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

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/inscription')->assertNotFound();
        $this->post('/inscription')->assertNotFound();
        $this->get('/connexion')->assertOk()->assertDontSee('S’inscrire')->assertSee('créé par l’administrateur');
    }

    public function test_admin_invites_user_who_chooses_name_and_password(): void
    {
        Notification::fake();
        $admin = $this->user(['name' => 'Ben', 'is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'email' => 'fatou@entreprise.test',
        ])->assertRedirect();

        $invited = User::where('email', 'fatou@entreprise.test')->firstOrFail();
        $this->assertTrue($invited->invitation_pending);
        $this->assertDatabaseHas('user_invitations', ['user_id' => $invited->id]);

        $invitationUrl = null;
        Notification::assertSentTo($invited, UserInvitationNotification::class, function ($notification) use (&$invitationUrl) {
            $invitationUrl = $notification->invitationUrl;
            return true;
        });

        $this->get($invitationUrl)
            ->assertOk()
            ->assertSee('fatou@entreprise.test')
            ->assertSee('data-password-target="password"', false)
            ->assertSee('data-password-target="password_confirmation"', false);

        $this->post(route('logout'));
        $this->assertGuest();

        $this->post($invitationUrl, [
            'name' => 'Fatou Diallo',
            'password' => 'Abcd1234',
            'password_confirmation' => 'Abcd1234',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($invited);
        $this->assertFalse($invited->fresh()->invitation_pending);
        $this->assertDatabaseMissing('user_invitations', ['user_id' => $invited->id]);
    }

    public function test_login_has_accessible_password_visibility_button(): void
    {
        $this->get('/connexion')
            ->assertOk()
            ->assertSee('Rester connecté')
            ->assertSee('Mot de passe oublié ?')
            ->assertSee('data-password-target="password"', false)
            ->assertSee('aria-label="Afficher le mot de passe"', false);
    }

    public function test_active_user_can_request_and_use_a_password_reset_link(): void
    {
        Notification::fake();
        $user = $this->user(['email' => 'utilisateur@entreprise.test', 'password' => 'AncienMotDePasse123']);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use (&$token) {
            $token = $notification->token;
            return true;
        });

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Nouveau mot de passe')
            ->assertSee('data-password-target="password"', false);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Nouv1234',
            'password_confirmation' => 'Nouv1234',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('Nouv1234', $user->fresh()->password));
    }

    public function test_invitation_email_template_can_be_rendered(): void
    {
        config(['mail.default' => 'array']);
        $user = $this->user(['email' => 'invitee@entreprise.test']);

        $user->notify(new UserInvitationNotification('https://caisseflow.test/invitation/exemple'));

        $this->assertTrue(true);
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

        $this->actingAs($viewer)->get(route('history.index'))
            ->assertOk()
            ->assertSee('Paiement du client partagé')
            ->assertSee('Créée par Aïcha Koné')
            ->assertSee('Historique commun des opérations');

        $this->actingAs($viewer)->get('/')
            ->assertOk()
            ->assertSee('MES INDICATEURS DE CAISSE')
            ->assertSee('Solde de mes opérations')
            ->assertDontSee('Paiement du client partagé')
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
            ->assertDontSee('Apport Moussa');

        $this->actingAs($moussa)->get('/')
            ->assertOk()
            ->assertSee('<div class="stat-value">70,00', false)
            ->assertDontSee('Apport Aïcha');

        $this->actingAs($moussa)->get(route('history.index'))
            ->assertOk()
            ->assertSee('Apport Aïcha')
            ->assertSee('Créée par Aïcha Koné')
            ->assertSee('Apport Moussa')
            ->assertSee('Créée par Moussa Traoré');

        $this->actingAs($admin)->get('/')
            ->assertOk()
            ->assertSee('VUE GLOBALE DE L’ENTREPRISE')
            ->assertSee('Solde global')
            ->assertSee('<div class="stat-value">320,00', false)
            ->assertSee('Votre solde personnel disponible : <strong>0,00 XOF</strong>', false)
            ->assertSee('Le solde global affiché sur le tableau de bord appartient à l’ensemble des utilisateurs.');
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

    public function test_admin_can_view_user_activity_while_regular_user_cannot(): void
    {
        $admin = $this->user(['name' => 'Ben', 'is_admin' => true]);
        $employee = $this->user(['name' => 'Awa Traoré', 'email' => 'awa@entreprise.test', 'password' => 'Securite123456']);

        $this->post('/connexion', ['email' => $employee->email, 'password' => 'Securite123456'])->assertRedirect();
        $this->get(route('history.index'))->assertOk();
        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertDatabaseHas('activity_logs', ['user_id' => $employee->id, 'action' => 'login']);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $employee->id, 'action' => 'view_history']);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $employee->id, 'action' => 'logout']);

        $this->actingAs($admin)->get(route('admin.activities.index'))
            ->assertOk()
            ->assertSee('Journal d’activité')
            ->assertSee('Awa Traoré')
            ->assertSee('S’est connecté à CaisseFlow.');

        $this->actingAs($employee)->get(route('admin.activities.index'))->assertForbidden();
    }
}
