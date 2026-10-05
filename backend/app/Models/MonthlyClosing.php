<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyClosing extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'total_revenue' => 'float',
        'total_expenses' => 'float',
        'total_teacher_payouts' => 'float',
        'net_profit' => 'float',
        'is_closed' => 'boolean',
        'closed_at' => 'datetime',
    ];

    public function closedByUser()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
