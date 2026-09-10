<?php

namespace App\Http\Controllers;

use App\Models\Cobro;
use App\Models\Expediente;
use App\Models\Tramite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CobroController extends Controller
{
    public function create(Request $request): View
    {
        $tramite = Tramite::with('gastos.cobros')->findOrFail($request->tramite_id);

        return view('cobros.create', compact('tramite'));
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
            'modo'          => 'required|in:total,abono,item',
            'fecha'         => 'required|date',
            'metodo_pago'   => 'required|in:efectivo,qr',
        ]);

        $validador->after(function ($validador) use ($request) {
            if ($request->filled('expediente_id') === $request->filled('tramite_id')) {
                $validador->errors()->add('expediente_id', 'Elegí un expediente o un trámite (no ambos).');
            }
        });

        $base = $validador->validate();

        $entidad = $base['tramite_id']
            ? Tramite::with('gastos.cobros')->findOrFail($base['tramite_id'])
            : Expediente::with('gastos.cobros')->findOrFail($base['expediente_id']);

        $creados = match ($base['modo']) {
            'total' => $this->cobrarTotal($entidad, $base),
            'item'  => $this->cobrarPorItem($entidad, $request, $base),
            default => $this->cobrarAbono($entidad, $request, $base),
        };

        if ($creados === 0) {
            return back()->withInput()->with('error', 'No se registró ningún cobro. Elegí al menos un ítem, o verificá que haya saldo pendiente.');
        }

        return back()->with('success', 'Cobro registrado correctamente.');
    }

    public function edit(Cobro $cobro): View
    {
        $tramite = Tramite::with('gastos')->findOrFail($cobro->tramite_id);

        return view('cobros.edit', compact('cobro', 'tramite'));
    }

    public function update(Request $request, Cobro $cobro): RedirectResponse
    {
        $entidad = $cobro->tramite_id
            ? Tramite::with(['gastos.cobros', 'cobros'])->findOrFail($cobro->tramite_id)
            : Expediente::with(['gastos.cobros', 'cobros'])->findOrFail($cobro->expediente_id);

        $gastoId = $request->input('gasto_id');
        $gasto   = $gastoId ? $entidad->gastos->firstWhere('id', (int) $gastoId) : null;

        if ($gasto) {
            // Si el cobro está (o pasa a estar) vinculado a un gasto puntual, el tope es
            // lo que falta cobrar de ESE gasto, no el saldo pendiente de todo el trámite/expediente:
            // de lo contrario se puede "tomar prestado" saldo de otros gastos impagos.
            $otrosCobrosDelGasto = $gasto->cobros->where('id', '!=', $cobro->id)->sum('monto');
            $saldoDisponible = (float) $gasto->monto - (float) $otrosCobrosDelGasto;
        } else {
            $saldoDisponible = $entidad->saldo_pendiente + (float) $cobro->monto;
        }

        $data = $request->validate([
            'gasto_id'    => [
                'nullable',
                $cobro->tramite_id
                    ? Rule::exists('gastos', 'id')->where('tramite_id', $cobro->tramite_id)
                    : Rule::exists('gastos', 'id')->where('expediente_id', $cobro->expediente_id),
            ],
            'monto'       => ['required', 'numeric', 'min:0.01', 'max:' . $saldoDisponible],
            'fecha'       => 'required|date',
            'metodo_pago' => 'required|in:efectivo,qr',
        ], [
            'monto.max' => 'El monto no puede superar el saldo pendiente (' . number_format($saldoDisponible, 2) . ' Bs).',
        ]);

        $cobro->update($data);

        return back()->with('success', 'Cobro actualizado correctamente.');
    }

    public function destroy(Cobro $cobro): RedirectResponse
    {
        $cobro->delete();

        return back()->with('success', 'Cobro eliminado.');
    }

    private function claveEntidad(Tramite|Expediente $entidad): array
    {
        return $entidad instanceof Tramite
            ? ['tramite_id' => $entidad->id, 'expediente_id' => null]
            : ['tramite_id' => null, 'expediente_id' => $entidad->id];
    }

    private function cobrarTotal(Tramite|Expediente $entidad, array $base): int
    {
        $monto = $entidad->saldo_pendiente;

        if ($monto <= 0) {
            return 0;
        }

        return $this->distribuirCobro($entidad, $monto, $base);
    }

    private function cobrarAbono(Tramite|Expediente $entidad, Request $request, array $base): int
    {
        $data = $request->validate([
            'monto' => ['required', 'numeric', 'min:0.01', 'max:' . $entidad->saldo_pendiente],
        ], [
            'monto.max' => 'El monto no puede superar el saldo pendiente (' . number_format($entidad->saldo_pendiente, 2) . ' Bs).',
        ]);

        return $this->distribuirCobro($entidad, $data['monto'], $base);
    }

    // Reparte el monto cobrado entre los gastos pendientes, del más antiguo al más nuevo
    // (FIFO), creando un Cobro por gasto que alcanza a cubrir. Así, ya sea que se cobre por
    // ítem, en bloque o en abonos, cada gasto queda vinculado a su propio cobro y su estado
    // "cubierto" refleja la realidad (antes, un cobro total/abono quedaba suelto sin
    // gasto_id y los gastos que cubría seguían apareciendo como pendientes para siempre).
    private function distribuirCobro(Tramite|Expediente $entidad, float $monto, array $base): int
    {
        $restante = $monto;
        $creados  = 0;

        $gastosPendientes = $entidad->gastos
            ->sortBy([['fecha', 'asc'], ['id', 'asc']])
            ->filter(fn ($g) => ((float) $g->monto - $g->total_cobrado) > 0.004);

        foreach ($gastosPendientes as $gasto) {
            if ($restante <= 0.004) {
                break;
            }

            $pendienteGasto = (float) $gasto->monto - $gasto->total_cobrado;
            $aplicar        = round(min($pendienteGasto, $restante), 2);

            Cobro::create(array_merge($this->claveEntidad($entidad), [
                'gasto_id'    => $gasto->id,
                'usuario_id'  => auth()->id(),
                'monto'       => $aplicar,
                'fecha'       => $base['fecha'],
                'metodo_pago' => $base['metodo_pago'],
            ]));

            $restante -= $aplicar;
            $creados++;
        }

        // No debería quedar sobrante (el monto nunca supera el saldo pendiente, que es la
        // suma de lo pendiente por gasto), pero por seguridad se registra como cobro
        // general en vez de perderlo.
        if ($restante > 0.004) {
            Cobro::create(array_merge($this->claveEntidad($entidad), [
                'usuario_id'  => auth()->id(),
                'monto'       => round($restante, 2),
                'fecha'       => $base['fecha'],
                'metodo_pago' => $base['metodo_pago'],
            ]));
            $creados++;
        }

        return $creados;
    }

    private function cobrarPorItem(Tramite|Expediente $entidad, Request $request, array $base): int
    {
        $seleccionados = collect($request->input('gastos_seleccionados', []))->map(fn ($id) => (int) $id);

        $gastos = $entidad->gastos->whereIn('id', $seleccionados);
        $creados = 0;

        foreach ($gastos as $gasto) {
            $pendiente = (float) $gasto->monto - $gasto->total_cobrado;

            if ($pendiente <= 0) {
                continue;
            }

            Cobro::create(array_merge($this->claveEntidad($entidad), [
                'gasto_id'    => $gasto->id,
                'usuario_id'  => auth()->id(),
                'monto'       => $pendiente,
                'fecha'       => $base['fecha'],
                'metodo_pago' => $base['metodo_pago'],
            ]));

            $creados++;
        }

        return $creados;
    }
}
