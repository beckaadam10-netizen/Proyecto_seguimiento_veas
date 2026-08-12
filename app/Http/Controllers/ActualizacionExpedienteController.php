<?php

namespace App\Http\Controllers;

use App\Models\ActualizacionExpediente;
use App\Models\Expediente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActualizacionExpedienteController extends Controller
{
    public function store(Request $request, Expediente $expediente): RedirectResponse
    {
        $data = $request->validate([
            'texto' => 'required|string|max:2000',
        ]);

        $expediente->actualizaciones()->create([
            'usuario_id' => auth()->id(),
            'texto'      => $data['texto'],
        ]);

        return back()->with('success', 'Actualización agregada.');
    }

    public function destroy(ActualizacionExpediente $actualizacion): RedirectResponse
    {
        $expediente = $actualizacion->expediente;
        $actualizacion->delete();

        return redirect()
            ->route('expedientes.show', $expediente)
            ->with('success', 'Actualización eliminada.');
    }
}
