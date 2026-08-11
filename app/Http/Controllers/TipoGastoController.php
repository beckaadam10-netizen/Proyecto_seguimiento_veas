<?php

namespace App\Http\Controllers;

use App\Models\TipoGasto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TipoGastoController extends Controller
{
    public function create(): View
    {
        return view('parametros.tipos-gasto.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100|unique:tipos_gasto,nombre',
            'descripcion' => 'nullable|string',
        ]);

        $tipo = TipoGasto::create($data);

        if ($request->wantsJson()) {
            return response()->json($tipo);
        }

        return redirect()
            ->route('parametros.index', ['abrir' => 'gasto'])
            ->with('success', 'Tipo de gasto creado correctamente.');
    }

    public function edit(TipoGasto $tipo_gasto): View
    {
        return view('parametros.tipos-gasto.edit', ['tipo' => $tipo_gasto]);
    }

    public function update(Request $request, TipoGasto $tipo_gasto): RedirectResponse
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100|unique:tipos_gasto,nombre,' . $tipo_gasto->id,
            'descripcion' => 'nullable|string',
            'activo'      => 'boolean',
        ]);

        $data['activo'] = $request->boolean('activo');

        $tipo_gasto->update($data);

        return redirect()
            ->route('parametros.index', ['abrir' => 'gasto'])
            ->with('success', 'Tipo de gasto actualizado correctamente.');
    }

    public function destroy(TipoGasto $tipo_gasto): RedirectResponse
    {
        $tipo_gasto->delete();

        return redirect()
            ->route('parametros.index', ['abrir' => 'gasto'])
            ->with('success', 'Tipo de gasto eliminado correctamente.');
    }
}
