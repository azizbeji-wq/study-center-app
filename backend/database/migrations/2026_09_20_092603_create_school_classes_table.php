<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_classes', function (Blueprint $table) {

            $table->id();

            $table->foreignId('school_id')
                ->constrained('schools')
                ->cascadeOnDelete();

            // Niveau primaire : 1 à 6
            $table->unsignedTinyInteger('level');

            // Classe : أ / ب / ج / د
            $table->string('section', 10);

            $table->timestamps();

            // Empêche par exemple deux "3ème ب"
            // dans la même école
            $table->unique([
                'school_id',
                'level',
                'section'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_classes');
    }
};