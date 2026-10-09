<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * Usage: Route::get(...)->middleware(['auth:api', 'role:admin']);
     * Multiple roles (any of them): ->middleware('role:client,business').
     * Role names may be given in English (client, business, admin)
     * or Spanish (cliente, negocio, administrador).
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        if (! $user->hasAnyRole($roles)) {
            return response()->json([
                'message' => 'No tienes permiso para realizar esta acción.',
            ], 403);
        }

        return $next($request);
    }
}
