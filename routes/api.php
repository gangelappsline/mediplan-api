<?php

use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Business;
use App\Http\Controllers\Api\Client;
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
    | Panel del negocio
    |----------------------------------------------------------------------
    */
    Route::middleware('role:business')
        ->prefix('business')
        ->name('api.business.')
        ->group(function () {
            Route::get('/dashboard', [Business\DashboardController::class, 'index'])
                ->name('dashboard');

            // Perfil y configuración del negocio
            Route::get('/profile', [Business\ProfileController::class, 'show'])->name('profile.show');
            Route::put('/profile', [Business\ProfileController::class, 'update'])->name('profile.update');
            Route::get('/settings', [Business\ProfileController::class, 'settings'])->name('settings.show');
            Route::put('/settings', [Business\ProfileController::class, 'updateSettings'])->name('settings.update');

            // Directorio de clientes
            Route::apiResource('/clients', Business\ClientController::class);

            // Embudo de leads
            Route::patch('/leads/{lead}/status', [Business\LeadController::class, 'updateStatus'])
                ->name('leads.status');
            Route::post('/leads/{lead}/convert', [Business\LeadController::class, 'convert'])
                ->name('leads.convert');
            Route::apiResource('/leads', Business\LeadController::class);

            // Agenda
            Route::get('/appointments/agenda', [Business\AppointmentController::class, 'agenda'])
                ->name('appointments.agenda');
            Route::patch('/appointments/{appointment}/status', [Business\AppointmentController::class, 'updateStatus'])
                ->name('appointments.status');
            Route::apiResource('/appointments', Business\AppointmentController::class);
        });

    /*
    |----------------------------------------------------------------------
    | Panel del cliente
    |----------------------------------------------------------------------
    */
    Route::middleware('role:client')
        ->prefix('client')
        ->name('api.client.')
        ->group(function () {
            Route::get('/dashboard', [Client\DashboardController::class, 'index'])->name('dashboard');

            Route::get('/appointments', [Client\AppointmentController::class, 'index'])
                ->name('appointments.index');
            Route::get('/appointments/{appointment}', [Client\AppointmentController::class, 'show'])
                ->name('appointments.show');
            Route::patch('/appointments/{appointment}/cancel', [Client\AppointmentController::class, 'cancel'])
                ->name('appointments.cancel');
        });

    /*
    |----------------------------------------------------------------------
    | Panel de administración
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('api.admin.')
        ->group(function () {
            Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

            Route::get('/roles', [Admin\RoleController::class, 'index'])->name('roles.index');

            // Usuarios de la plataforma
            Route::patch('/users/{user}/status', [Admin\UserController::class, 'updateStatus'])
                ->name('users.status');
            Route::put('/users/{user}/roles', [Admin\UserController::class, 'updateRoles'])
                ->name('users.roles');
            Route::apiResource('/users', Admin\UserController::class);

            // Negocios
            Route::patch('/businesses/{business}/status', [Admin\BusinessController::class, 'updateStatus'])
                ->name('businesses.status');
            Route::apiResource('/businesses', Admin\BusinessController::class)
                ->except('store');

            // Supervisión de leads
            Route::get('/leads', [Admin\LeadController::class, 'index'])->name('leads.index');
        });
});
