<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLog extends Model
{
    public const ACTIONS = [
        'login' => 'Connexion',
        'logout' => 'Déconnexion',
        'activate_account' => 'Activation du compte',
        'reset_password' => 'Mot de passe réinitialisé',
        'view_dashboard' => 'Tableau de bord consulté',
        'view_entries' => 'Entrées consultées',
        'view_expenses' => 'Dépenses consultées',
        'view_history' => 'Historique consulté',
        'create_transaction' => 'Opération créée',
        'cancel_transaction' => 'Opération annulée',
        'download_receipt' => 'Reçu téléchargé',
        'export_report' => 'Rapport exporté',
        'view_users' => 'Utilisateurs consultés',
        'invite_user' => 'Utilisateur invité',
        'resend_invitation' => 'Invitation renvoyée',
        'toggle_user_status' => 'Statut utilisateur modifié',
        'view_activity' => 'Journal consulté',
    ];

    protected $guarded = [];
    protected function casts(): array { return ['metadata' => 'array']; }
    public function user() { return $this->belongsTo(User::class); }
    public function subject() { return $this->morphTo(); }

    public static function record(User $user, string $action, string $description, Request $request, ?Model $subject = null, array $metadata = []): self
    {
        return self::create([
            'user_id' => $user->id,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
            'metadata' => $metadata ?: null,
        ]);
    }
}
