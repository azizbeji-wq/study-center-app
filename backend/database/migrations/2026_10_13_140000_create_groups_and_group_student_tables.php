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
        // 1. Table des groupes si elle n'existe pas encore
        if (!Schema::hasTable('groups')) {
            Schema::create('groups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('level')->nullable();
                $table->string('subject')->nullable();
                $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
                $table->unsignedBigInteger('teacher_id')->nullable()->index();
                $table->unsignedInteger('max_capacity')->nullable()->default(15);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Table pivot group_student reliant élèves et groupes
        if (!Schema::hasTable('group_student')) {
            Schema::create('group_student', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->timestamps();

                // Prévention stricte des doublons au niveau de la base de données
                $table->unique(['group_id', 'student_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_student');
        Schema::dropIfExists('groups');
    }
};
