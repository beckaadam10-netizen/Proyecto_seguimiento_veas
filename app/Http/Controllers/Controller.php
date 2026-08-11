<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class Controller
{
    // Un usuario "cliente" solo puede ver sus propios trámites/expedientes,
    // aunque adivine el id de otro en la URL.
    protected function autorizarPropioCliente(Request $request, Model $registro): void
    {
        abort_if(
            $request->user()->esCliente() && $registro->cliente_id !== $request->user()->cliente_id,
            403,
            'No tenés acceso a este registro.'
        );
    }
}
