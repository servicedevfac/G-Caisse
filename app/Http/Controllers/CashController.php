<?php
namespace App\Http\Controllers;
use App\Models\{CashAccount, Transaction};
use App\Services\CashLedger;
use App\Exports\TransactionsExport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
class CashController
{
    private function filtered(Request $request) {
        $request->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])], 'type' => ['nullable', Rule::in(array_keys(Transaction::OPERATION_TYPES))], 'flow' => 'nullable|in:entree,sortie', 'q' => 'nullable|string|max:100', 'period' => 'nullable|in:day,week,month', 'status' => 'nullable|in:active,cancelled']);
        $query = Transaction::query();
        if ($request->filled('period')) {
            $start = match ($request->period) { 'day' => today(), 'week' => today()->startOfWeek(), default => today()->startOfMonth() };
            $query->whereBetween('occurred_on', [$start->toDateString(), today()->toDateString()]);
        }
        if ($request->filled('from')) $query->whereDate('occurred_on', '>=', $request->from);
        if ($request->filled('to')) $query->whereDate('occurred_on', '<=', $request->to);
        if ($request->flow === 'entree') $query->whereIn('type', ['recette', 'approvisionnement']);
        if ($request->flow === 'sortie') $query->whereIn('type', ['depense', 'retrait']);
        if ($request->filled('type')) $query->where('type', $request->type);
        if ($request->filled('q')) $query->where('description', 'like', '%'.$request->q.'%');
        if ($request->status === 'active') $query->whereNull('cancelled_at');
        if ($request->status === 'cancelled') $query->whereNotNull('cancelled_at');
        return $query;
    }
    public function index(Request $request) {
        if ($request->filled('flow')) {
            return redirect()->route($request->flow === 'entree' ? 'entries.index' : 'expenses.index', $request->except('flow'));
        }

        return $this->renderPage($request, 'dashboard');
    }
    public function entries(Request $request) {
        $request->merge(['flow' => 'entree']);
        return $this->renderPage($request, 'entries');
    }
    public function expenses(Request $request) {
        $request->merge(['flow' => 'sortie']);
        return $this->renderPage($request, 'expenses');
    }
    public function history(Request $request) {
        $request->request->remove('flow');
        return $this->renderPage($request, 'history');
    }
    private function renderPage(Request $request, string $page) {
        $user = $request->user();
        $isGlobalDashboard = $user->is_admin;
        $historyQuery = $this->filtered($request);
        $metricsQuery = clone $historyQuery;
        if (!$isGlobalDashboard) $metricsQuery->where('user_id', $user->id);
        $active = (clone $metricsQuery)->whereNull('cancelled_at');
        $totals = [];
        foreach (array_keys(Transaction::TYPES) as $type) $totals[$type] = (int) (clone $active)->where('type', $type)->sum('amount_minor');
        $allActive = Transaction::query()->whereNull('cancelled_at');
        if (!$isGlobalDashboard) $allActive->where('user_id', $user->id);
        $typeChart = collect(Transaction::OPERATION_TYPES)->map(fn ($label, $type) => [
            'label' => $label,
            'value' => (int) (clone $allActive)->where('type', $type)->sum('amount_minor') / 100,
        ])->values();
        $paymentChart = collect(Transaction::METHODS)->map(fn ($label, $method) => [
            'label' => $label,
            'value' => (int) (clone $allActive)->where('payment_method', $method)->sum('amount_minor') / 100,
        ])->values();
        $chart = collect(range(6, 0))->map(function ($offset) use ($isGlobalDashboard, $user) {
            $date = today()->subDays($offset);
            $query = Transaction::whereNull('cancelled_at')->whereDate('occurred_on', $date);
            if (!$isGlobalDashboard) $query->where('user_id', $user->id);
            return ['label' => $date->format('d/m'), 'in' => (int) (clone $query)->whereIn('type', ['recette', 'approvisionnement'])->sum('amount_minor') / 100, 'out' => (int) (clone $query)->whereIn('type', ['depense', 'retrait'])->sum('amount_minor') / 100];
        });
        $balance = CashAccount::findOrFail(1)->balance_minor;
        $personalAvailable = (int) Transaction::where('user_id', $user->id)->whereNull('cancelled_at')->where('type', 'approvisionnement')->sum('amount_minor')
            - (int) Transaction::where('user_id', $user->id)->whereNull('cancelled_at')->where('type', 'depense')->sum('amount_minor');
        if (!$isGlobalDashboard) {
            $balanceQuery = Transaction::where('user_id', $user->id)->whereNull('cancelled_at');
            $balance = (int) (clone $balanceQuery)->whereIn('type', ['recette', 'approvisionnement'])->sum('amount_minor')
                - (int) (clone $balanceQuery)->whereIn('type', ['depense', 'retrait'])->sum('amount_minor');
        }
        $todayQuery = Transaction::whereNull('cancelled_at')->whereDate('occurred_on', today());
        if (!$isGlobalDashboard) $todayQuery->where('user_id', $user->id);
        return view('dashboard', [
            'page' => $page, 'balance' => $balance, 'isGlobalDashboard' => $isGlobalDashboard,
            'totals' => $totals, 'chart' => $chart,
            'typeChart' => $typeChart, 'paymentChart' => $paymentChart,
            'availableBalance' => max(0, $personalAvailable),
            'todayCount' => $todayQuery->count(),
            'transactions' => $historyQuery->with('user', 'canceller')->orderByDesc('occurred_on')->orderByDesc('id')->paginate(12)->withQueryString(),
        ]);
    }
    public function store(Request $request, CashLedger $ledger) {
        $data = $request->validate([
            'request_key' => 'required|uuid', 'type' => ['required', Rule::in(array_keys(Transaction::OPERATION_TYPES))],
            'amount' => ['required', 'regex:/^\d{1,10}(\.\d{1,2})?$/', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'payment_method' => ['required', Rule::in(array_keys(Transaction::METHODS))],
            'company' => ['nullable', 'required_if:type,depense', Rule::in(array_keys(config('caisse.companies')))],
            'beneficiary' => ['nullable', 'required_if:type,depense', 'string', 'max:255'],
            'description' => 'required|string|max:255', 'justification' => 'nullable|string|max:5000',
            'occurred_on' => 'required|date_format:Y-m-d|before_or_equal:today',
            'source' => 'nullable|in:dashboard',
        ]);
        $source = $data['source'] ?? null;
        unset($data['source']);
        $transaction = $ledger->record($data, $request->user());
        $destination = $source === 'dashboard'
            ? 'dashboard'
            : ($transaction->type === 'approvisionnement' ? 'entries.index' : 'expenses.index');
        return redirect()->route($destination)->with('success', 'Opération '.$transaction->reference.' enregistrée.');
    }
    public function cancel(Request $request, Transaction $transaction, CashLedger $ledger) {
        $data = $request->validate(['cancellation_reason' => 'required|string|min:5|max:255']);
        $ledger->cancel($transaction, $data['cancellation_reason'], $request->user());
        return back()->with('success', 'Opération annulée. Le solde a été recalculé.');
    }
    public function receipt(Transaction $transaction) {
        $transaction->loadMissing('user', 'canceller');
        return Pdf::loadView('receipt', compact('transaction'))->setPaper('a4')->download($transaction->reference.'.pdf');
    }
    public function export(Request $request, string $format) {
        abort_unless(in_array($format, ['pdf', 'xlsx']), 404);
        $query = $this->filtered($request)->orderBy('occurred_on')->orderBy('id');
        if ((clone $query)->count() > 5000) return back()->withErrors(['export' => 'Limitez la période à 5 000 opérations maximum.']);
        $transactions = $query->get();
        return $format === 'xlsx' ? Excel::download(new TransactionsExport($transactions), 'rapport-caisse.xlsx') : Pdf::loadView('report', compact('transactions'))->setPaper('a4', 'landscape')->download('rapport-caisse.pdf');
    }
}
