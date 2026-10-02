<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('attachment_original_name')->nullable();
            $table->string('attachment_path')->nullable()->unique();
            $table->string('attachment_mime_type', 150)->nullable();
            $table->unsignedBigInteger('attachment_original_size')->nullable();
            $table->unsignedBigInteger('attachment_stored_size')->nullable();
            $table->boolean('attachment_is_compressed')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['attachment_path']);
            $table->dropColumn([
                'attachment_original_name', 'attachment_path', 'attachment_mime_type',
                'attachment_original_size', 'attachment_stored_size', 'attachment_is_compressed',
            ]);
        });
    }
};
