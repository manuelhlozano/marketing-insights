<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngestaArchivo extends Model
{
    protected $table = 'ingestas_archivos';

    protected $fillable = [
        'dashboard_id',
        'nombre_archivo_original',
        'tipo_fuente', // meta_videos_csv, ig_posts_csv, ig_stories_csv, brevo_csv, pauta_csv, entregables_csv, json
        'ruta_almacenamiento',
        'registros_procesados',
        'resumen_ingesta',
        'estado' // completado, error, pendiente
    ];

    protected $casts = [
        'resumen_ingesta' => 'array'
    ];

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }
}
