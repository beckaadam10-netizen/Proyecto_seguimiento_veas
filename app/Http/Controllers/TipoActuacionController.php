<?php

namespace App\Http\Controllers;

use App\Models\TipoActuacion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TipoActuacionController extends Controller
{
    public function index(): View
    {
        $tipos = TipoActuacion::orderBy('nombre')->paginate(20);

        return view('parametros.tipos-actuacion.index', compact('tipos'));
    }

    public function create(): View
    {
        return view('parametros.tipos-actuacion.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100|unique:tipos_actuacion,nombre',
            'descripcion' => 'nullable|string',
        ]);

        $tipo = TipoActuacion::create($data);

        if ($request->wantsJson()) {
            return response()->json($tipo);
        }

        return redirect()
            ->route('parametros.index', ['abrir' => 'actuacion'])
            ->with('success', 'Tipo de actuación creado correctamente.');
    }

    public function edit(TipoActuacion $tipo_actuacion): View
    {
        return view('parametros.tipos-actuacion.edit', ['tipo' => $tipo_actuacion]);
    }

    public function update(Request $request, TipoActuacion $tipo_actuacion): RedirectResponse
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100|unique:tipos_actuacion,nombre,' . $tipo_actuacion->id,
            'descripcion' => 'nullable|string',
            'activo'      => 'boolean',
        ]);

        $tipo_actuacion->update($data);

        return redirect()
            ->route('parametros.index', ['abrir' => 'actuacion'])
            ->with('success', 'Tipo de actuación actualizado correctamente.');
    }

    public function destroy(TipoActuacion $tipo_actuacion): RedirectResponse
    {
        $tipo_actuacion->delete();

        return redirect()
            ->route('parametros.index', ['abrir' => 'actuacion'])
            ->with('success', 'Tipo de actuación eliminado correctamente.');
    }
}
