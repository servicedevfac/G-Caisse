<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('company', 50)->nullable()->after('type')->index();
            $table->string('beneficiary')->nullable()->after('company');
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 80)->index();
            $table->string('description', 500);
            $table->nullableMorphs('subject');
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::table('transactions', fn (Blueprint $table) => $table->dropColumn(['company', 'beneficiary']));
    }
};
