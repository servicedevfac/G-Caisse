<?php
use App\Models\{Document, Transaction, User};
use App\Services\PrivateFileStorage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
Artisan::command('caisse:user {email} {--admin}', function () {
    $data = ['name' => $this->ask('Nom'), 'email' => $this->argument('email'), 'password' => $this->secret('Mot de passe (12 caractères minimum)')];
    $validator = Validator::make($data, ['name' => 'required|string|max:255', 'email' => 'required|email|unique:users', 'password' => ['required', Password::min(12)]]);
    if ($validator->fails()) { $this->error($validator->errors()->first()); return 1; }
    User::create([...$data, 'is_admin' => $this->option('admin'), 'is_active' => true]);
    $this->info('Utilisateur créé.');
});

Artisan::command('caisse:admin {email}', function () {
    $user = User::where('email', $this->argument('email'))->first();
    if (!$user) { $this->error('Utilisateur introuvable. Inscrivez d’abord ce compte.'); return 1; }
    $user->update(['is_admin' => true, 'is_active' => true]);
    $this->info("{$user->email} est maintenant administrateur et actif.");
    return 0;
})->purpose('Promouvoir un utilisateur inscrit en administrateur');

Artisan::command('caisse:reset {--force : Confirmer la suppression de toutes les données}', function () {
    $admins = User::query()->where('is_admin', true)->orderBy('id')->get();
    if ($admins->isEmpty()) {
        $this->error('Reset annulé : aucun compte administrateur n’a été trouvé.');
        return 1;
    }
    if ($admins->count() > 1) {
        $this->error('Reset annulé : plusieurs administrateurs existent. Conservez manuellement un seul administrateur avant de relancer la commande.');
        return 1;
    }
    if (!$this->option('force')) {
        $this->error('Cette commande supprime définitivement les données. Relancez-la avec --force.');
        return 1;
    }

    $admin = $admins->first();
    $storage = app(PrivateFileStorage::class);
    Document::query()->pluck('storage_path')
        ->merge(Transaction::query()->whereNotNull('attachment_path')->pluck('attachment_path'))
        ->filter()->unique()->each(fn ($path) => $storage->delete($path));

    DB::transaction(function () use ($admin) {
        DB::table('activity_logs')->delete();
        DB::table('documents')->delete();
        DB::table('transactions')->delete();
        DB::table('user_invitations')->delete();
        DB::table('password_reset_tokens')->delete();
        User::query()->whereKeyNot($admin->id)->delete();
        $admin->update([
            'is_admin' => true,
            'is_active' => true,
            'invitation_pending' => false,
            'remember_token' => null,
        ]);
        DB::table('cash_accounts')->where('id', '<>', 1)->delete();
        DB::table('cash_accounts')->updateOrInsert(
            ['id' => 1],
            ['balance_minor' => 0, 'updated_at' => now(), 'created_at' => now()]
        );
        if (Schema::hasTable('cache')) DB::table('cache')->delete();
        if (Schema::hasTable('cache_locks')) DB::table('cache_locks')->delete();
    });

    if (DB::getDriverName() === 'mysql') {
        foreach (['activity_logs', 'documents', 'transactions', 'user_invitations'] as $table) {
            DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = 1");
        }
    }

    $this->info("Reset terminé. Seul l’administrateur {$admin->email} a été conservé et le solde est à 0.");
    return 0;
})->purpose('Supprimer toutes les données de caisse en conservant l’unique administrateur');
