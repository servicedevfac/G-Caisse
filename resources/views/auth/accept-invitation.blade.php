@extends('layout')
@section('title', 'Créer votre mot de passe')
@section('content')
<main class="auth-page">
    <div class="auth-card auth-card-register">
        @include('partials.auth-visual')
        <section class="auth-panel">
            <div class="auth-form-card">
                <p class="auth-kicker">ACTIVER VOTRE ACCÈS</p>
                <h1>Créez votre accès</h1>
                <p class="auth-intro">Bienvenue sur CaisseFlow. Choisissez votre nom et votre mot de passe pour activer <strong>{{ $invitation->user->email }}</strong>.</p>
                @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                <form method="post" action="{{ route('invitation.store', $token) }}">@csrf
                    <label class="form-label" for="name">Nom complet</label>
                    <div class="auth-input"><span aria-hidden="true">●</span><input id="name" class="form-control" name="name" value="{{ old('name') }}" placeholder="Votre nom et prénom" required autofocus autocomplete="name"></div>
                    <label class="form-label" for="password">Mot de passe <span class="muted">(8 caractères minimum)</span></label>
                    <div class="auth-input"><span aria-hidden="true">●</span><input id="password" class="form-control" type="password" name="password" placeholder="Choisissez votre mot de passe" minlength="8" required autocomplete="new-password">@include('partials.password-toggle', ['target' => 'password'])</div>
                    <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
                    <div class="auth-input"><span aria-hidden="true">●</span><input id="password_confirmation" class="form-control" type="password" name="password_confirmation" placeholder="Répétez votre mot de passe" required autocomplete="new-password">@include('partials.password-toggle', ['target' => 'password_confirmation'])</div>
                    <button class="btn btn-primary auth-submit" type="submit">Activer mon compte <span>→</span></button>
                </form>
                <p class="auth-switch"><a href="{{ route('login') }}">Retour à la connexion</a></p>
            </div>
        </section>
    </div>
</main>
@endsection
