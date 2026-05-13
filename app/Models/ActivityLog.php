<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $table = 'ais_log_aktivitas';

    protected $fillable = [
        'id_pengguna',
        'aksi',
        'subjek_tipe',
        'subjek_id',
        'properti',
        'alamat_ip',
        'dibuat_pada',
    ];

    protected function casts(): array
    {
        return [
            'properti' => 'array',
            'dibuat_pada' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo('subject', 'subjek_tipe', 'subjek_id');
    }
}
