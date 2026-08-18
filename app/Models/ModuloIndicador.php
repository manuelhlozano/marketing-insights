<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuloIndicador extends Model
{
    protected $table = 'modulos_indicadores';

    protected $fillable = [
        'dashboard_id',
        'canal',
        'titulo_modulo',
        'subtitulo',
        'icono',
        'orden',
        'configuracion'
    ];

    protected $casts = [
        'configuracion' => 'array'
    ];

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    public function indicadores(): HasMany
    {
        return $this->hasMany(DatoIndicador::class, 'modulo_id')->orderBy('orden');
    }
}
