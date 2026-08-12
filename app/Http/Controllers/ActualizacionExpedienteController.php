<?php

namespace App\Http\Controllers;

use App\Models\ActualizacionExpediente;
use App\Models\Expediente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActualizacionExpedienteController extends Controller
{
    public function index(Request $request, Expediente $expediente): View
    {
        $this->autorizarPropioCliente($request, $expediente);

        $expediente->load('actualizaciones.usuario');

        return view('expedientes.actualizaciones', compact('expediente'));
    }

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

    public function update(Request $request, ActualizacionExpediente $actualizacion): RedirectResponse
    {
        $data = $request->validate([
            'texto' => 'required|string|max:2000',
        ]);

        $actualizacion->update($data);

        return back()->with('success', 'Actualización editada.');
    }

    public function destroy(ActualizacionExpediente $actualizacion): RedirectResponse
    {
        $actualizacion->delete();

        return back()->with('success', 'Actualización eliminada.');
    }
}
