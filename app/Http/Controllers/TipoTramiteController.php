<?php

namespace App\Http\Controllers;

use App\Models\TipoTramite;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class TipoTramiteController extends Controller
{
    public function create(): View
    {
        return view('parametros.tipos-tramite.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100|unique:tipos_tramite,nombre',
            'descripcion' => 'nullable|string',
        ], [
            'nombre.unique' => 'Ya existe un tipo de trámite con ese nombre.',
        ]);

        $tipo = TipoTramite::create($data);

        if ($request->wantsJson()) {
            return response()->json(['id' => $tipo->id, 'nombre' => $tipo->nombre]);
        }

        return redirect()
            ->route('parametros.index', ['abrir' => 'tramite'])
            ->with('success', 'Tipo de trámite creado correctamente.');
    }

    public function edit(TipoTramite $tipo_tramite): View
    {
        return view('parametros.tipos-tramite.edit', ['tipo' => $tipo_tramite]);
    }

    public function update(Request $request, TipoTramite $tipo_tramite): RedirectResponse
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100|unique:tipos_tramite,nombre,' . $tipo_tramite->id,
            'descripcion' => 'nullable|string',
            'activo'      => 'boolean',
        ]);

        $data['activo'] = $request->boolean('activo');

        $tipo_tramite->update($data);

        return redirect()
            ->route('parametros.index', ['abrir' => 'tramite'])
            ->with('success', 'Tipo de trámite actualizado correctamente.');
    }

    public function destroy(TipoTramite $tipo_tramite): RedirectResponse
    {
        $tipo_tramite->delete();

        return redirect()
            ->route('parametros.index', ['abrir' => 'tramite'])
            ->with('success', 'Tipo de trámite eliminado correctamente.');
    }
}
