<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAuditLog extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'float',
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class)->withTrashed();
    }
}
