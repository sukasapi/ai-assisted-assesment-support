<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiOpenRouterModel extends Model
{
    use SoftDeletes;

    protected $table = 'ais_model_ai';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    public const DELETED_AT = 'dihapus_pada';

    protected $fillable = [
        'id_model_openrouter',
        'label',
        'urutan',
        'utama',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'utama' => 'boolean',
            'aktif' => 'boolean',
            'urutan' => 'integer',
        ];
    }
}
