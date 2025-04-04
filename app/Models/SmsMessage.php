<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'phone_number',
        'message',
        'sim_number',
        'received_at',
        'raw_payload',
        'device_id',
        'webhook_id'
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];
} 