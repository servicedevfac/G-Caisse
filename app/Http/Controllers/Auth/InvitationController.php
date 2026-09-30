<?php

namespace App\Http\Controllers\Auth;

use App\Models\UserInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\Validation\Rules\Password;

class InvitationController
{
    public function show(string $token)
    {
        $invitation = $this->validInvitation($token);

        return view('auth.accept-invitation', ['invitation' => $invitation, 'token' => $token]);
    }

    public function store(Request $request, string $token)
    {
        $invitation = $this->validInvitation($token);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        $user = $invitation->user;
        DB::transaction(function () use ($user, $invitation, $data) {
            $user->update([
                'name' => $data['name'],
                'password' => $data['password'],
                'invitation_pending' => false,
                'is_active' => true,
            ]);
            $invitation->delete();
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Votre mot de passe a été créé. Bienvenue sur CaisseFlow.');
    }

    private function validInvitation(string $token): UserInvitation
    {
        $invitation = UserInvitation::with('user')
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->first();

        abort_unless($invitation && $invitation->user->invitation_pending, 410, 'Ce lien d’invitation est invalide ou a expiré.');

        return $invitation;
    }
}
