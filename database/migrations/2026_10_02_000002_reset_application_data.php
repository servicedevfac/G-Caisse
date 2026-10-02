<?php

use App\Services\ApplicationReset;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('users')->exists()) {
            app(ApplicationReset::class)->run();
        }
    }

    public function down(): void
    {
        // Une suppression de données ne peut pas être annulée.
    }
};
