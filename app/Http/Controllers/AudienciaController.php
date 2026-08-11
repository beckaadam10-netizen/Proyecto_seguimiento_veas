<?php

namespace App\Http\Controllers;

use App\Models\Abogado;
use App\Models\Audiencia;
use App\Models\Expediente;
use App\Models\TipoAudiencia;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AudienciaController extends Controller
{
    public function index(Request $request): View
    {
        $audiencias = Audiencia::with(['expediente.cliente', 'abogado', 'tipoAudiencia'])
            ->when($request->filled('id'), fn($q) => $q->where('id', $request->id))
            ->when($request->estado,       fn($q) => $q->where('estado', $request->estado))
            ->when($request->abogado_id,   fn($q) => $q->porAbogado($request->abogado_id))
            ->when($request->expediente_id,fn($q) => $q->where('expediente_id', $request->expediente_id))
            ->when($request->hoy,          fn($q) => $q->hoy())
            ->when($request->proximas,     fn($q) => $q->proximas(30))
            ->orderBy('fecha_hora')
            ->paginate(20)
            ->withQueryString();

        $estados        = ['programada', 'confirmada', 'realizada', 'suspendida', 'reprogramada', 'cancelada'];
        $abogados       = Abogado::activos()->orderBy('nombre')->get();
        $expedientes    = Expediente::with('cliente')->activos()->orderByDesc('created_at')->get();
        $tiposAudiencia = TipoAudiencia::activos()->orderBy('nombre')->get();

        return view('audiencias.index', compact('audiencias', 'estados', 'abogados', 'expedientes', 'tiposAudiencia'));
    }

    public function create(Request $request): View
    {
        $expediente_id  = $request->expediente_id;
        $expedientes    = Expediente::with('cliente')->activos()->orderByDesc('created_at')->get();
        $abogados       = Abogado::activos()->orderBy('nombre')->get();
        $tiposAudiencia = TipoAudiencia::activos()->orderBy('nombre')->get();
        $estados        = ['programada', 'confirmada', 'realizada', 'suspendida', 'reprogramada', 'cancelada'];

        return view('audiencias.create', compact('expedientes', 'expediente_id', 'abogados', 'tiposAudiencia', 'estados'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'expediente_id'      => 'required|exists:expedientes,id',
            'abogado_id'         => 'nullable|exists:abogados,id',
            'titulo'             => 'required|string|max:200',
            'tipo_audiencia_id'  => 'required|exists:tipos_audiencia,id',
            'fecha_hora'         => 'required|date',
            'modalidad'          => 'required|in:presencial,virtual',
            'lugar'              => 'nullable|string|max:200',
            'sala'               => 'nullable|string|max:50',
            'estado'             => 'required|in:programada,confirmada,realizada,suspendida,reprogramada,cancelada',
            'resultado'          => 'nullable|string',
            'proxima_fecha'      => 'nullable|date|after:fecha_hora',
            'notificado_cliente' => 'boolean',
            'notificar_cliente'  => 'boolean',
        ]);

        if ($data['modalidad'] === 'virtual') {
            $data['lugar'] = null;
            $data['sala']  = null;
        }

        $notificar = $request->boolean('notificar_cliente');
        unset($data['notificar_cliente']);

        $data['notificado_cliente'] = $request->boolean('notificado_cliente') || $notificar;

        $audiencia = Audiencia::create($data);
        $audiencia->load('expediente.cliente');

        $whatsappUrl = null;
        $cliente     = $audiencia->expediente->cliente;

        if ($notificar && $cliente?->telefono_whatsapp) {
            $texto = "Hola {$cliente->nombre_completo}, te confirmamos que se programó una audiencia: \"{$audiencia->titulo}\", "
                . "el {$audiencia->fecha_hora->format('d/m/Y')} a las {$audiencia->fecha_hora->format('H:i')}.";

            if ($audiencia->lugar && $audiencia->sala) {
                $texto .= " Lugar: {$audiencia->lugar} (Sala {$audiencia->sala}).";
            } elseif ($audiencia->lugar) {
                $texto .= " Lugar: {$audiencia->lugar}.";
            } elseif ($audiencia->sala) {
                $texto .= " Sala: {$audiencia->sala}.";
            }

            $whatsappUrl = 'https://wa.me/' . $cliente->telefono_whatsapp . '?text=' . rawurlencode($texto);
        }

        $mensaje = 'Audiencia registrada correctamente.';

        if ($notificar) {
            $mensaje .= $whatsappUrl
                ? ' Si WhatsApp no se abrió automáticamente, hacé clic en el botón.'
                : ' No se pudo notificar: el cliente no tiene teléfono registrado.';
        }

        return redirect()
            ->route('expedientes.show', $audiencia->expediente_id)
            ->with('success', $mensaje)
            ->with('whatsapp_url', $whatsappUrl);
    }

    public function show(Audiencia $audiencia): View
    {
        $audiencia->load(['expediente.cliente', 'abogado']);
        return view('audiencias.show', compact('audiencia'));
    }

    public function edit(Audiencia $audiencia): View
    {
        $expedientes    = Expediente::with('cliente')->activos()->orderByDesc('created_at')->get();
        $abogados       = Abogado::activos()->orderBy('nombre')->get();
        $tiposAudiencia = TipoAudiencia::activos()->orderBy('nombre')->get();
        $estados        = ['programada', 'confirmada', 'realizada', 'suspendida', 'reprogramada', 'cancelada'];

        return view('audiencias.edit', compact('audiencia', 'expedientes', 'abogados', 'tiposAudiencia', 'estados'));
    }

    public function update(Request $request, Audiencia $audiencia): RedirectResponse
    {
        $data = $request->validate([
            'expediente_id'      => 'required|exists:expedientes,id',
            'abogado_id'         => 'nullable|exists:abogados,id',
            'titulo'             => 'required|string|max:200',
            'tipo_audiencia_id'  => 'required|exists:tipos_audiencia,id',
            'fecha_hora'         => 'required|date',
            'duracion_estimada'  => 'required|integer|min:15|max:480',
            'lugar'              => 'nullable|string|max:200',
            'sala'               => 'nullable|string|max:50',
            'estado'             => 'required|in:programada,confirmada,realizada,suspendida,reprogramada,cancelada',
            'resultado'          => 'nullable|string',
            'proxima_fecha'      => 'nullable|date',
            'notificado_cliente' => 'boolean',
        ]);

        $data['notificado_cliente'] = $request->boolean('notificado_cliente');
        $audiencia->update($data);

        return redirect()
            ->route('expedientes.show', $audiencia->expediente_id)
            ->with('success', 'Audiencia actualizada correctamente.');
    }

    public function destroy(Audiencia $audiencia): RedirectResponse
    {
        // Si el expediente de esta audiencia ya no existe (fue eliminado), no hay a
        // dónde volver: mandamos al listado general en vez de a una página que 404.
        $expedienteExiste = Expediente::whereKey($audiencia->expediente_id)->exists();

        $audiencia->delete();

        return $expedienteExiste
            ? redirect()->route('expedientes.show', $audiencia->expediente_id)->with('success', 'Audiencia eliminada.')
            : redirect()->route('audiencias.index')->with('success', 'Audiencia eliminada.');
    }

    // Marcar como realizada rápidamente
    public function marcarRealizada(Audiencia $audiencia): RedirectResponse
    {
        $audiencia->update(['estado' => 'realizada']);

        return back()->with('success', 'Audiencia marcada como realizada.');
    }
}
