<?php

namespace App\Http\Controllers;

use App\Models\EstadoExpediente;
use App\Models\InstitucionPublica;
use App\Models\TipoActuacion;
use App\Models\TipoAudiencia;
use App\Models\TipoDocumento;
use App\Models\TipoGasto;
use App\Models\TipoTramite;
use Illuminate\View\View;

class ParametroController extends Controller
{
    public function index(): View
    {
        $tiposAudiencia        = TipoAudiencia::orderBy('nombre')->get();
        $tiposDocumento        = TipoDocumento::orderBy('nombre')->get();
        $tiposActuacion        = TipoActuacion::orderBy('nombre')->get();
        $tiposTramite          = TipoTramite::orderBy('nombre')->get();
        $institucionesPublicas = InstitucionPublica::orderBy('nombre')->get();
        $tiposGasto             = TipoGasto::orderBy('nombre')->get();
        $estadosExpediente      = EstadoExpediente::orderBy('nombre')->get();

        return view('parametros.index', compact(
            'tiposAudiencia',
            'tiposDocumento',
            'tiposActuacion',
            'tiposTramite',
            'institucionesPublicas',
            'tiposGasto',
            'estadosExpediente'
        ));
    }
}
