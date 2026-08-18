<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Expediente;
use App\Models\Tramite;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

// Consulta pública (sin login) para que un cliente vea el estado de su trámite
// o expediente ingresando su nombre y DNI, al estilo "rastree su envío". Nunca
// expone datos de otros clientes: el DNI es la clave real de búsqueda (único),
// el nombre es solo un segundo factor de verificación.
class RastreoController extends Controller
{
    // Cuando el link trae nombre y DNI (ej. el que se manda por WhatsApp desde el
    // reporte de pasantes), busca directo, sin que el cliente tenga que volver a
    // tipearlos.
    public function index(Request $request): View
    {
        if ($request->filled('nombre') && $request->filled('dni')) {
            return $this->buscar($request);
        }

        return view('welcome');
    }

    public function buscar(Request $request): View
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'dni'    => ['required', 'string', 'max:50'],
        ]);

        $mensajeError = 'No encontramos ningún trámite ni expediente con esos datos. Verificá el nombre y el DNI, o contactá al estudio.';

        $cliente = Cliente::activos()->where('dni', trim($request->dni))->first();

        if (! $cliente || ! $this->nombreCoincide($request->nombre, $cliente->nombre_completo)) {
            return view('welcome', ['error' => $mensajeError]);
        }

        $registros = $this->registrosDelCliente($cliente);

        if ($registros->isEmpty()) {
            return view('welcome', ['error' => 'Ese cliente todavía no tiene trámites ni expedientes registrados.']);
        }

        $claveSeleccionada = $request->input('registro');
        $registro = $registros->first(fn ($r) => $claveSeleccionada === "{$r->tipo}-{$r->id}") ?? $registros->first();

        $registro->modelo->load(['gastos.cobros', 'gastos.seguimiento', 'seguimientos.tipoActuacion', 'seguimientos.usuario']);

        if ($registro->modelo instanceof Expediente) {
            $registro->modelo->load('actualizaciones');
        }

        $etapas = $registro->tipo === 'tramite'
            ? $this->construirLineaDeTiempoTramite($registro->modelo)
            : $this->construirLineaDeTiempoExpediente($registro->modelo);

        return view('welcome', [
            'cliente'       => $cliente,
            'registros'     => $registros,
            'registro'      => $registro,
            'etapas'        => $etapas,
            'pendientesPorActuacion' => $this->pendientesPorActuacion($registro->modelo),
            'nombreBuscado' => $request->nombre,
            'dniBuscado'    => $request->dni,
        ]);
    }

    // Junta trámites y expedientes del cliente en una sola lista, para que el
    // selector y el resto de la vista no tengan que distinguir de dónde vino cada uno.
    private function registrosDelCliente(Cliente $cliente): Collection
    {
        $tramites = Tramite::where('cliente_id', $cliente->id)
            ->orderByDesc('fecha_inicio')
            ->get()
            ->map(fn (Tramite $t) => (object) [
                'tipo'        => 'tramite',
                'id'          => $t->id,
                'codigo'      => $t->codigo,
                'titulo'      => $t->nombre,
                'fecha_orden' => $t->fecha_inicio,
                'modelo'      => $t,
            ]);

        $expedientes = Expediente::where('cliente_id', $cliente->id)
            ->orderByDesc('fecha_recepcion')
            ->get()
            ->map(fn (Expediente $e) => (object) [
                'tipo'        => 'expediente',
                'id'          => $e->id,
                'codigo'      => $e->numero,
                'titulo'      => $e->caratula,
                'fecha_orden' => $e->fecha_recepcion,
                'modelo'      => $e,
            ]);

        return $tramites->concat($expedientes)->sortByDesc('fecha_orden')->values();
    }

    private function nombreCoincide(string $nombreIngresado, string $nombreReal): bool
    {
        $normalizar = fn (string $t) => trim(preg_replace('/\s+/', ' ', mb_strtolower($t)));

        return $normalizar($nombreIngresado) === $normalizar($nombreReal);
    }

    // Arma la línea de tiempo a partir del historial real de "Cambio de estado"
    // (guardado como Seguimiento cada vez que se usa el botón de cambiar estado),
    // más las etapas futuras del flujo normal que todavía no se alcanzaron.
    private function construirLineaDeTiempoTramite(Tramite $tramite): array
    {
        $labels = Tramite::estadosLabels();
        $flujo  = Tramite::FLUJO_ESTADOS;

        // Se ordena por created_at (no por fecha_actuacion) porque cambiarEstado() siempre
        // graba fecha_actuacion = today(), sin hora: si hay dos cambios el mismo día, la
        // única forma de saber cuál pasó primero es por el instante real de creación.
        $cambios = $tramite->seguimientos
            ->filter(fn ($s) => $this->esMarcadorDeCambioDeEstado($s))
            ->sortBy('created_at')
            ->values();

        $etapas = [[
            'estado'        => 'iniciado',
            'label'         => $labels['iniciado'],
            'fecha'         => $tramite->fecha_inicio,
            'responsable'   => $tramite->responsable?->nombre ?? 'Estudio Jurídico',
            'descripcion'   => 'El trámite fue registrado en el sistema.',
            'observaciones' => null,
            'completada'    => true,
            'actual'        => $tramite->estado === 'iniciado',
        ]];

        foreach ($cambios as $cambio) {
            preg_match('/→\s*(.+)$/u', $cambio->titulo, $coincidencia);
            $estadoDestinoLabel = $coincidencia[1] ?? null;
            $estadoDestino = $estadoDestinoLabel ? (array_search($estadoDestinoLabel, $labels, true) ?: null) : null;

            $etapas[] = [
                'estado'        => $estadoDestino,
                'label'         => $estadoDestinoLabel ?? $cambio->titulo,
                'fecha'         => $cambio->fecha_actuacion,
                'responsable'   => $cambio->usuario?->name ?? 'Estudio Jurídico',
                'descripcion'   => $cambio->descripcion,
                'observaciones' => $cambio->observaciones,
                'completada'    => true,
                'actual'        => $estadoDestino === $tramite->estado,
            ];
        }

        $posicionActual = array_search($tramite->estado, $flujo, true);
        if ($posicionActual !== false) {
            foreach (array_slice($flujo, $posicionActual + 1) as $estado) {
                $etapas[] = [
                    'estado'        => $estado,
                    'label'         => $labels[$estado],
                    'fecha'         => null,
                    'responsable'   => null,
                    'descripcion'   => null,
                    'observaciones' => null,
                    'completada'    => false,
                    'actual'        => false,
                ];
            }
        }

        return $this->conGastosYDocumentosPorEtapa($etapas, $tramite);
    }

    // Los expedientes no tienen un flujo fijo de estados (pueden pasar de "activo" a
    // "ganado", "perdido", "archivado", etc. sin un orden predefinido), así que acá
    // solo mostramos el historial real de cambios, sin inventar etapas futuras.
    private function construirLineaDeTiempoExpediente(Expediente $expediente): array
    {
        // Se ordena por created_at (no por fecha_actuacion) porque cambiarEstado() siempre
        // graba fecha_actuacion = today(), sin hora: si hay dos cambios el mismo día, la
        // única forma de saber cuál pasó primero es por el instante real de creación.
        $cambios = $expediente->seguimientos
            ->filter(fn ($s) => $this->esMarcadorDeCambioDeEstado($s))
            ->sortBy('created_at')
            ->values();

        $etapas = [[
            'estado'        => 'Activo',
            'label'         => 'Recibido',
            'fecha'         => $expediente->fecha_recepcion,
            'responsable'   => $expediente->abogado?->nombre ?? 'Estudio Jurídico',
            'descripcion'   => 'El expediente fue registrado en el sistema.',
            'observaciones' => null,
            'completada'    => true,
            'actual'        => $cambios->isEmpty(),
        ]];

        foreach ($cambios as $cambio) {
            preg_match('/→\s*(.+)$/u', $cambio->titulo, $coincidencia);
            $estadoDestinoLabel = $coincidencia[1] ?? null;

            $etapas[] = [
                'estado'        => $estadoDestinoLabel,
                'label'         => $estadoDestinoLabel ?? $cambio->titulo,
                'fecha'         => $cambio->fecha_actuacion,
                'responsable'   => $cambio->usuario?->name ?? 'Estudio Jurídico',
                'descripcion'   => $cambio->descripcion,
                'observaciones' => $cambio->observaciones,
                'completada'    => true,
                'actual'        => $estadoDestinoLabel === $expediente->estado_label,
            ];
        }

        return $this->conGastosYDocumentosPorEtapa($etapas, $expediente);
    }

    // Un seguimiento solo cuenta como marcador de una etapa nueva (y por lo tanto se
    // muestra como su propia etapa en la línea de tiempo) si, además de tener la
    // categoría "Cambio de estado", su título sigue el patrón "Cambio de estado: X → Y"
    // que genera automáticamente el botón de cambiar estado. Esa misma categoría también
    // está disponible para cargar una actuación común desde el formulario normal — si
    // alguien la usa así, no debe "robarse" su propia etapa: tiene que aparecer como un
    // seguimiento más, anidado dentro de la etapa vigente.
    private function esMarcadorDeCambioDeEstado($seguimiento): bool
    {
        return $seguimiento->tipoActuacion?->nombre === 'Cambio de estado'
            && (bool) preg_match('/^Cambio de estado:\s*.+\s*→\s*.+$/u', $seguimiento->titulo);
    }

    // Cuánto debe el cliente, agrupado por la actuación que generó cada gasto.
    // Los cobros "total" o "abono" (a diferencia de los cobros por ítem) no quedan
    // vinculados a un gasto puntual, así que la suma de "pendiente" por gasto puede
    // no coincidir con el saldo real del trámite/expediente. Para que el desglose
    // siempre sume exactamente lo que el cliente debe, se reescala proporcionalmente.
    private function pendientesPorActuacion(Tramite|Expediente $registro): \Illuminate\Support\Collection
    {
        $porActuacion = $registro->gastos
            ->where('cubierto', false)
            ->groupBy(fn ($g) => $g->seguimiento?->titulo ?? 'Otros gastos')
            ->map(fn ($grupo) => $grupo->sum(fn ($g) => max(0, (float) $g->monto - $g->total_cobrado)));

        $sumaBruta = $porActuacion->sum();
        $saldoReal = (float) $registro->saldo_pendiente;

        if ($sumaBruta > 0 && round($sumaBruta, 2) !== round($saldoReal, 2)) {
            $factor = $saldoReal / $sumaBruta;
            $porActuacion = $porActuacion->map(fn ($monto) => $monto * $factor);
        }

        return $porActuacion;
    }

    // Reparte los gastos y documentos del trámite/expediente entre las etapas ya
    // completadas, según en qué rango de fechas (entre una etapa y la siguiente) caiga cada uno.
    private function conGastosYDocumentosPorEtapa(array $etapas, Tramite|Expediente $registro): array
    {
        // Las actualizaciones (notas de avance) solo existen para expedientes.
        $actualizaciones = $registro instanceof Expediente ? $registro->actualizaciones : collect();

        foreach ($etapas as $i => &$etapa) {
            if (! $etapa['completada']) {
                $etapa['gastos'] = collect();
                $etapa['documentos'] = collect();
                $etapa['actualizaciones'] = collect();
                continue;
            }

            $desde = $etapa['fecha'];
            $hasta = $etapas[$i + 1]['fecha'] ?? null;

            $etapa['gastos'] = $registro->gastos->filter(
                fn ($g) => $g->fecha->gte($desde) && (! $hasta || $g->fecha->lt($hasta))
            )->values();

            $etapa['documentos'] = $registro->seguimientos->filter(
                fn ($s) => $s->archivo_adjunto && $s->fecha_actuacion->gte($desde) && (! $hasta || $s->fecha_actuacion->lt($hasta))
            )->values();

            // Actualizaciones del caso (notas de avance) que caen en el rango de esta etapa.
            $etapa['actualizaciones'] = $actualizaciones->filter(
                fn ($a) => $a->created_at->gte($desde) && (! $hasta || $a->created_at->lt($hasta))
            )->sortBy('created_at')->values();
        }
        unset($etapa);

        return $etapas;
    }
}
