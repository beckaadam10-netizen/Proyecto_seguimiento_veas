<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Expediente;
use App\Models\Tramite;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
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
            'expedientes' => fn ($q) => $q->orderByDesc('created_at')->with(['gastos', 'cobros']),
            'tramites'    => fn ($q) => $q->orderByDesc('created_at')->with(['gastos', 'cobros']),
        ]);

        $saldoPendiente = $cliente->expedientes->sum('saldo_pendiente') + $cliente->tramites->sum('saldo_pendiente');

        return view('clientes.show', compact('cliente', 'saldoPendiente'));
    }

    // Estado de cuenta del cliente: resumen de saldo pendiente por cada expediente/trámite,
    // para entregarle al cliente. Se genera al vuelo, igual que los comprobantes de cobro.
    public function estadoCuentaPdf(Cliente $cliente): Response
    {
        $cliente->load([
            'expedientes.gastos.cobros', 'expedientes.cobros',
            'tramites.gastos.cobros', 'tramites.cobros',
        ]);

        $registros = $cliente->expedientes
            ->map(fn (Expediente $e) => (object) [
                'codigo_display'  => $e->numero,
                'titulo_display'  => $e->caratula,
                'total_gastos'    => $e->total_gastos,
                'total_cobrado'   => $e->total_cobrado,
                'saldo_pendiente' => $e->saldo_pendiente,
                'gastos'          => $e->gastos,
            ])
            ->concat($cliente->tramites->map(fn (Tramite $t) => (object) [
                'codigo_display'  => $t->codigo,
                'titulo_display'  => $t->nombre,
                'total_gastos'    => $t->total_gastos,
                'total_cobrado'   => $t->total_cobrado,
                'saldo_pendiente' => $t->saldo_pendiente,
                'gastos'          => $t->gastos,
            ]));

        abort_if($registros->isEmpty(), 404);

        $pdf = Pdf::loadView('clientes.estado-cuenta-pdf', [
            'cliente'        => $cliente,
            'registros'      => $registros,
            'totalPendiente' => (float) $registros->sum('saldo_pendiente'),
        ])->setPaper('a4');

        return $pdf->stream("estado-cuenta-{$cliente->id}.pdf");
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
