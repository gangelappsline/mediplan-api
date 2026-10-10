<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Formatea respuestas paginadas con el sobre { message, data, meta } de la API.
 */
trait RespondsWithPaginatedResources
{
    /**
     * @param  LengthAwarePaginator<Model>  $paginator
     * @param  class-string<JsonResource>  $resourceClass
     */
    protected function paginatedJson(
        LengthAwarePaginator $paginator,
        string $resourceClass,
        string $message,
        int $status = 200,
    ): JsonResponse {
        return response()->json([
            'message' => $message,
            'data' => $resourceClass::collection($paginator->getCollection())->resolve(request()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
        ], $status);
    }

    /**
     * Límite de elementos por página aceptado en los query strings.
     */
    protected function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        $perPage = (int) $request->query('per_page', $default);

        if ($perPage < 1) {
            $perPage = $default;
        }

        return min($perPage, $max);
    }
}
