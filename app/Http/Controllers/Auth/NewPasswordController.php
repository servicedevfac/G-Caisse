<?php

namespace App\Http\Controllers\Auth;

use App\Models\{ActivityLog, User};
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class NewPasswordController
{
    public function create(Request $request, string $token): View|RedirectResponse
    {
        $email = (string) $request->query('email');
        $user = User::query()
            ->where('email', $email)
            ->where('invitation_pending', false)
            ->where('is_active', true)
            ->first();

        if (!$user || !Password::broker()->tokenExists($user, $token)) {
            return redirect()->route('password.request')
                ->withInput(['email' => $email])
                ->withErrors(['email' => 'Ce lien de réinitialisation a expiré. Demandez un nouveau lien pour modifier votre mot de passe.']);
        }

        return view('auth.reset-password', ['token' => $token, 'email' => $email]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $resetUser = null;
        $status = Password::reset($data, function (User $user, string $password) use (&$resetUser) {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();
            event(new PasswordReset($user));
            $resetUser = $user;
        });

        if ($status !== Password::PASSWORD_RESET) {
            return redirect()->route('password.request')
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Ce lien de réinitialisation a expiré. Demandez un nouveau lien pour modifier votre mot de passe.']);
        }

        ActivityLog::record($resetUser, 'reset_password', 'A réinitialisé son mot de passe.', $request);

        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login')->with('status', 'Votre mot de passe a été modifié. Vous pouvez maintenant vous connecter.');
    }
}
