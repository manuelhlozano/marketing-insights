<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entregable extends Model
{
    protected $table = 'entregables';

    protected $fillable = [
        'dashboard_id',
        'numero_item',
        'nombre',
        'formato', // MP4, JPG, PDF, Word
        'categoria', // Cartelera, Promoción, Concursos, Convenios, POP, Proyección, Efemérides, Otros
        'fecha_entrega',
        'url_archivo',
        'url_preview',
        'impacto_comercial',
        'destacado'
    ];

    protected $casts = [
        'fecha_entrega' => 'date',
        'destacado' => 'boolean'
    ];

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }
}
