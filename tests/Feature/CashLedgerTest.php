<?php
namespace Tests\Feature;
use App\Models\{CashAccount, Transaction, User};
use App\Services\CashLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class CashLedgerTest extends TestCase
{
    use RefreshDatabase;
    private function operator(): User { return User::create(['name'=>'Jenifer','email'=>Str::uuid().'@example.test','password'=>'test-password-only']); }
    private function data(string $type = 'approvisionnement', string $amount = '100.10'): array {
        return ['request_key'=>(string) Str::uuid(),'type'=>$type,'amount'=>$amount,'description'=>'Test caisse','payment_method'=>'especes','occurred_on'=>today()->toDateString()];
    }
    public function test_exact_amounts_and_duplicate_submission(): void {
        $user=$this->operator(); $ledger=app(CashLedger::class); $data=$this->data();
        $first=$ledger->record($data,$user); $again=$ledger->record($data,$user);
        $this->assertSame($first->id,$again->id); $this->assertDatabaseCount('transactions',1);
        $ledger->record($this->data('depense','0.10'),$user);
        $this->assertSame(10000,CashAccount::find(1)->balance_minor);
    }
    public function test_overdraft_is_rejected_without_writing(): void {
        try { app(CashLedger::class)->record($this->data('depense'),$this->operator()); $this->fail('Expected rejection'); }
        catch (ValidationException $e) { $this->assertDatabaseCount('transactions',0); $this->assertSame(0,CashAccount::find(1)->balance_minor); }
    }
    public function test_cancellation_is_audited_and_cannot_be_repeated(): void {
        $user=$this->operator(); $ledger=app(CashLedger::class); $t=$ledger->record($this->data(),$user);
        $ledger->cancel($t,'Erreur de saisie',$user);
        $this->assertSame(0,CashAccount::find(1)->balance_minor);
        $this->assertNotNull($t->fresh()->cancelled_at); $this->assertSame($user->id,$t->fresh()->cancelled_by);
        $this->expectException(ValidationException::class); $ledger->cancel($t,'Deuxième annulation',$user);
    }
    public function test_cannot_cancel_funds_already_spent(): void {
        $user=$this->operator(); $ledger=app(CashLedger::class); $t=$ledger->record($this->data(),$user);
        $ledger->record($this->data('depense','80'),$user);
        try { $ledger->cancel($t,'Erreur de saisie',$user); $this->fail('Expected rejection'); }
        catch (ValidationException $e) { $this->assertNull($t->fresh()->cancelled_at); $this->assertSame(2010,CashAccount::find(1)->balance_minor); }
    }
    public function test_guest_is_redirected_to_login(): void { $this->get('/')->assertRedirect('/connexion'); }
    public function test_authenticated_user_can_record(): void {
        $this->actingAs($this->operator())->post('/operations', $this->data())->assertRedirect(route('entries.index'));
        $this->assertDatabaseCount('transactions', 1);
    }
    public function test_invalid_amount_and_future_date_are_rejected(): void {
        $user=$this->operator();
        $this->actingAs($user)->post('/operations',[...$this->data('approvisionnement','-10'),'occurred_on'=>today()->addDay()->toDateString()])->assertSessionHasErrors(['amount','occurred_on']);
        $this->assertDatabaseCount('transactions',0);
    }
    public function test_dashboard_renders_for_authenticated_user(): void {
        $user=$this->operator();
        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('Tableau de bord')
            ->assertSee('Mouvements de caisse')
            ->assertSee('Gestion des entrées')
            ->assertSee('Gestion des sorties')
            ->assertSee('Historique des mouvements')
            ->assertSee('Déconnexion')
            ->assertSee('Les flux de mes opérations')
            ->assertSee('cashChart', false)
            ->assertSee('typeChart', false)
            ->assertSee('paymentChart', false)
            ->assertSee('chart.umd.min.js', false)
            ->assertDontSee('Historique commun des opérations')
            ->assertDontSee('data-bs-target="#operationModal"', false);
    }
    public function test_entry_flow_only_includes_funding(): void {
        $user = $this->operator();
        $ledger = app(CashLedger::class);
        $ledger->record([...$this->data('approvisionnement', '75'), 'description' => 'Approvisionnement visible'], $user);
        $ledger->record([...$this->data('depense', '10'), 'description' => 'Dépense masquée'], $user);

        $this->actingAs($user)->get(route('entries.index'))
            ->assertOk()
            ->assertSee('Gestion des entrées')
            ->assertSee('Nouvelle entrée')
            ->assertSee('Approvisionnement visible')
            ->assertDontSee('Dépense masquée')
            ->assertSee('75,00')
            ->assertDontSee('cashChart', false);
    }

    public function test_expense_page_only_includes_expenses(): void {
        $user = $this->operator();
        $ledger = app(CashLedger::class);
        $ledger->record([...$this->data('approvisionnement', '75'), 'description' => 'Approvisionnement masqué'], $user);
        $ledger->record([...$this->data('depense', '10'), 'description' => 'Dépense visible'], $user);

        $this->actingAs($user)->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('Gestion des sorties')
            ->assertSee('Nouvelle sortie')
            ->assertSee('Dépense visible')
            ->assertDontSee('Approvisionnement masqué')
            ->assertDontSee('cashChart', false);
    }

    public function test_removed_operation_types_cannot_be_recorded(): void {
        $user = $this->operator();

        foreach (['recette', 'retrait'] as $type) {
            $this->actingAs($user)->post('/operations', $this->data($type))->assertSessionHasErrors('type');
        }

        $this->assertDatabaseCount('transactions', 0);
    }
}
