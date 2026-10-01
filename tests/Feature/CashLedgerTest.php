<?php
namespace Tests\Feature;
use App\Exports\TransactionsExport;
use App\Models\{CashAccount, Transaction, User};
use App\Services\CashLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;
class CashLedgerTest extends TestCase
{
    use RefreshDatabase;
    private function operator(): User { return User::create(['name'=>'Jenifer','email'=>Str::uuid().'@example.test','password'=>'test-password-only']); }
    private function data(string $type = 'approvisionnement', string $amount = '100.10'): array {
        return ['request_key'=>(string) Str::uuid(),'type'=>$type,'amount'=>$amount,'description'=>'Test caisse','payment_method'=>'especes','occurred_on'=>today()->toDateString(),'company'=>'fid',
            ...($type === 'depense' ? ['company'=>'fid', 'beneficiary'=>'Fournisseur Test'] : [])];
    }
    public function test_exact_amounts_and_duplicate_submission(): void {
        $user=$this->operator(); $ledger=app(CashLedger::class); $data=$this->data();
        $first=$ledger->record($data,$user); $again=$ledger->record($data,$user);
        $this->assertSame($first->id,$again->id); $this->assertDatabaseCount('transactions',1);
        $this->assertSame('CF-000001', $first->reference);
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
    public function test_operation_cannot_be_cancelled_after_seven_days(): void {
        $user=$this->operator(); $ledger=app(CashLedger::class); $t=$ledger->record($this->data(),$user);
        $t->update(['created_at' => now()->subDays(8)]);
        try { $ledger->cancel($t,'Annulation trop tardive',$user); $this->fail('Expected rejection'); }
        catch (ValidationException $e) {
            $this->assertSame('Le délai de 7 jours pour annuler cette opération est dépassé.', $e->errors()['cancel'][0]);
            $this->assertNull($t->fresh()->cancelled_at);
            $this->assertSame(10010,CashAccount::find(1)->balance_minor);
        }

        $this->actingAs($user)->get(route('history.index'))
            ->assertOk()
            ->assertSee('Délai d’annulation expiré')
            ->assertDontSee('data-reference="'.$t->reference.'"', false);
    }
    public function test_guest_is_redirected_to_login(): void { $this->get('/')->assertRedirect('/connexion'); }
    public function test_authenticated_user_can_record(): void {
        $this->actingAs($this->operator())->post('/operations', $this->data())->assertRedirect(route('entries.index'));
        $this->assertDatabaseCount('transactions', 1);
    }
    public function test_invalid_amount_and_future_date_are_rejected(): void {
        $user=$this->operator();
        $this->actingAs($user)->post('/operations',[...$this->data('approvisionnement','-10'),'occurred_on'=>today()->addDay()->toDateString()])->assertSessionHasErrors(['amount','occurred_on']);
        $this->actingAs($user)->post('/operations',[...$this->data('approvisionnement','10'),'occurred_on'=>'2023-12-31'])->assertSessionHasErrors('occurred_on');
        $this->assertDatabaseCount('transactions',0);
    }
    public function test_dashboard_renders_for_authenticated_user(): void {
        $user=$this->operator();
        app(CashLedger::class)->record([...$this->data('approvisionnement', '75'), 'description' => 'Opération du jour visible'], $user);
        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('Tableau de bord')
            ->assertSee('Mouvements de caisse')
            ->assertDontSee('Mon entreprise')
            ->assertDontSee('Espace de gestion')
            ->assertSee('Gestion des entrées')
            ->assertSee('Gestion des sorties')
            ->assertSee('Historique des mouvements')
            ->assertSee('Déconnexion')
            ->assertSee('Les flux de mes opérations')
            ->assertSee('Aujourd’hui')
            ->assertSee('7 derniers jours')
            ->assertSee('Ce mois')
            ->assertSee('Toutes les périodes')
            ->assertDontSee('Votre caisse en équilibre')
            ->assertSee('cashChart', false)
            ->assertDontSee('typeChart', false)
            ->assertDontSee('Répartition des mouvements')
            ->assertSee('paymentChart', false)
            ->assertSee('Mes opérations effectuées aujourd’hui')
            ->assertSee('Opération du jour visible')
            ->assertSee('Voir tout l’historique')
            ->assertSee('chart.umd.min.js', false)
            ->assertDontSee('Historique commun des opérations')
            ->assertSee('Nouvelle opération')
            ->assertSee('data-bs-target="#operationModal"', false)
            ->assertSee('min="2024-01-01"', false)
            ->assertSee('Approvisionnement')
            ->assertSee('Dépense');
    }
    public function test_dashboard_chart_period_can_be_changed(): void {
        $user = $this->operator();
        app(CashLedger::class)->record([...$this->data('approvisionnement', '75'), 'description' => 'Flux filtré'], $user);

        $this->actingAs($user)->get(route('dashboard', ['chart_period' => 'month']))
            ->assertOk()
            ->assertSee('<option value="month" selected>Ce mois</option>', false)
            ->assertSee('Flux filtré');

        $this->actingAs($user)->get(route('dashboard', ['chart_period' => 'invalid']))
            ->assertSessionHasErrors('chart_period');
    }
    public function test_entry_flow_only_includes_funding(): void {
        $user = $this->operator();
        $ledger = app(CashLedger::class);
        $ledger->record([...$this->data('approvisionnement', '75'), 'description' => 'Approvisionnement visible'], $user);
        $ledger->record([...$this->data('depense', '10'), 'description' => 'Dépense masquée'], $user);

        $this->actingAs($user)->get(route('entries.index'))
            ->assertOk()
            ->assertSee('Gestion des entrées')
            ->assertSee('Nouvel approvisionnement')
            ->assertSee('Approvisionnement visible')
            ->assertDontSee('Dépense masquée')
            ->assertSee('75,00')
            ->assertDontSee('aria-label="Reçu ', false)
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
            ->assertSee('Nouvelle dépense')
            ->assertSee('VOYAGEDIFIANT')
            ->assertSee('Dépense visible')
            ->assertSee('aria-label="Reçu ', false)
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

    public function test_user_cannot_spend_another_users_funding(): void
    {
        $fundedUser = $this->operator();
        $otherUser = $this->operator();
        app(CashLedger::class)->record($this->data('approvisionnement', '500'), $fundedUser);

        $this->actingAs($otherUser)->post('/operations', $this->data('depense', '1'))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame(50000, CashAccount::findOrFail(1)->balance_minor);
    }

    public function test_only_creator_or_admin_can_cancel_an_operation(): void
    {
        $creator = $this->operator();
        $otherUser = $this->operator();
        $admin = User::create(['name' => 'Administrateur', 'email' => Str::uuid().'@example.test', 'password' => 'test-password-only', 'is_admin' => true]);
        $transaction = app(CashLedger::class)->record($this->data('approvisionnement', '100'), $creator);
        $cancelUrl = route('transactions.cancel', $transaction);

        $this->actingAs($otherUser)->get(route('history.index'))
            ->assertOk()
            ->assertDontSee('data-action="'.$cancelUrl.'"', false);
        $this->actingAs($otherUser)->post($cancelUrl, ['cancellation_reason' => 'Tentative interdite'])
            ->assertForbidden();
        $this->assertNull($transaction->fresh()->cancelled_at);

        $this->actingAs($admin)->get(route('history.index'))
            ->assertOk()
            ->assertSee('data-action="'.$cancelUrl.'"', false);
        $this->actingAs($admin)->post($cancelUrl, ['cancellation_reason' => 'Annulation administrateur'])
            ->assertRedirect();
        $this->assertSame($admin->id, $transaction->fresh()->cancelled_by);
    }

    public function test_expense_requires_company_and_beneficiary_and_generates_branded_receipt(): void
    {
        $user = $this->operator();
        app(CashLedger::class)->record($this->data('approvisionnement', '500'), $user);

        $invalid = $this->data('depense', '25');
        unset($invalid['company'], $invalid['beneficiary']);
        $this->actingAs($user)->post('/operations', $invalid)->assertSessionHasErrors(['company', 'beneficiary']);

        $this->actingAs($user)->post('/operations', [
            ...$this->data('depense', '25'),
            'company' => 'fac_immobilier',
            'beneficiary' => 'Imprimerie Centrale',
        ])->assertRedirect(route('expenses.index'));

        $expense = Transaction::where('type', 'depense')->firstOrFail();
        $this->assertSame('FAC IMMOBILIER', $expense->companyName());
        $receipt = view('receipt', ['transaction' => $expense->load('user')])->render();
        $this->assertSame(2, substr_count($receipt, 'BON DE CAISSE'));
        $this->assertSame(2, substr_count($receipt, 'N° ....../......./'.today()->format('Y')));
        $this->assertSame(2, substr_count($receipt, 'class="object-table"'));
        $this->assertStringNotContainsString('Mode de paiement', $receipt);
        $this->assertStringNotContainsString('Enregistré par', $receipt);
        $this->assertStringNotContainsString('FAC IMMOBILIER', $receipt);
        $this->assertStringContainsString('Imprimerie Centrale', $receipt);
        $this->assertSame(4, substr_count($receipt, 'data:image/jpeg;base64,'));
        $pdf = $this->actingAs($user)->get(route('transactions.receipt', $expense));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_funding_requires_company_and_uses_its_logo_on_receipt(): void
    {
        $user = $this->operator();
        $invalid = $this->data('approvisionnement', '150');
        unset($invalid['company']);

        $this->actingAs($user)->post('/operations', $invalid)->assertSessionHasErrors('company');
        $this->actingAs($user)->post('/operations', [
            ...$this->data('approvisionnement', '150'),
            'company' => 'voyage_edifiant',
        ])->assertRedirect(route('entries.index'));

        $funding = Transaction::where('type', 'approvisionnement')->firstOrFail();
        $this->assertSame('VOYAGEDIFIANT', $funding->companyName());
        $receipt = view('receipt', ['transaction' => $funding->load('user')])->render();
        $this->assertSame(4, substr_count($receipt, 'data:image/jpeg;base64,'));
    }

    public function test_reports_exclude_cancelled_operations_and_status_columns(): void
    {
        $user = $this->operator();
        $ledger = app(CashLedger::class);
        $active = $ledger->record([...$this->data('approvisionnement', '100'), 'description' => 'Opération active'], $user);
        $cancelled = $ledger->record([...$this->data('approvisionnement', '50'), 'description' => 'Opération annulée'], $user);
        $ledger->cancel($cancelled, 'Erreur de rapport', $user);

        Excel::fake();
        $this->actingAs($user)->get(route('reports.export', ['format' => 'xlsx', 'status' => 'cancelled']))->assertOk();
        Excel::assertDownloaded('rapport-caisse.xlsx', function (TransactionsExport $export) use ($active, $cancelled) {
            return $export->collection()->pluck('id')->all() === [$active->id]
                && !in_array('Statut', $export->headings(), true)
                && count($export->map($active)) === 7
                && !$export->collection()->contains('id', $cancelled->id);
        });

        $html = view('report', ['transactions' => collect([$active])])->render();
        $this->assertStringNotContainsString('<th>Statut</th>', $html);
        $this->assertStringNotContainsString('Validée', $html);
        $this->assertStringNotContainsString('Annulée', $html);
    }
}
