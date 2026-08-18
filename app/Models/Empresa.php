<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Empresa extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
        'nombre',
        'slug',
        'sector',
        'ciudad',
        'pais',
        'logo_url',
        'token_acceso_maestro',
        'configuracion_branding',
        'activo'
    ];

    protected $casts = [
        'configuracion_branding' => 'array',
        'activo' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($empresa) {
            if (empty($empresa->slug)) {
                $empresa->slug = Str::slug($empresa->nombre);
            }
            if (empty($empresa->token_acceso_maestro)) {
                $empresa->token_acceso_maestro = 'mkt_live_' . Str::random(12);
            }
        });
    }

    public function dashboards(): HasMany
    {
        return $this->hasMany(Dashboard::class);
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
