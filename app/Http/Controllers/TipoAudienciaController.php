<?php

namespace App\Http\Controllers;

use App\Models\TipoAudiencia;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class TipoAudienciaController extends Controller
{
    public function index(): View
    {
        $tipos = TipoAudiencia::orderBy('nombre')->paginate(20);

        return view('parametros.tipos-audiencia.index', compact('tipos'));
    }

    public function create(): View
    {
        return view('parametros.tipos-audiencia.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100|unique:tipos_audiencia,nombre',
            'descripcion' => 'nullable|string',
        ]);

        $tipo = TipoAudiencia::create($data);

        if ($request->wantsJson()) {
            return response()->json($tipo);
        }

        return redirect()
            ->route('parametros.index', ['abrir' => 'audiencia'])
            ->with('success', 'Tipo de audiencia creado correctamente.');
    }

    public function edit(TipoAudiencia $tipo_audiencia): View
    {
        return view('parametros.tipos-audiencia.edit', ['tipo' => $tipo_audiencia]);
    }

    public function update(Request $request, TipoAudiencia $tipo_audiencia): RedirectResponse
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100|unique:tipos_audiencia,nombre,' . $tipo_audiencia->id,
            'descripcion' => 'nullable|string',
            'activo'      => 'boolean',
        ]);

        $tipo_audiencia->update($data);

        return redirect()
            ->route('parametros.index', ['abrir' => 'audiencia'])
            ->with('success', 'Tipo de audiencia actualizado correctamente.');
    }

    public function destroy(TipoAudiencia $tipo_audiencia): RedirectResponse
    {
        $tipo_audiencia->delete();

        return redirect()
            ->route('parametros.index', ['abrir' => 'audiencia'])
            ->with('success', 'Tipo de audiencia eliminado correctamente.');
    }
}
