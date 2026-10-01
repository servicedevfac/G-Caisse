<?php

namespace App\Http\Middleware;

use App\Models\{ActivityLog, Document, Transaction};
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $response = $next($request);

        if (!$user || $response->getStatusCode() >= 400) return $response;

        $routeName = $request->route()?->getName();
        [$action, $description] = match ($routeName) {
            'dashboard' => ['view_dashboard', 'A consulté le tableau de bord.'],
            'entries.index' => ['view_entries', 'A consulté la gestion des entrées.'],
            'expenses.index' => ['view_expenses', 'A consulté la gestion des sorties.'],
            'history.index' => ['view_history', 'A consulté l’historique des mouvements.'],
            'transactions.store' => ['create_transaction', 'A enregistré une '.($request->input('type') === 'depense' ? 'dépense' : 'entrée').' de '.$request->input('amount').' '.config('caisse.currency').'.'],
            'transactions.cancel' => ['cancel_transaction', 'A annulé l’opération '.$request->route('transaction')->reference.'.'],
            'transactions.receipt' => ['download_receipt', 'A téléchargé le reçu '.$request->route('transaction')->reference.'.'],
            'reports.export' => ['export_report', 'A exporté un rapport au format '.strtoupper((string) $request->route('format')).'.'],
            'documents.index' => ['view_documents', 'A consulté l’espace Documents.'],
            'documents.store' => ['upload_document', 'A ajouté le document '.$request->file('document')?->getClientOriginalName().'.'],
            'documents.download' => ['download_document', 'A téléchargé le document '.$request->route('document')->original_name.'.'],
            'documents.destroy' => ['delete_document', 'A supprimé le document '.$request->route('document')->original_name.'.'],
            'admin.users.index' => ['view_users', 'A consulté la liste des utilisateurs.'],
            'admin.users.store' => ['invite_user', 'A créé et invité le compte '.$request->input('email').'.'],
            'admin.users.invitation' => ['resend_invitation', 'A renvoyé l’invitation de '.$request->route('user')->email.'.'],
            'admin.users.toggle-status' => ['toggle_user_status', 'A '.($request->route('user')->is_active ? 'débloqué' : 'bloqué').' le compte '.$request->route('user')->email.'.'],
            'admin.activities.index' => ['view_activity', 'A consulté le journal d’activité.'],
            'logout' => ['logout', 'S’est déconnecté de CaisseFlow.'],
            default => [null, null],
        };

        if ($action) {
            $subject = $request->route('transaction') ?? $request->route('document') ?? $request->route('user');
            if ($routeName === 'transactions.store') $subject = Transaction::where('request_key', $request->input('request_key'))->first();
            if ($routeName === 'documents.store') $subject = Document::where('user_id', $user->id)->latest('id')->first();
            if (!($subject instanceof \Illuminate\Database\Eloquent\Model)) $subject = null;
            try {
                ActivityLog::record($user, $action, $description, $request, $subject, array_filter([
                    'route' => $routeName,
                    'company' => $request->input('company'),
                ]));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $response;
    }
}
