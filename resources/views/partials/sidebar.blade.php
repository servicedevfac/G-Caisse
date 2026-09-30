<aside class="sidebar">
    <a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark">CF</span> CaisseFlow</a>
    <div class="workspace"><span class="workspace-icon">E</span><div>Mon entreprise<small>Espace de gestion</small></div><span class="ms-auto muted">⌄</span></div>
    <p class="nav-label">ESPACE CAISSE</p>
    <nav aria-label="Navigation principale">
        <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span>◫</span> Tableau de bord</a>
        <details class="nav-group" @if(request()->routeIs('entries.*', 'expenses.*', 'history.*')) open @endif>
            <summary><span>⇄</span> Mouvements de caisse <b>⌄</b></summary>
            <div class="nav-submenu">
                <a class="{{ request()->routeIs('entries.*') ? 'active' : '' }}" href="{{ route('entries.index') }}">Gestion des entrées</a>
                <a class="{{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}">Gestion des sorties</a>
                <a class="{{ request()->routeIs('history.*') ? 'active' : '' }}" href="{{ route('history.index') }}">Historique des mouvements</a>
            </div>
        </details>
        <a class="{{ request()->routeIs('history.*') && request('period') ? 'active' : '' }}" href="{{ route('history.index', ['period' => 'month']) }}"><span>▥</span> Rapports & statistiques</a>
        @if(auth()->user()->is_admin)
            <a class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><span>◎</span> Utilisateurs</a>
            <a class="{{ request()->routeIs('admin.activities.*') ? 'active' : '' }}" href="{{ route('admin.activities.index') }}"><span>◷</span> Journal d’activité</a>
        @endif
    </nav>
    <form method="post" action="{{ route('logout') }}" class="logout-form">@csrf<button class="logout-button" type="submit"><span aria-hidden="true">↪</span> Déconnexion</button></form>
    <div class="sidebar-note"><span class="status-dot"></span> Une caisse bien suivie<p>Chaque opération compte.<br>Gardez une trace, en toute simplicité.</p></div>
    <div class="profile"><span class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><div>{{ auth()->user()->name }}<small>{{ auth()->user()->is_admin ? 'Administrateur' : 'Utilisateur de la caisse' }}</small></div></div>
</aside>
