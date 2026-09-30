@extends('layout')
@section('title', 'Connexion')
@section('content')
<main class="auth-page">
    <div class="auth-card">
        @include('partials.auth-visual')
        <section class="auth-panel">
            <div class="auth-form-card">
                <p class="auth-kicker">ESPACE SÉCURISÉ</p>
                <h1>Connexion</h1>
                <p class="auth-intro">Accédez à votre espace de gestion de caisse.</p>
                @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
                <form method="post" action="{{ route('login.store') }}">@csrf
                    <label class="form-label" for="email">Adresse e-mail</label>
                    <div class="auth-input"><span aria-hidden="true">@</span><input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" placeholder="nom@entreprise.com" required autofocus autocomplete="username"></div>
                    <label class="form-label" for="password">Mot de passe</label>
                    <div class="auth-input"><span aria-hidden="true">●</span><input id="password" class="form-control" type="password" name="password" placeholder="Votre mot de passe" required autocomplete="current-password">@include('partials.password-toggle', ['target' => 'password'])</div>
                    <label class="remember-row"><input type="checkbox" name="remember" value="1"> <span>Rester connecté</span></label>
                    <button class="btn btn-primary auth-submit" type="submit">Se connecter <span>→</span></button>
                </form>
                <div class="auth-divider"><span>ou</span></div>
                <a class="btn auth-register-button" href="{{ route('register') }}">S’inscrire</a>
                <p class="auth-help">Nouveau dans l’entreprise ? Créez votre accès pour rejoindre la caisse partagée.</p>
            </div>
        </section>
    </div>
</main>
@endsection
