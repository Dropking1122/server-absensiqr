<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeartbeatLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'installation_id',
        'app_version',
        'wa_online',
        'php_version',
        'received_at',
    ];

    protected $casts = [
        'wa_online'   => 'boolean',
        'received_at' => 'datetime',
    ];
}
