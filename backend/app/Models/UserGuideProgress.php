<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGuideProgress extends Model
{
    protected $table = 'user_guide_progress';

    protected $fillable = [
        'user_id',
        'key',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    /**
     * Liste fermée des clés valides — toute clé hors de cette liste est refusée.
     */
    public static function validKeys(): array
    {
        return [
            // Checklist démarrage
            'checklist.subjects',
            'checklist.teachers',
            'checklist.pricing',
            'checklist.first_student',
            'checklist.first_attendance',
            // Visites guidées
            'tour.students',
            'tour.attendance',
            'tour.payments',
            'tour.schedule',
            'tour.teachers',
            'tour.today',
            'tour.groups',
            // Bandeaux "nouveau ici"
            'banner.students',
            'banner.attendance',
            'banner.payments',
            'banner.schedule',
            'banner.teachers',
            'banner.today',
            'banner.groups',
            'banner.daycare',
            'banner.schoolcalendar',
            'banner.dashboard',
            'banner.reports',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

