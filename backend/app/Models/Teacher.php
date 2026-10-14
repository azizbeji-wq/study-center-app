<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'name',
        'subject',          // Ancien champ texte conservé (compatibilité front)
        'phone',
        'email',
        'weekly_lessons',
        'salary_per_lesson',
        'fixed_student_rate',
        'center_fixed_per_student',
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

    /** Utilisateur associé (compte de connexion). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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

    /**
     * Groupes pédagogiques sous la responsabilité de cet enseignant.
     */
    public function groups(): HasMany
    {
        return $this->hasMany(Group::class, 'teacher_id');
    }

    /**
     * Registre de présence des séances tenues par cet enseignant.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Règlements / paiements associés aux cours de cet enseignant.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}

