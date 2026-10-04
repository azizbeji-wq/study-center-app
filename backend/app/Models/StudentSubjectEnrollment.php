<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSubjectEnrollment extends Model
{
    protected $fillable = [
        'student_id',
        'subject_name',     // Ancien champ texte conservé (compatibilité front Vue actuel)
        'subject_id',       // Nouvelle FK vers subjects (Phase 1A) — nullable pendant transition
        'teacher_id',
        'group_name',
        'price_per_month',
        'price_per_session',
    ];

    // ─── Relations ───────────────────────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Matière structurée (nouvelle relation Phase 1A).
     * Nullable : peut être null si subject_id n'a pas encore été renseigné.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}

