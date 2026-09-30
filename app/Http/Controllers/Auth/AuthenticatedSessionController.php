<?php
namespace App\Http\Controllers\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, RateLimiter};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\ActivityLog;
// Breeze session flow adapted to the Bootstrap interface.
class AuthenticatedSessionController
{
    public function create() { return view('auth.login'); }
    public function store(Request $request) {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $key = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) throw ValidationException::withMessages(['email' => 'Trop de tentatives. Réessayez dans '.RateLimiter::availableIn($key).' secondes.']);
        if (\App\Models\User::where('email', $credentials['email'])->where('invitation_pending', true)->exists()) {
            throw ValidationException::withMessages(['email' => 'Votre compte attend son activation. Utilisez le lien reçu par e-mail.']);
        }
        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Adresse e-mail ou mot de passe incorrect.']);
        }
        if (!$request->user()->is_active) {
            Auth::logout();
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Votre compte a été bloqué. Contactez l’administrateur.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        ActivityLog::record($request->user(), 'login', 'S’est connecté à CaisseFlow.', $request);
        return redirect()->intended(route('dashboard'));
    }
    public function destroy(Request $request) {
        Auth::guard('web')->logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
