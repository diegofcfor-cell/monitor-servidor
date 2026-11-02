<?php

// Los controladores viven en app/Http/Controllers
namespace App\Http\Controllers;

// "use" importa la clase para poder escribir ServerMetrics en vez del nombre completo
use App\Services\ServerMetrics;

// El controlador recibe el pedido del navegador y decide qué responder.
// "extends Controller" = hereda del controlador base que trae Laravel.
class ServerController extends Controller
{
    // Responde a GET /servidor con la página HTML.
    // Laravel ve el tipo "ServerMetrics", crea el objeto y lo pasa solo:
    // eso es la inyección de dependencias.
    public function index(ServerMetrics $metrics)
    {
        // view('servidor', ...) busca resources/views/servidor.blade.php
        // y le pasa los datos con el nombre $data
        return view('servidor', ['data' => $metrics->snapshot()]);
    }

    // Responde a GET /api/metrics con los mismos datos, pero en formato JSON
    public function api(ServerMetrics $metrics)
    {
        // response()->json convierte el array de PHP en texto JSON
        return response()->json($metrics->snapshot());
    }
}
