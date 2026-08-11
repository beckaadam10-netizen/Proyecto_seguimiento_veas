<?php

namespace App\Http\Controllers;

use App\Models\TipoDocumento;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TipoDocumentoController extends Controller
{
    public function index(): View
    {
        $tipos = TipoDocumento::orderBy('nombre')->paginate(20);

        return view('parametros.tipos-documento.index', compact('tipos'));
    }

    public function create(): View
    {
        return view('parametros.tipos-documento.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100|unique:tipos_documento,nombre',
            'descripcion' => 'nullable|string',
        ]);

        $tipo = TipoDocumento::create($data);

        if ($request->wantsJson()) {
            return response()->json($tipo);
        }

        return redirect()
            ->route('parametros.index', ['abrir' => 'documento'])
            ->with('success', 'Tipo de documento creado correctamente.');
    }

    public function edit(TipoDocumento $tipo_documento): View
    {
        return view('parametros.tipos-documento.edit', ['tipo' => $tipo_documento]);
    }

    public function update(Request $request, TipoDocumento $tipo_documento): RedirectResponse
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100|unique:tipos_documento,nombre,' . $tipo_documento->id,
            'descripcion' => 'nullable|string',
            'activo'      => 'boolean',
        ]);

        $tipo_documento->update($data);

        return redirect()
            ->route('parametros.index', ['abrir' => 'documento'])
            ->with('success', 'Tipo de documento actualizado correctamente.');
    }

    public function destroy(TipoDocumento $tipo_documento): RedirectResponse
    {
        $tipo_documento->delete();

        return redirect()
            ->route('parametros.index', ['abrir' => 'documento'])
            ->with('success', 'Tipo de documento eliminado correctamente.');
    }
}
