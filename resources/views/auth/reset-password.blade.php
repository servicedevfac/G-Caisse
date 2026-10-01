@extends('layout')
@section('title', 'Nouveau mot de passe')
@section('content')
<main class="auth-page">
    <div class="auth-card auth-card-register">
        @include('partials.auth-visual')
        <section class="auth-panel">
            <div class="auth-form-card">
                <p class="auth-kicker">SÉCURISATION DU COMPTE</p>
                <h1>Nouveau mot de passe</h1>
                <p class="auth-intro">Choisissez un mot de passe d’au moins 8 caractères pour votre compte CaisseFlow.</p>
                @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
                <form method="post" action="{{ route('password.update') }}">@csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <label class="form-label" for="email">Adresse e-mail</label>
                    <div class="auth-input"><span aria-hidden="true">@</span><input id="email" class="form-control" type="email" name="email" value="{{ old('email', $email) }}" required readonly autocomplete="username"></div>
                    <label class="form-label" for="password">Nouveau mot de passe</label>
                    <div class="auth-input"><span aria-hidden="true">●</span><input id="password" class="form-control" type="password" name="password" placeholder="8 caractères minimum" minlength="8" required autocomplete="new-password">@include('partials.password-toggle', ['target' => 'password'])</div>
                    <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
                    <div class="auth-input"><span aria-hidden="true">●</span><input id="password_confirmation" class="form-control" type="password" name="password_confirmation" placeholder="Répétez le mot de passe" required autocomplete="new-password">@include('partials.password-toggle', ['target' => 'password_confirmation'])</div>
                    <button class="btn btn-primary auth-submit" type="submit">Enregistrer mon mot de passe <span>→</span></button>
                </form>
            </div>
        </section>
    </div>
</main>
@endsection
