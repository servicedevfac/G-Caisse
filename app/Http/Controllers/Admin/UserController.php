<?php
namespace App\Http\Controllers\Admin;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserController
{
    public function index()
    {
        return view('admin.users', ['users' => User::withCount('transactions')->orderByDesc('created_at')->paginate(15)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['email' => 'required|string|lowercase|email|max:255|unique:users']);

        $user = User::create([
            'name' => Str::before($data['email'], '@'),
            'email' => $data['email'],
            'password' => Str::random(64),
            'is_admin' => false,
            'is_active' => true,
            'invitation_pending' => true,
        ]);

        $this->sendInvitation($user);

        return back()->with('success', 'Utilisateur créé. Le lien de création du mot de passe a été envoyé.');
    }

    public function resendInvitation(User $user)
    {
        abort_unless($user->invitation_pending, 422, 'Ce compte est déjà activé.');
        $this->sendInvitation($user);

        return back()->with('success', 'Une nouvelle invitation a été envoyée.');
    }

    public function toggleStatus(Request $request, User $user)
    {
        abort_if($user->is_admin, 422, 'Un administrateur ne peut pas être bloqué.');
        $user->update(['is_active' => !$user->is_active]);
        return back()->with('success', $user->is_active ? 'Utilisateur débloqué.' : 'Utilisateur bloqué.');
    }

    private function sendInvitation(User $user): void
    {
        $token = Str::random(64);

        DB::transaction(function () use ($user, $token) {
            $user->invitation()->updateOrCreate([], [
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addHours(72),
            ]);
        });

        $user->notify(new UserInvitationNotification(route('invitation.show', $token)));
    }
}
