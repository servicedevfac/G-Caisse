@extends('layout')
@section('title', 'Inscription')
@section('content')
<main class="auth-page">
    <div class="auth-card auth-card-register">
        @include('partials.auth-visual')
        <section class="auth-panel">
            <div class="auth-form-card">
                <p class="auth-kicker">CRÉER VOTRE ACCÈS</p>
                <h1>Inscription</h1>
                <p class="auth-intro">Rejoignez la caisse partagée de votre entreprise.</p>
                @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                <form method="post" action="{{ route('register.store') }}">@csrf
                    <label class="form-label" for="name">Nom complet</label>
                    <div class="auth-input"><span aria-hidden="true">●</span><input id="name" class="form-control" name="name" value="{{ old('name') }}" placeholder="Votre nom et prénom" required autofocus autocomplete="name"></div>
                    <label class="form-label" for="email">Adresse e-mail professionnelle</label>
                    <div class="auth-input"><span aria-hidden="true">@</span><input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" placeholder="nom@entreprise.com" required autocomplete="username"></div>
                    <label class="form-label" for="password">Mot de passe <span class="muted">(12 caractères minimum)</span></label>
                    <div class="auth-input"><span aria-hidden="true">●</span><input id="password" class="form-control" type="password" name="password" placeholder="Choisissez un mot de passe" required autocomplete="new-password">@include('partials.password-toggle', ['target' => 'password'])</div>
                    <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
                    <div class="auth-input"><span aria-hidden="true">●</span><input id="password_confirmation" class="form-control" type="password" name="password_confirmation" placeholder="Répétez le mot de passe" required autocomplete="new-password">@include('partials.password-toggle', ['target' => 'password_confirmation'])</div>
                    <button class="btn btn-primary auth-submit" type="submit">Créer mon compte <span>→</span></button>
                </form>
                <p class="auth-switch">Déjà inscrit ? <a href="{{ route('login') }}">Se connecter</a></p>
            </div>
        </section>
    </div>
</main>
@endsection
