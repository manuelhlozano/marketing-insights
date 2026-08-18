<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Dashboard extends Model
{
    protected $table = 'dashboards';

    protected $fillable = [
        'empresa_id',
        'titulo',
        'slug',
        'periodo_label',
        'periodo_inicio',
        'periodo_fin',
        'public_token',
        'es_publico',
        'agencia_nombre',
        'resumen_ejecutivo',
        'fases_timeline',
        'kpis_destacados',
        'compromisos_futuros',
        'estado'
    ];

    protected $casts = [
        'es_publico' => 'boolean',
        'periodo_inicio' => 'date',
        'periodo_fin' => 'date',
        'fases_timeline' => 'array',
        'kpis_destacados' => 'array',
        'compromisos_futuros' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($dash) {
            if (empty($dash->slug)) {
                $dash->slug = Str::slug($dash->periodo_label ?? $dash->titulo);
            }
            if (empty($dash->public_token)) {
                $dash->public_token = 'mkt_live_' . Str::random(16);
            }
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function modulos(): HasMany
    {
        return $this->hasMany(ModuloIndicador::class)->orderBy('orden');
    }

    public function entregables(): HasMany
    {
        return $this->hasMany(Entregable::class)->orderBy('numero_item');
    }

    public function ingestas(): HasMany
    {
        return $this->hasMany(IngestaArchivo::class)->latest();
    }
}
