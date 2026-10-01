<?php
namespace App\Services;
use App\Models\{CashAccount, Transaction, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class CashLedger
{
    public function record(array $data, User $user): Transaction
    {
        if (!array_key_exists($data['type'] ?? '', Transaction::OPERATION_TYPES)) {
            throw ValidationException::withMessages(['type' => 'Le type d’opération sélectionné n’est pas disponible.']);
        }
        if (($data['type'] ?? null) === 'depense' && (!array_key_exists($data['company'] ?? '', config('caisse.companies')) || blank($data['beneficiary'] ?? null))) {
            throw ValidationException::withMessages(['company' => 'L’entreprise et le bénéficiaire sont obligatoires pour une dépense.']);
        }

        return DB::transaction(function () use ($data, $user) {
            $account = CashAccount::lockForUpdate()->findOrFail(1);
            $existing = Transaction::where('request_key', $data['request_key'])->first();
            if ($existing) return $existing;
            // Integer arithmetic: no floating-point rounding in the ledger.
            [$whole, $fraction] = array_pad(explode('.', (string) $data['amount'], 2), 2, '');
            $minor = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
            $delta = $data['type'] === 'approvisionnement' ? $minor : -$minor;
            if ($account->balance_minor + $delta < 0) throw ValidationException::withMessages(['amount' => 'Le solde disponible est insuffisant.']);
            unset($data['amount']);
            $transaction = Transaction::create([...$data, 'amount_minor' => $minor, 'user_id' => $user->id]);
            $account->update(['balance_minor' => $account->balance_minor + $delta]);
            return $transaction;
        }, 3);
    }
    public function cancel(Transaction $transaction, string $reason, User $user): void
    {
        DB::transaction(function () use ($transaction, $reason, $user) {
            $account = CashAccount::lockForUpdate()->findOrFail(1);
            $entry = Transaction::lockForUpdate()->findOrFail($transaction->id);
            if ($entry->cancelled_at) throw ValidationException::withMessages(['cancel' => 'Cette opération est déjà annulée.']);
            if ($entry->created_at->lt(now()->subDays(7))) throw ValidationException::withMessages(['cancel' => 'Le délai de 7 jours pour annuler cette opération est dépassé.']);
            $delta = $entry->isInflow() ? -$entry->amount_minor : $entry->amount_minor;
            if ($account->balance_minor + $delta < 0) throw ValidationException::withMessages(['cancel' => 'Annulation impossible : les fonds ont déjà été utilisés.']);
            $entry->update(['cancelled_at' => now(), 'cancelled_by' => $user->id, 'cancellation_reason' => $reason]);
            $account->update(['balance_minor' => $account->balance_minor + $delta]);
        }, 3);
    }
}
