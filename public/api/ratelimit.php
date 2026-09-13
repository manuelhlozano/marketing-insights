<?php
// Límite de peticiones compartido por todos los endpoints públicos.
//
// Antes cada archivo llevaba su propia copia del mismo bloque (leer JSON,
// comparar ventana, reescribir), y los endpoints públicos añadidos después
// —el sorteo en vivo y la ruleta— se quedaron sin ninguno. Tenerlo en un
// solo sitio es lo que evita que el próximo endpoint nazca otra vez sin
// protección.
//
// El contador vive en un archivo por clave dentro del directorio temporal.
// Es una ventana fija: el primer acierto marca el inicio y el contador se
// reinicia cuando esa ventana expira. Se usa flock() porque dos peticiones
// simultáneas leyendo y reescribiendo el mismo archivo se pisaban y perdían
// aciertos, que es justo lo que un atacante provoca al lanzar peticiones en
// paralelo.

if (function_exists('uopz_allow_exit')) { uopz_allow_exit(true); }

function mkt_ip_cliente(): string {
    // No se confía en X-Forwarded-For: cualquiera puede enviarla y saltarse
    // el límite con solo cambiar una cabecera.
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

/**
 * Devuelve true si la petición cabe dentro del límite, false si lo excede.
 * No responde ni corta por su cuenta: quien llama decide el formato del error.
 */
function mkt_rate_ok(string $clave, int $maxPeticiones, int $ventanaSegundos): bool {
    $archivo = sys_get_temp_dir() . '/mkt_rl_' . hash('sha256', $clave) . '.json';
    $fp = @fopen($archivo, 'c+');
    if (!$fp) return true;  // Si no se puede escribir, no se bloquea el servicio.

    try {
        if (!flock($fp, LOCK_EX)) return true;
        $contenido = stream_get_contents($fp);
        $datos = $contenido ? json_decode($contenido, true) : null;
        $ahora = time();

        if (!is_array($datos) || !isset($datos['inicio'], $datos['conteo'])
            || ($ahora - (int) $datos['inicio']) >= $ventanaSegundos) {
            $datos = ['inicio' => $ahora, 'conteo' => 0];
        }

        $datos['conteo']++;
        $dentroDelLimite = $datos['conteo'] <= $maxPeticiones;

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($datos));
        fflush($fp);
        return $dentroDelLimite;
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

/**
 * Aplica el límite y responde 429 en JSON si se excede.
 * $clave debe identificar al actor (IP, token de estación, o ambos).
 */
function mkt_rate_limit(string $clave, int $maxPeticiones, int $ventanaSegundos,
                        string $mensaje = 'Demasiadas peticiones. Espera un momento.'): void {
    if (mkt_rate_ok($clave, $maxPeticiones, $ventanaSegundos)) return;
    header('Content-Type: application/json; charset=utf-8');
    header('Retry-After: ' . $ventanaSegundos);
    http_response_code(429);
    echo json_encode(["status" => "error", "message" => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}
