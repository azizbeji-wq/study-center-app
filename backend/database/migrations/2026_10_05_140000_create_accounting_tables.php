<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Dépenses réelles du centre
        if (!Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('category')->default('autres'); // loyer, electricite, fournitures, entretien, publicite, salaires, autres
                $table->decimal('amount', 10, 2);
                $table->date('date');
                $table->string('payment_method')->default('especes'); // especes, cheque, virement, autre
                $table->boolean('is_recurring')->default(false);
                $table->string('recurring_frequency')->nullable(); // monthly, yearly
                $table->string('receipt_reference')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // 2. Traçabilité & Journal d'Audit des paiements
        if (!Schema::hasTable('payment_audit_logs')) {
            Schema::create('payment_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('user_name')->nullable();
                $table->string('user_role')->nullable();
                $table->string('action'); // created, updated, deleted, marked_paid
                $table->decimal('amount', 10, 2)->nullable();
                $table->string('student_name')->nullable();
                $table->string('payment_method')->nullable();
                $table->json('details')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamps();
            });
        }

        // 3. Clôtures mensuelles de comptabilité
        if (!Schema::hasTable('monthly_closings')) {
            Schema::create('monthly_closings', function (Blueprint $table) {
                $table->id();
                $table->string('month'); // Ex: 2026-05
                $table->string('school_year')->nullable(); // Ex: 2025-2026
                $table->decimal('total_revenue', 10, 2)->default(0);
                $table->decimal('total_expenses', 10, 2)->default(0);
                $table->decimal('total_teacher_payouts', 10, 2)->default(0);
                $table->decimal('net_profit', 10, 2)->default(0);
                $table->timestamp('closed_at');
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->boolean('is_closed')->default(true);
                $table->timestamps();
            });
        }

        // Ajouter payment_method dans payments si inexistant
        if (Schema::hasTable('payments') && !Schema::hasColumn('payments', 'payment_method')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('payment_method')->default('especes')->after('status');
                $table->foreignId('created_by')->nullable()->after('payment_method')->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_closings');
        Schema::dropIfExists('payment_audit_logs');
        Schema::dropIfExists('expenses');

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'payment_method')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn(['payment_method', 'created_by']);
            });
        }
    }
};
