@extends('layout')
@section('title', 'Connexion')
@section('content')
<main class="auth-page">
    <div class="auth-card auth-card-login">
        @include('partials.auth-visual')
        <section class="auth-panel">
            <div class="auth-form-card">
                <p class="auth-kicker">ESPACE SÉCURISÉ</p>
                <h1>Connexion</h1>
                <p class="auth-intro">Accédez à votre espace de gestion de caisse.</p>
                @if(session('status'))<div class="alert alert-success password-reset-message" role="status">{{ session('status') }}</div>@endif
                @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
                <form method="post" action="{{ route('login.store') }}">@csrf
                    <label class="form-label" for="email">Adresse e-mail</label>
                    <div class="auth-input"><span aria-hidden="true">@</span><input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" placeholder="nom@entreprise.com" required autofocus autocomplete="username"></div>
                    <label class="form-label" for="password">Mot de passe</label>
                    <div class="auth-input"><span aria-hidden="true">●</span><input id="password" class="form-control" type="password" name="password" placeholder="Votre mot de passe" required autocomplete="current-password">@include('partials.password-toggle', ['target' => 'password'])</div>
                    <div class="login-options"><label class="remember-row"><input type="checkbox" name="remember" value="1"> <span>Rester connecté</span></label><a class="auth-forgot-link" href="{{ route('password.request') }}">Mot de passe oublié ?</a></div>
                    <button class="btn btn-primary auth-submit" type="submit">Se connecter <span>→</span></button>
                </form>
                <p class="auth-help">Votre accès est créé par l’administrateur. Consultez votre e-mail pour choisir votre mot de passe lors de votre première connexion.</p>
            </div>
        </section>
    </div>
</main>
@endsection
