@extends('layout')
@section('title', 'Journal d’activité')
@section('content')
@include('partials.sidebar')
<div class="main-shell">
    <header class="topbar"><span>Administration <span class="muted mx-2">/</span> <strong>Journal d’activité</strong></span><span class="top-date">{{ now()->translatedFormat('l d F Y') }}</span></header>
    <main class="dashboard">
        <div class="page-heading"><div><p class="eyebrow">TRAÇABILITÉ DES ACTIONS</p><h1>Journal d’activité<span class="heading-dot">.</span></h1><p class="muted">Connexions, consultations, opérations, reçus, exports et actions d’administration avec leur date et leur heure.</p></div></div>

        @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif

        <section class="panel operations">
            <form method="get" class="filters activity-filters">
                <select class="form-select" name="user_id" aria-label="Utilisateur"><option value="">Tous les utilisateurs</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string)request('user_id') === (string)$user->id)>{{ $user->name }} — {{ $user->email }}</option>@endforeach</select>
                <select class="form-select" name="action" aria-label="Action"><option value="">Toutes les actions</option>@foreach($actions as $value=>$label)<option value="{{ $value }}" @selected(request('action')===$value)>{{ $label }}</option>@endforeach</select>
                <label>Du <input class="form-control" type="date" name="from" value="{{ request('from') }}"></label>
                <label>Au <input class="form-control" type="date" name="to" value="{{ request('to') }}"></label>
                <button class="btn btn-filter">Filtrer</button><a class="reset-link" href="{{ route('admin.activities.index') }}">Réinitialiser</a>
            </form>
            <div class="table-responsive"><table class="table ledger-table activity-table">
                <thead><tr><th>Date et heure</th><th>Utilisateur</th><th>Action</th><th>Détail</th><th>Adresse IP</th></tr></thead>
                <tbody>@forelse($logs as $log)<tr>
                    <td class="text-nowrap"><strong>{{ $log->created_at->format('d/m/Y') }}</strong><small class="d-block muted">{{ $log->created_at->format('H:i:s') }}</small></td>
                    <td><div class="operation-cell"><span class="avatar">{{ mb_strtoupper(mb_substr($log->user?->name ?? '?', 0, 1)) }}</span><div><strong>{{ $log->user?->name ?? 'Compte supprimé' }}</strong><small>{{ $log->user?->email }}</small></div></div></td>
                    <td><span class="badge-status neutral">{{ $actions[$log->action] ?? $log->action }}</span></td>
                    <td>{{ $log->description }}@if($log->metadata)<small class="d-block muted mt-1">{{ collect($log->metadata)->except('route')->map(fn($value,$key) => ucfirst($key).' : '.$value)->implode(' · ') }}</small>@endif</td>
                    <td class="text-nowrap">{{ $log->ip_address ?: '—' }}</td>
                </tr>@empty<tr><td colspan="5"><div class="empty-state"><span>◷</span><h3>Aucune activité trouvée</h3><p>Les prochaines actions des utilisateurs apparaîtront ici.</p></div></td></tr>@endforelse</tbody>
            </table></div><div class="pagination-wrap">{{ $logs->links('pagination::bootstrap-5') }}</div>
        </section>
        <footer class="page-footer"><span>CaisseFlow <span class="muted">Journal sécurisé des actions.</span></span><span>{{ $logs->total() }} activités</span></footer>
    </main>
</div>
@endsection
