<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'level',
        'subject',
        'subject_id',
        'teacher_id',   // FK vers teachers.id (enseignant responsable)
        'user_id',      // FK vers users.id (créateur du groupe)
        'max_capacity',
        'notes',
    ];

    protected $casts = [
        'max_capacity' => 'integer',
    ];

    /**
     * Les élèves inscrits dans ce groupe (table pivot group_student).
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'group_student')
                    ->withTimestamps();
    }

    /**
     * Matière enseignée dans ce groupe.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Enseignant responsable de ce groupe (FK teachers.id).
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    /**
     * Utilisateur créateur du groupe (FK users.id).
     * Séparé de teacher() pour éviter toute ambigüité.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
