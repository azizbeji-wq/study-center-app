<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'subject',          // Ancien champ texte conservé (compatibilité front)
        'phone',
        'email',
        'weekly_lessons',
        'salary_per_lesson',
        'fixed_student_rate',
        'remuneration_type',
        'rate_percentage',
        'payout_status',
        'payout_date',
        'payout_month',
        'payout_amount',
    ];

    protected $casts = [
        'payout_date' => 'date',
        'payout_amount' => 'decimal:2',
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

