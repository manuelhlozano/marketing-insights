<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->string('sector')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('pais')->default('Colombia');
            $table->string('logo_url')->nullable();
            $table->string('token_acceso_maestro')->unique();
            $table->json('configuracion_branding')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('dashboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('titulo');
            $table->string('slug');
            $table->string('periodo_label')->default('Julio 2026');
            $table->date('periodo_inicio')->nullable();
            $table->date('periodo_fin')->nullable();
            $table->string('public_token')->unique();
            $table->boolean('es_publico')->default(true);
            $table->string('agencia_nombre')->default('Cibergenios Agencia Digital SAS');
            $table->text('resumen_ejecutivo')->nullable();
            $table->json('fases_timeline')->nullable();
            $table->json('kpis_destacados')->nullable();
            $table->json('compromisos_futuros')->nullable();
            $table->string('estado')->default('publicado');
            $table->timestamps();
        });

        Schema::create('modulos_indicadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained('dashboards')->onDelete('cascade');
            $table->string('canal'); // facebook, instagram, tiktok, pauta, email, produccion, crm
            $table->string('titulo_modulo');
            $table->string('subtitulo')->nullable();
            $table->string('icono')->nullable();
            $table->integer('orden')->default(1);
            $table->json('configuracion')->nullable();
            $table->timestamps();
        });

        Schema::create('datos_indicadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modulo_id')->constrained('modulos_indicadores')->onDelete('cascade');
            $table->string('clave_metrica');
            $table->string('etiqueta');
            $table->string('tipo_dato')->default('numero');
            $table->decimal('valor_actual', 14, 2)->nullable();
            $table->decimal('valor_anterior', 14, 2)->nullable();
            $table->decimal('porcentaje_variacion', 8, 2)->nullable();
            $table->string('subetiqueta')->nullable();
            $table->string('icono')->nullable();
            $table->string('status_color')->default('neutro');
            $table->json('datos_serie_o_desglose')->nullable();
            $table->integer('orden')->default(1);
            $table->timestamps();
        });

        Schema::create('entregables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained('dashboards')->onDelete('cascade');
            $table->integer('numero_item');
            $table->string('nombre');
            $table->string('formato'); // MP4, JPG, PDF, Word
            $table->string('categoria'); // Cartelera, Promoción, Concursos, Convenios, POP, Proyección, Efemérides, Otros
            $table->date('fecha_entrega');
            $table->string('url_archivo')->nullable();
            $table->string('url_preview')->nullable();
            $table->text('impacto_comercial')->nullable();
            $table->boolean('destacado')->default(false);
            $table->timestamps();
        });

        Schema::create('ingestas_archivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained('dashboards')->onDelete('cascade');
            $table->string('nombre_archivo_original');
            $table->string('tipo_fuente');
            $table->string('ruta_almacenamiento');
            $table->integer('registros_procesados')->default(0);
            $table->json('resumen_ingesta')->nullable();
            $table->string('estado')->default('completado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingestas_archivos');
        Schema::dropIfExists('entregables');
        Schema::dropIfExists('datos_indicadores');
        Schema::dropIfExists('modulos_indicadores');
        Schema::dropIfExists('dashboards');
        Schema::dropIfExists('empresas');
    }
};
