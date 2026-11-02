{{-- Esto es un comentario de Blade: no llega al navegador --}}
{{-- Los datos vienen del controlador en la variable $data --}}
<!DOCTYPE html>
<html lang="es">
<head>
    {{-- UTF-8 permite mostrar tildes y la ñ --}}
    <meta charset="UTF-8">
    {{-- Hace que la página se adapte al ancho del celular --}}
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monitor del servidor</title>
    <style>
        /* Estilo general de la página */
        body {
            font-family: system-ui, sans-serif;
            background: #f4f5f7;
            margin: 0;
            padding: 24px;
        }
        h1 { margin-top: 0; }
        /* Grilla: arma tantas columnas de al menos 220px como entren */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }
        /* Cada tarjeta blanca con bordes redondeados y sombra suave */
        .card {
            background: #fff;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,.1);
        }
        .label { color: #666; font-size: 14px; }                      /* título chico de la tarjeta */
        .value { font-size: 24px; font-weight: 600; margin-top: 4px; } /* número grande */
    </style>
</head>
<body>
    <h1>Monitor del servidor</h1>

    {{-- {{ }} imprime un valor de PHP. El id="at" lo usa el script para actualizarlo --}}
    <p>Última lectura: <span id="at">{{ $data['at'] }}</span></p>

    <div class="grid">
        {{-- Estos tres datos no cambian, por eso no tienen id --}}
        <div class="card">
            <div class="label">Nombre del equipo</div>
            <div class="value">{{ $data['hostname'] }}</div>
        </div>
        <div class="card">
            <div class="label">Sistema operativo</div>
            <div class="value">{{ $data['os'] }}</div>
        </div>
        <div class="card">
            <div class="label">Versión de PHP</div>
            <div class="value">{{ $data['php'] }}</div>
        </div>

        {{-- Estos cambian: cada uno tiene un id para que el script lo encuentre --}}
        <div class="card">
            <div class="label">Disco usado (%)</div>
            <div class="value" id="disk">{{ $data['disk']['used_pct'] }}</div>
        </div>
        <div class="card">
            <div class="label">Memoria usada (%)</div>
            {{-- ?? muestra "No disponible" si el dato es null --}}
            <div class="value" id="memory">{{ $data['memory']['used_pct'] ?? 'No disponible' }}</div>
        </div>
        <div class="card">
            <div class="label">Uso de CPU (%)</div>
            <div class="value" id="cpu">{{ $data['cpu_pct'] ?? 'No disponible' }}</div>
        </div>
        <div class="card">
            <div class="label">Temperatura CPU (°C)</div>
            <div class="value" id="temp">{{ $data['cpu_temp_c'] ?? 'No disponible' }}</div>
        </div>
        <div class="card">
            <div class="label">Encendido hace (segundos)</div>
            <div class="value" id="uptime">{{ $data['uptime_s'] ?? 'No disponible' }}</div>
        </div>
    </div>

    {{-- Paso 5: polling. El navegador le pide datos nuevos a la API cada 5 segundos --}}
    <script>
        // "async" permite usar "await" adentro: esperar una respuesta sin congelar la página
        async function actualizar() {
            // fetch hace un pedido GET a nuestra API y espera la respuesta
            const res = await fetch('/api/metrics');

            // Si la respuesta no es correcta (por ejemplo, error 429 por el throttle), no hacemos nada
            if (!res.ok) return;

            // Convierte el texto JSON de la respuesta en un objeto de JavaScript
            const d = await res.json();

            // Busca cada elemento por su id y le cambia el texto por el valor nuevo.
            // "?." evita un error si memory es null; "??" pone "No disponible" si el valor es null
            document.getElementById('at').textContent     = d.at;
            document.getElementById('disk').textContent   = d.disk.used_pct;
            document.getElementById('memory').textContent = d.memory?.used_pct ?? 'No disponible';
            document.getElementById('cpu').textContent    = d.cpu_pct ?? 'No disponible';
            document.getElementById('temp').textContent   = d.cpu_temp_c ?? 'No disponible';
            document.getElementById('uptime').textContent = d.uptime_s ?? 'No disponible';
        }

        // Ejecuta actualizar() cada 5000 milisegundos (5 segundos)
        setInterval(actualizar, 5000);
    </script>
</body>
</html>