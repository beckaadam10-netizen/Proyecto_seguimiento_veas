<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use App\Models\TipoGasto;
use App\Models\Tramite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class GastoController extends Controller
{
    public function create(Request $request): View
    {
        $tramite     = Tramite::findOrFail($request->tramite_id);
        $tiposGasto  = TipoGasto::activos()->orderBy('nombre')->get();

        return view('gastos.create', compact('tramite', 'tiposGasto'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'expediente_id' => $request->filled('expediente_id') ? $request->expediente_id : null,
            'tramite_id'    => $request->filled('tramite_id') ? $request->tramite_id : null,
        ]);

        $validador = Validator::make($request->all(), [
            'expediente_id' => 'nullable|exists:expedientes,id',
            'tramite_id'    => 'nullable|exists:tramites,id',
            'tipo_gasto_id' => 'required|exists:tipos_gasto,id',
            'concepto'      => 'required|string|max:200',
            'monto'         => 'required|numeric|min:0',
            'fecha'         => 'required|date',
        ]);

        $validador->after(function ($validador) use ($request) {
            if ($request->filled('expediente_id') === $request->filled('tramite_id')) {
                $validador->errors()->add('expediente_id', 'Elegí un expediente o un trámite (no ambos).');
            }
        });

        $data = $validador->validate();
        $data['usuario_id'] = auth()->id();

        Gasto::create($data);

        return back()->with('success', 'Gasto registrado correctamente.');
    }

    public function edit(Gasto $gasto): View
    {
        $tiposGasto = TipoGasto::activos()->orderBy('nombre')->get();

        return view('gastos.edit', compact('gasto', 'tiposGasto'));
    }

    public function update(Request $request, Gasto $gasto): RedirectResponse
    {
        $data = $request->validate([
            'tipo_gasto_id' => 'required|exists:tipos_gasto,id',
            'concepto'      => 'required|string|max:200',
            'monto'         => 'required|numeric|min:0',
            'fecha'         => 'required|date',
        ]);

        $gasto->update($data);

        return back()->with('success', 'Gasto actualizado correctamente.');
    }

    public function destroy(Gasto $gasto): RedirectResponse
    {
        $gasto->delete();

        return back()->with('success', 'Gasto eliminado.');
    }
}
