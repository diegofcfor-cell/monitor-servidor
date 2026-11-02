<?php

use App\Http\Controllers\ServerController; // importa nuestro controlador
use Illuminate\Support\Facades\Route;

// Página de bienvenida de Laravel (ya venía en el archivo)
Route::get('/', function () {
    return view('welcome');
});

// Cuando el navegador pida GET /servidor, se ejecuta el método index de ServerController
Route::get('/servidor', [ServerController::class, 'index']);