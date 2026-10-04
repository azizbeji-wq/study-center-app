<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = [
        'name',
        'capacity',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity'  => 'integer',
    ];

    // ─── Relations ───────────────────────────────────────────────────────────────

    /**
     * Créneaux d'emploi du temps attribués à cette salle.
     * (Sera actif après la Phase 1C qui ajoute room_id à schedule_items.)
     */
    public function scheduleItems(): HasMany
    {
        return $this->hasMany(ScheduleItem::class);
    }
}
