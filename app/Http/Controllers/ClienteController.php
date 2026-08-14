<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $clientes = Cliente::query()
            ->when($request->filled('id'), fn($q) => $q->where('id', $request->id))
            ->when($request->buscar, fn($q) => $q->buscar($request->buscar))
            ->when($request->tipo,   fn($q) => $q->where('tipo', $request->tipo))
            ->when($request->filled('activo'), fn($q) => $q->where('activo', $request->activo))
            ->withCount('expedientes')
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        if ($request->ajax()) {
            return view('clientes._tabla-clientes', compact('clientes'));
        }

        return view('clientes.index', compact('clientes'));
    }

    public function create(): View
    {
        return view('clientes.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'nombre'       => 'required_if:tipo,persona_fisica|nullable|string|max:100',
            'apellido'     => 'required_if:tipo,persona_fisica|nullable|string|max:100',
            'dni'          => 'required|string|max:20|unique:clientes,dni',
            'email'        => 'nullable|email|max:150',
            'telefono'     => 'nullable|string|max:30',
            'direccion'    => 'nullable|string|max:255',
            'tipo'         => 'required|in:persona_fisica,persona_juridica',
            'razon_social' => 'required_if:tipo,persona_juridica|nullable|string|max:200',
        ]);

        if ($data['tipo'] === 'persona_juridica') {
            $data['nombre'] = $data['apellido'] = null;
        } else {
            $data['razon_social'] = null;
        }

        $cliente = Cliente::create($data);

        if ($request->wantsJson()) {
            return response()->json([
                'id'              => $cliente->id,
                'tipo'            => $cliente->tipo,
                'nombre_completo' => $cliente->nombre_completo,
            ]);
        }

        return redirect()
            ->route('clientes.show', $cliente)
            ->with('success', 'Cliente registrado correctamente.');
    }

    public function show(Cliente $cliente): View
    {
        $cliente->load([
            'expedientes' => fn ($q) => $q->orderByDesc('created_at'),
            'tramites'    => fn ($q) => $q->orderByDesc('created_at'),
        ]);
        return view('clientes.show', compact('cliente'));
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $data = $request->validate([
            'nombre'       => 'required_if:tipo,persona_fisica|nullable|string|max:100',
            'apellido'     => 'required_if:tipo,persona_fisica|nullable|string|max:100',
            'dni'          => 'required|string|max:20|unique:clientes,dni,' . $cliente->id,
            'email'        => 'nullable|email|max:150',
            'telefono'     => 'nullable|string|max:30',
            'direccion'    => 'nullable|string|max:255',
            'tipo'         => 'required|in:persona_fisica,persona_juridica',
            'razon_social' => 'required_if:tipo,persona_juridica|nullable|string|max:200',
            'activo'       => 'boolean',
        ]);

        if ($data['tipo'] === 'persona_juridica') {
            $data['nombre'] = $data['apellido'] = null;
        } else {
            $data['razon_social'] = null;
        }

        $cliente->update($data);

        return redirect()
            ->route('clientes.show', $cliente)
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        if ($cliente->expedientes()->exists() || $cliente->tramites()->exists()) {
            return redirect()
                ->route('clientes.index')
                ->with('error', 'No se puede eliminar: el cliente tiene expedientes o trámites a su nombre.');
        }

        $cliente->delete();

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }
}
