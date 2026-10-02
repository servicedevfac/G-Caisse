<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Transaction extends Model
{
    public const TYPES = ['recette' => 'Recette', 'depense' => 'Dépense', 'approvisionnement' => 'Approvisionnement', 'retrait' => 'Retrait'];
    public const OPERATION_TYPES = ['approvisionnement' => 'Approvisionnement', 'depense' => 'Dépense'];
    public const METHODS = ['especes' => 'Espèces', 'mobile_money' => 'Mobile Money', 'virement' => 'Virement'];
    protected $guarded = [];
    protected function casts(): array { return ['amount_minor' => 'integer', 'occurred_on' => 'date', 'cancelled_at' => 'datetime', 'attachment_original_size' => 'integer', 'attachment_stored_size' => 'integer', 'attachment_is_compressed' => 'boolean']; }
    public function user() { return $this->belongsTo(User::class); }
    public function canceller() { return $this->belongsTo(User::class, 'cancelled_by'); }
    public function isInflow(): bool { return in_array($this->type, ['recette', 'approvisionnement'], true); }
    public function canBeCancelled(): bool { return !$this->cancelled_at && $this->created_at->gte(now()->subDays(7)); }
    public function canBeCancelledBy(User $user): bool { return $user->is_admin || $this->user_id === $user->id; }
    public function companyName(): ?string { return $this->company ? config('caisse.companies.'.$this->company.'.name') : null; }
    public function companyLogoPath(): ?string { return $this->company ? config('caisse.companies.'.$this->company.'.logo') : null; }
    public function getReferenceAttribute(): string { return 'CF-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT); }
}
