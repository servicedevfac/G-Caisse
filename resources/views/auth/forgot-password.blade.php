@extends('layout')
@section('title', 'Mot de passe oublié')
@section('content')
<main class="auth-page">
    <div class="auth-card">
        @include('partials.auth-visual')
        <section class="auth-panel">
            <div class="auth-form-card">
                <p class="auth-kicker">RÉCUPÉRATION DU COMPTE</p>
                <h1>Mot de passe oublié ?</h1>
                <p class="auth-intro">Saisissez votre adresse e-mail. Nous vous enverrons un lien sécurisé pour choisir un nouveau mot de passe.</p>
                @if(session('status'))<div class="alert alert-success password-reset-message" role="status">{{ session('status') }}</div>@endif
                @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
                <form method="post" action="{{ route('password.email') }}">@csrf
                    <label class="form-label" for="email">Adresse e-mail</label>
                    <div class="auth-input"><span aria-hidden="true">@</span><input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" placeholder="nom@entreprise.com" required autofocus autocomplete="email"></div>
                    <button class="btn btn-primary auth-submit" type="submit">Envoyer le lien <span>→</span></button>
                </form>
                <p class="auth-switch"><a href="{{ route('login') }}">← Retour à la connexion</a></p>
            </div>
        </section>
    </div>
</main>
@endsection
