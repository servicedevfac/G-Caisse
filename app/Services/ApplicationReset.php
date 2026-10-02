<?php

namespace App\Services;

use App\Models\{Document, Transaction, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ApplicationReset
{
    public function run(): User
    {
        $admins = User::query()->where('is_admin', true)->orderBy('id')->get();
        if ($admins->isEmpty()) {
            throw new RuntimeException('Reset annulé : aucun compte administrateur n’a été trouvé.');
        }
        if ($admins->count() > 1) {
            throw new RuntimeException('Reset annulé : plusieurs administrateurs existent. Conservez manuellement un seul administrateur avant de relancer la commande.');
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

        return $admin->fresh();
    }
}
