<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Resuelve el negocio del usuario autenticado y protege sus recursos.
 */
trait ResolvesCurrentBusiness
{
    /**
     * Negocio del usuario autenticado.
     *
     * @throws HttpException cuando el usuario no tiene negocio asociado.
     */
    protected function currentBusiness(Request $request): Business
    {
        /** @var User|null $user */
        $user = $request->user();

        $business = $user?->business;

        if ($business === null) {
            throw new HttpException(
                404,
                'Tu usuario todavía no tiene un negocio asociado.',
            );
        }

        return $business;
    }

    /**
     * Comprueba que el modelo pertenece al negocio indicado.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  TModel|null  $model
     * @return TModel
     *
     * @throws NotFoundHttpException cuando el recurso no pertenece al negocio.
     */
    protected function assertBelongsToBusiness(?object $model, Business $business): object
    {
        if ($model === null || (int) $model->business_id !== (int) $business->getKey()) {
            throw new NotFoundHttpException('El recurso solicitado no existe.');
        }

        return $model;
    }
}
