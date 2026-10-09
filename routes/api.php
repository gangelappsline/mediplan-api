<?php

use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas públicas de autenticación
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register'])->name('api.register');
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

/*
|--------------------------------------------------------------------------
| Rutas protegidas (requieren token de Passport)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');

    /*
    |----------------------------------------------------------------------
    | Ejemplo de rutas protegidas por rol (puedes usarlas como referencia):
    |
    | Route::get('/admin/dashboard', fn () => ['message' => 'Hola, administrador.'])
    |     ->middleware('role:admin');
    |
    | Route::get('/business/panel', fn () => ['message' => 'Hola, negocio.'])
    |     ->middleware('role:business');
    |
    | También se aceptan los nombres en español y varios roles (con que
    | tenga uno basta): ->middleware('role:cliente,negocio').
    |----------------------------------------------------------------------
    */
});
