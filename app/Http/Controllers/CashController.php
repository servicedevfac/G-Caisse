<?php
namespace App\Http\Controllers;
use App\Models\{CashAccount, Transaction};
use App\Services\{CashLedger, PrivateFileStorage};
use App\Exports\TransactionsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
class CashController
{
    private function filtered(Request $request) {
        $request->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])], 'type' => ['nullable', Rule::in(array_keys(Transaction::OPERATION_TYPES))], 'flow' => 'nullable|in:entree,sortie', 'q' => 'nullable|string|max:100', 'period' => 'nullable|in:day,week,month', 'chart_period' => 'nullable|in:day,7days,month,all', 'status' => 'nullable|in:active,cancelled']);
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
        $chartPeriod = $request->input('chart_period', '7days');
        $allActive = Transaction::query()->whereNull('cancelled_at');
        if (!$isGlobalDashboard) $allActive->where('user_id', $user->id);
        $paymentQuery = $this->forChartPeriod(clone $allActive, $chartPeriod);
        $paymentChart = collect(Transaction::METHODS)->map(fn ($label, $method) => [
            'label' => $label,
            'value' => (int) (clone $paymentQuery)->where('payment_method', $method)->sum('amount_minor') / 100,
        ])->values();
        $chart = $this->chartData($allActive, $chartPeriod);
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
            'paymentChart' => $paymentChart,
            'chartPeriod' => $chartPeriod,
            'availableBalance' => max(0, $personalAvailable),
            'todayCount' => (clone $todayQuery)->count(),
            'todayTransactions' => (clone $todayQuery)->with('user')->orderByDesc('created_at')->orderByDesc('id')->limit(10)->get(),
            'transactions' => $historyQuery->with('user', 'canceller')->orderByDesc('occurred_on')->orderByDesc('id')->paginate(12)->withQueryString(),
        ]);
    }
    private function forChartPeriod($query, string $period) {
        return match ($period) {
            'day' => $query->whereDate('occurred_on', today()),
            'month' => $query->whereBetween('occurred_on', [today()->startOfMonth()->toDateString(), today()->toDateString()]),
            'all' => $query,
            default => $query->whereBetween('occurred_on', [today()->subDays(6)->toDateString(), today()->toDateString()]),
        };
    }
    private function chartData($query, string $period) {
        if ($period === 'all') {
            $firstDate = (clone $query)->min('occurred_on');
            $cursor = $firstDate ? \Carbon\Carbon::parse($firstDate)->startOfMonth() : today()->startOfMonth();
            $periods = collect();
            while ($cursor->lte(today())) {
                $periods->push(['start' => $cursor->copy()->startOfMonth(), 'end' => $cursor->copy()->endOfMonth(), 'label' => $cursor->translatedFormat('M Y')]);
                $cursor->addMonth();
            }
        } else {
            $dates = match ($period) {
                'day' => collect([today()]),
                'month' => collect(range(0, today()->day - 1))->map(fn ($offset) => today()->startOfMonth()->addDays($offset)),
                default => collect(range(6, 0))->map(fn ($offset) => today()->subDays($offset)),
            };
            $periods = $dates->map(fn ($date) => ['start' => $date->copy()->startOfDay(), 'end' => $date->copy()->endOfDay(), 'label' => $period === 'day' ? 'Aujourd’hui' : $date->format('d/m')]);
        }

        return $periods->map(function ($range) use ($query) {
            $periodQuery = (clone $query)->whereBetween('occurred_on', [$range['start']->toDateString(), $range['end']->toDateString()]);
            return [
                'label' => $range['label'],
                'in' => (int) (clone $periodQuery)->whereIn('type', ['recette', 'approvisionnement'])->sum('amount_minor') / 100,
                'out' => (int) (clone $periodQuery)->whereIn('type', ['depense', 'retrait'])->sum('amount_minor') / 100,
            ];
        });
    }
    public function store(Request $request, CashLedger $ledger, PrivateFileStorage $storage) {
        $data = $request->validate([
            'request_key' => 'required|uuid', 'type' => ['required', Rule::in(array_keys(Transaction::OPERATION_TYPES))],
            'amount' => ['required', 'regex:/^\d{1,10}(\.\d{1,2})?$/', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'payment_method' => ['required', Rule::in(array_keys(Transaction::METHODS))],
            'company' => ['required', Rule::in(array_keys(config('caisse.companies')))],
            'beneficiary' => ['nullable', 'required_if:type,depense', 'string', 'max:255'],
            'description' => 'required|string|max:255', 'justification' => 'nullable|string|max:5000',
            'attachment' => ['nullable', 'file', 'max:'.config('caisse.document_max_kilobytes'), 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,txt,csv'],
            'occurred_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.config('caisse.operation_start_date'), 'before_or_equal:today'],
            'source' => 'nullable|in:dashboard',
        ], [
            'company.required' => 'Sélectionnez l’entreprise concernée.',
            'attachment.max' => 'Le document associé ne doit pas dépasser 10 Mo.',
            'attachment.mimes' => 'Formats acceptés : PDF, Word, Excel, image, texte et CSV.',
            'occurred_on.after_or_equal' => 'La date de l’opération doit être égale ou postérieure au 1er janvier 2024.',
            'occurred_on.before_or_equal' => 'La date de l’opération ne peut pas être postérieure à aujourd’hui.',
        ]);
        $source = $data['source'] ?? null;
        $attachment = $data['attachment'] ?? null;
        unset($data['source'], $data['attachment']);
        $stored = $attachment ? $storage->store($attachment, 'operations') : null;
        $storedWasUsed = false;

        try {
            $transaction = DB::transaction(function () use ($ledger, $data, $request, $stored, &$storedWasUsed) {
                $transaction = $ledger->record($data, $request->user());
                if ($stored && !$transaction->attachment_path) {
                    $transaction->update([
                        'attachment_original_name' => $stored['original_name'],
                        'attachment_path' => $stored['path'],
                        'attachment_mime_type' => $stored['mime_type'],
                        'attachment_original_size' => $stored['original_size'],
                        'attachment_stored_size' => $stored['stored_size'],
                        'attachment_is_compressed' => $stored['is_compressed'],
                    ]);
                    $storedWasUsed = true;
                }
                return $transaction;
            }, 3);
        } catch (\Throwable $exception) {
            if ($stored) $storage->delete($stored['path']);
            throw $exception;
        }
        if ($stored && !$storedWasUsed) $storage->delete($stored['path']);
        $destination = $source === 'dashboard'
            ? 'dashboard'
            : ($transaction->type === 'approvisionnement' ? 'entries.index' : 'expenses.index');
        return redirect()->route($destination)->with('success', 'Opération '.$transaction->reference.' enregistrée.');
    }
    public function cancel(Request $request, Transaction $transaction, CashLedger $ledger) {
        abort_unless($transaction->canBeCancelledBy($request->user()), 403);
        $data = $request->validate(['cancellation_reason' => 'required|string|min:5|max:255']);
        $ledger->cancel($transaction, $data['cancellation_reason'], $request->user());
        return back()->with('success', 'Opération annulée. Le solde a été recalculé.');
    }
    public function receipt(Transaction $transaction) {
        $transaction->loadMissing('user', 'canceller');
        return Pdf::loadView('receipt', compact('transaction'))->setPaper('a4')->download($transaction->reference.'.pdf');
    }
    public function attachment(Transaction $transaction, PrivateFileStorage $storage) {
        abort_unless($transaction->attachment_path, 404);
        $contents = $storage->contents($transaction->attachment_path, $transaction->attachment_is_compressed);
        $filename = str_replace(["\r", "\n"], '', $transaction->attachment_original_name);
        return response($contents)
            ->header('Content-Type', $transaction->attachment_mime_type ?: 'application/octet-stream')
            ->header('Content-Disposition', 'attachment; filename="'.addcslashes($filename, '"\\').'"');
    }
    public function export(Request $request, string $format) {
        abort_unless(in_array($format, ['pdf', 'xlsx']), 404);
        $request->merge(['status' => 'active']);
        $query = $this->filtered($request)->orderBy('occurred_on')->orderBy('id');
        if ((clone $query)->count() > 5000) return back()->withErrors(['export' => 'Limitez la période à 5 000 opérations maximum.']);
        $transactions = $query->get();
        return $format === 'xlsx' ? Excel::download(new TransactionsExport($transactions), 'rapport-caisse.xlsx') : Pdf::loadView('report', compact('transactions'))->setPaper('a4', 'landscape')->download('rapport-caisse.pdf');
    }
}
