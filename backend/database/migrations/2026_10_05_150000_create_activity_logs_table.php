<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('user_name')->default('Directeur');
                $table->string('user_role')->default('director'); // director, secretary, system
                $table->string('type')->default('general'); // payment, student, daycare, attendance, expense, teacher, schedule, account
                $table->string('action')->default('created'); // created, updated, deleted, recorded, login, etc.
                $table->text('message');
                $table->json('details')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamps();

                $table->index(['type', 'created_at']);
                $table->index(['user_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
