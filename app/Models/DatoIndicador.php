<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatoIndicador extends Model
{
    protected $table = 'datos_indicadores';

    protected $fillable = [
        'modulo_id',
        'clave_metrica',
        'etiqueta',
        'tipo_dato', // numero, porcentaje, moneda, tiempo, serie_temporal, demografico
        'valor_actual',
        'valor_anterior',
        'porcentaje_variacion',
        'subetiqueta',
        'icono',
        'status_color', // verde, rojo, azul, neutro
        'datos_serie_o_desglose',
        'orden'
    ];

    protected $casts = [
        'valor_actual' => 'decimal:2',
        'valor_anterior' => 'decimal:2',
        'porcentaje_variacion' => 'decimal:2',
        'datos_serie_o_desglose' => 'array'
    ];

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(ModuloIndicador::class, 'modulo_id');
    }
}
