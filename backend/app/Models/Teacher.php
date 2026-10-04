<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    protected $fillable = [
        'name',
        'subject',          // Ancien champ texte conservé (compatibilité front)
        'phone',
        'email',
        'weekly_lessons',
        'salary_per_lesson',
        'remuneration_type',
        'rate_percentage',
    ];

    // ─── Relations ───────────────────────────────────────────────────────────────

    /** Inscriptions d'élèves gérées par cet enseignant. */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentSubjectEnrollment::class);
    }

    /**
     * Matières enseignées (nouvelle relation Phase 1A).
     * Remplacera progressivement teachers.subject (texte libre).
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'teacher_subject')
                    ->withTimestamps();
    }
}

