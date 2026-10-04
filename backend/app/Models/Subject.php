<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Subject extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ─── Relations ───────────────────────────────────────────────────────────────

    /** Enseignants qui enseignent cette matière (pivot teacher_subject). */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'teacher_subject')
                    ->withTimestamps();
    }

    /** Inscriptions d'élèves à cette matière. */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentSubjectEnrollment::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Génère un slug normalisé à partir d'un nom de matière.
     * Utilisé par la commande de migration pour déduplication.
     * Ex : "Mathématiques" → "mathematiques"
     */
    public static function normalizeSlug(string $name): string
    {
        return Str::slug($name, '-');
    }
}
