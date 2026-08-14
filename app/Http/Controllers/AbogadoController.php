<?php

namespace App\Http\Controllers;

use App\Models\Abogado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AbogadoController extends Controller
{
    public function index(Request $request): View
    {
        $abogados = Abogado::query()
            ->when($request->filled('id'), fn($q) => $q->where('id', $request->id))
            ->when($request->buscar, fn($q) => $q->buscar($request->buscar))
            ->when($request->filled('activo'), fn($q) => $q->where('activo', $request->activo))
            ->withCount(['expedientes', 'audiencias', 'tramites'])
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        if ($request->ajax()) {
            return view('abogados._tabla-abogados', compact('abogados'));
        }

        return view('abogados.index', compact('abogados'));
    }

    public function create(): View
    {
        return view('abogados.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'nombre'       => 'required|string|max:150',
            'matricula'    => 'nullable|string|max:50',
            'telefono'     => 'nullable|string|max:30',
            'email'        => 'nullable|email|max:150',
            'especialidad' => 'nullable|string|max:150',
        ]);

        $abogado = Abogado::create($data);

        if ($request->wantsJson()) {
            return response()->json($abogado);
        }

        return redirect()
            ->route('abogados.show', $abogado)
            ->with('success', 'Abogado registrado correctamente.');
    }

    public function show(Abogado $abogado): View
    {
        $abogado->load([
            'expedientes' => fn($q) => $q->orderByDesc('created_at'),
            'audiencias'  => fn($q) => $q->orderByDesc('fecha_hora'),
            'tramites'    => fn($q) => $q->orderByDesc('created_at'),
        ]);

        return view('abogados.show', compact('abogado'));
    }

    public function edit(Abogado $abogado): View
    {
        return view('abogados.edit', compact('abogado'));
    }

    public function update(Request $request, Abogado $abogado): RedirectResponse
    {
        $data = $request->validate([
            'nombre'       => 'required|string|max:150',
            'matricula'    => 'nullable|string|max:50',
            'telefono'     => 'nullable|string|max:30',
            'email'        => 'nullable|email|max:150',
            'especialidad' => 'nullable|string|max:150',
            'activo'       => 'boolean',
        ]);

        $data['activo'] = $request->boolean('activo');

        $abogado->update($data);

        return redirect()
            ->route('abogados.show', $abogado)
            ->with('success', 'Abogado actualizado correctamente.');
    }

    public function destroy(Abogado $abogado): RedirectResponse
    {
        $abogado->delete();

        return redirect()
            ->route('abogados.index')
            ->with('success', 'Abogado eliminado correctamente.');
    }
}
