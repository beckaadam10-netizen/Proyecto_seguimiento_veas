<?php

namespace App\Http\Controllers;

use App\Models\InstitucionPublica;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class InstitucionPublicaController extends Controller
{
    public function create(): View
    {
        return view('parametros.instituciones-publicas.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:150|unique:instituciones_publicas,nombre',
            'descripcion' => 'nullable|string',
        ], [
            'nombre.unique' => 'Ya existe una institución pública con ese nombre.',
        ]);

        $institucion = InstitucionPublica::create($data);

        if ($request->wantsJson()) {
            return response()->json(['id' => $institucion->id, 'nombre' => $institucion->nombre]);
        }

        return redirect()
            ->route('parametros.index', ['abrir' => 'institucion'])
            ->with('success', 'Institución pública creada correctamente.');
    }

    public function edit(InstitucionPublica $institucion_publica): View
    {
        return view('parametros.instituciones-publicas.edit', ['institucion' => $institucion_publica]);
    }

    public function update(Request $request, InstitucionPublica $institucion_publica): RedirectResponse
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:150|unique:instituciones_publicas,nombre,' . $institucion_publica->id,
            'descripcion' => 'nullable|string',
            'activo'      => 'boolean',
        ]);

        $data['activo'] = $request->boolean('activo');

        $institucion_publica->update($data);

        return redirect()
            ->route('parametros.index', ['abrir' => 'institucion'])
            ->with('success', 'Institución pública actualizada correctamente.');
    }

    public function destroy(InstitucionPublica $institucion_publica): RedirectResponse
    {
        $institucion_publica->delete();

        return redirect()
            ->route('parametros.index', ['abrir' => 'institucion'])
            ->with('success', 'Institución pública eliminada correctamente.');
    }
}
