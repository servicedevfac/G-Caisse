<?php
namespace App\Exports;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, WithMapping, WithCustomValueBinder};
use PhpOffice\PhpSpreadsheet\Cell\{StringValueBinder, Cell, DataType};
class TransactionsExport extends StringValueBinder implements FromCollection, WithHeadings, WithMapping, WithCustomValueBinder
{
    public function __construct(private Collection $transactions) {}
    public function collection() { return $this->transactions; }
    public function headings(): array { return ['Référence', 'Date', 'Type', 'Motif', 'Paiement', 'Montant', 'Devise']; }
    public function map($t): array { return [$t->reference, $t->occurred_on->format('Y-m-d'), $t->type, $t->description, $t->payment_method, $t->amount_minor / 100, config('caisse.currency')]; }
    public function bindValue(Cell $cell, $value): bool {
        if ($cell->getColumn() === 'F' && is_numeric($value)) { $cell->setValueExplicit($value, DataType::TYPE_NUMERIC); return true; }
        return parent::bindValue($cell, $value); // User text must never become an Excel formula.
    }
}
