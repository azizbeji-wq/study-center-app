<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1A — Création de la table rooms (salles).
 *
 * Remplacera à terme le champ texte libre `room` de schedule_items (Phase 1C).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();         // Ex : "Salle 101"
            $table->unsignedSmallInteger('capacity')->default(30);
            $table->string('description')->nullable(); // Équipements, usage, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};

