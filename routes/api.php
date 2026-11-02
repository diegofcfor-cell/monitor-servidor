<?php

use App\Http\Controllers\ServerController; // importa nuestro controlador
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Ruta de ejemplo que creó "php artisan install:api" (se puede dejar)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Todo lo de este archivo ya lleva el prefijo /api, así que la URL final es /api/metrics.
// throttle:60,1 = como máximo 60 pedidos por minuto desde la misma dirección
Route::get('/metrics', [ServerController::class, 'api'])
    ->middleware('throttle:60,1');