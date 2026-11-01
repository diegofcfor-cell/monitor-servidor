<?php

// "namespace" es la "dirección" de la clase dentro del proyecto.
// Tiene que coincidir con la carpeta: app/Services -> App\Services
namespace App\Services;

// Un service es una clase PHP común: acá vive TODA la lógica de leer el sistema.
// El controlador, la vista y la API solo la usan, no saben cómo se obtienen los datos.
class ServerMetrics
{
    // Método público: es lo único que se llama desde afuera.
    // Devuelve un array (lista clave => valor) con todos los datos del servidor.
    public function snapshot(): array
    {
        // PHP_OS_FAMILY dice en qué sistema corre PHP: 'Windows', 'Linux', etc.
        // Según eso, leemos los datos de una forma o de otra.
        // Las dos funciones devuelven las MISMAS claves, así el resto del código no cambia.
        $sys = PHP_OS_FAMILY === 'Windows'
            ? $this->readWindows()
            : $this->readLinux();

        return [
            // Nombre del equipo (función propia de PHP)
            'hostname'   => gethostname(),

            // Sistema operativo y su versión. php_uname('s') = nombre, php_uname('r') = versión
            'os'         => php_uname('s') . ' ' . php_uname('r'),

            // Versión de PHP: es una constante que PHP ya trae
            'php'        => PHP_VERSION,

            // Disco: lo calcula el método disk() de más abajo
            'disk'       => $this->disk(),

            // Memoria: la calcula memory() a partir de lo que leímos del sistema
            'memory'     => $this->memory($sys),

            // "??" significa: si lo de la izquierda no existe o es null, usá lo de la derecha.
            // Así, si un dato no se pudo leer, queda en null y la app no se rompe.
            'cpu_pct'    => $sys['cpu_pct'] ?? null,
            'uptime_s'   => $sys['uptime_s'] ?? null,
            'cpu_temp_c' => $sys['temp_c'] ?? null,

            // Fecha y hora de esta lectura, en formato estándar ISO 8601
            'at'         => now()->toIso8601String(),
        ];
    }

    // "private" = solo se puede usar dentro de esta clase.
    private function disk(): array
    {
        // base_path() es la carpeta del proyecto: medimos el disco donde está guardado
        $path  = base_path();
        $total = disk_total_space($path); // tamaño total en bytes
        $free  = disk_free_space($path);  // espacio libre en bytes

        return [
            // 1e9 = 1.000.000.000 bytes = 1 GB. round(..., 1) deja un decimal
            'total_gb' => round($total / 1e9, 1),
            'free_gb'  => round($free / 1e9, 1),
            // Porcentaje usado = (total - libre) / total * 100
            'used_pct' => round(100 * ($total - $free) / $total, 1),
        ];
    }

    // "?array" = puede recibir un array o null (si no se pudo leer el sistema)
    private function memory(?array $sys): ?array
    {
        // Si no tenemos el dato de memoria total, devolvemos null ("No disponible")
        if (empty($sys['mem_total_kb'])) {
            return null;
        }

        $total = $sys['mem_total_kb']; // en kilobytes
        $free  = $sys['mem_free_kb'];  // en kilobytes

        return [
            // intdiv = división entera. 1024 KB = 1 MB
            'total_mb'     => intdiv($total, 1024),
            'available_mb' => intdiv($free, 1024),
            'used_pct'     => round(100 * ($total - $free) / $total, 1),
        ];
    }

    // ---------- LINUX: lee archivos de texto que genera el sistema ----------
    private function readLinux(): ?array
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            return null;
        }

        // /proc/meminfo tiene líneas como "MemTotal:       16314204 kB"
        // file() devuelve el archivo como un array, una línea por posición.
        $mem = [];
        foreach (file('/proc/meminfo') as $linea) {
            // explode corta la línea en el ":" -> ["MemTotal", "       16314204 kB"]
            [$clave, $valor] = explode(':', $linea);
            // (int) convierte "   16314204 kB" en el número 16314204
            $mem[$clave] = (int) $valor;
        }

        // /proc/uptime tiene dos números: "12345.67 98765.43". El primero son los segundos encendido.
        $uptime = (int) explode(' ', file_get_contents('/proc/uptime'))[0];

        // La temperatura viene en milésimas de grado: 45000 = 45 °C
        $temp = null;
        $archivo = '/sys/class/thermal/thermal_zone0/temp';
        // is_readable evita un error si el archivo no existe (por ejemplo, en una máquina virtual)
        if (is_readable($archivo)) {
            $temp = round((int) file_get_contents($archivo) / 1000, 1);
        }

        // Devolvemos las MISMAS claves que readWindows()
        return [
            'mem_total_kb' => $mem['MemTotal'] ?? null,
            // MemAvailable (no MemFree): es la memoria que realmente puede usar un programa nuevo
            'mem_free_kb'  => $mem['MemAvailable'] ?? null,
            'cpu_pct'      => $this->cpuLinux(),
            'uptime_s'     => $uptime,
            'temp_c'       => $temp,
        ];
    }

    // Uso de CPU en Linux. La primera línea de /proc/stat es algo como:
    // "cpu  4705 356 584 3699 23 0 23 0 0 0"
    // Son tiempos ACUMULADOS desde que se encendió el equipo. La 4ª columna es "idle" (ocioso)
    // y la 5ª "iowait" (esperando al disco). Leemos dos veces y comparamos la diferencia.
    private function cpuLinux(): float
    {
        // Función anónima (sin nombre) guardada en una variable, para usarla dos veces
        $leer = function (): array {
            // preg_split separa por espacios; array_map('intval', ...) convierte cada parte en número
            $v = array_map('intval', preg_split('/\s+/', trim(file('/proc/stat')[0])));
            array_shift($v);                       // saca el primer elemento (la palabra "cpu")
            return [array_sum($v), $v[3] + $v[4]]; // [tiempo total, tiempo ocioso]
        };

        [$total1, $ocioso1] = $leer();
        usleep(200000);                            // espera 200.000 microsegundos = 0,2 segundos
        [$total2, $ocioso2] = $leer();

        $total = $total2 - $total1;                // cuánto tiempo pasó en total
        // % usado = 100 * (1 - parte del tiempo que estuvo ocioso)
        return $total > 0 ? round(100 * (1 - ($ocioso2 - $ocioso1) / $total), 1) : 0.0;
    }
}