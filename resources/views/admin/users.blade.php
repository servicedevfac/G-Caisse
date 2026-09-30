@extends('layout')
@section('title', 'Utilisateurs')
@section('content')
@include('partials.sidebar')
<div class="main-shell">
    <header class="topbar"><span>Administration <span class="muted mx-2">/</span> <strong>Utilisateurs</strong></span><span class="top-date">{{ now()->translatedFormat('l d F Y') }}</span></header>
    <main class="dashboard">
        <div class="page-heading">
            <div><p class="eyebrow">GESTION DES ACCÈS</p><h1>Utilisateurs<span class="heading-dot">.</span></h1><p class="muted">L’administrateur crée les accès et chaque utilisateur choisit ensuite son mot de passe.</p></div>
            <a class="btn btn-light" href="{{ route('dashboard') }}">← Retour au dashboard</a>
        </div>

        @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif

        <section class="panel mb-4">
            <div class="panel-heading"><div><h2>Inviter un utilisateur</h2><p>Saisissez son adresse e-mail. Un lien valable 72 heures lui permettra de définir son nom et son mot de passe.</p></div></div>
            <form method="post" action="{{ route('admin.users.store') }}" class="d-flex flex-wrap gap-2 align-items-end">@csrf
                <div class="flex-grow-1"><label class="form-label" for="invite-email">Adresse e-mail professionnelle</label><input id="invite-email" class="form-control" type="email" name="email" value="{{ old('email') }}" placeholder="nom@entreprise.com" required></div>
                <button class="btn btn-primary" type="submit">Créer et envoyer l’invitation</button>
            </form>
        </section>

        <div class="stat-grid admin-stats">
            <article class="stat-card"><div class="stat-title">Comptes activés</div><div class="stat-value">{{ App\Models\User::where('invitation_pending', false)->count() }}</div><div class="stat-foot">Administrateur et utilisateurs</div></article>
            <article class="stat-card"><div class="stat-title">Invitations en attente</div><div class="stat-value">{{ App\Models\User::where('invitation_pending', true)->count() }}</div><div class="stat-foot">Mot de passe non créé</div></article>
            <article class="stat-card"><div class="stat-title">Comptes bloqués</div><div class="stat-value">{{ App\Models\User::where('is_active', false)->where('invitation_pending', false)->count() }}</div><div class="stat-foot">Accès suspendu</div></article>
        </div>

        <section class="panel operations">
            <div class="panel-heading"><div><h2>Comptes de l’entreprise</h2><p>Le blocage conserve le compte et toutes ses opérations.</p></div></div>
            <div class="table-responsive"><table class="table ledger-table">
                <thead><tr><th>Utilisateur</th><th>Rôle</th><th>Création</th><th>Opérations</th><th>Statut</th><th class="text-end">Action</th></tr></thead>
                <tbody>@foreach($users as $user)
                    <tr>
                        <td><div class="operation-cell"><span class="avatar">{{ mb_strtoupper(mb_substr($user->email,0,1)) }}</span><div><strong>{{ $user->invitation_pending ? 'Invitation en attente' : $user->name }}</strong><small>{{ $user->email }}</small></div></div></td>
                        <td>{{ $user->is_admin ? 'Administrateur' : 'Utilisateur' }}</td>
                        <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $user->transactions_count }}</td>
                        <td>@if($user->invitation_pending)<span class="badge-status neutral">En attente</span>@else<span class="badge-status {{ $user->is_active ? '' : 'cancelled' }}">{{ $user->is_active ? 'Actif' : 'Bloqué' }}</span>@endif</td>
                        <td class="text-end">
                            @if($user->is_admin)
                                <span class="muted small">Compte principal</span>
                            @elseif($user->invitation_pending)
                                <form method="post" action="{{ route('admin.users.invitation', $user) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary">Renvoyer le lien</button></form>
                            @else
                                <form method="post" action="{{ route('admin.users.toggle-status', $user) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $user->is_active ? 'Bloquer' : 'Débloquer' }}</button></form>
                            @endif
                        </td>
                    </tr>
                @endforeach</tbody>
            </table></div>
            <div class="pagination-wrap">{{ $users->links('pagination::bootstrap-5') }}</div>
        </section>
        <footer class="page-footer"><span>CaisseFlow <span class="muted">La sérénité, dans vos comptes.</span></span><span>Administration des accès</span></footer>
    </main>
</div>
@endsection
