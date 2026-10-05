<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Cobro;
use App\Models\Expediente;
use App\Models\Gasto;
use App\Models\Seguimiento;
use App\Models\Tramite;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BitacoraController extends Controller
{
    public function index(Request $request): View
    {
        $registros = Bitacora::with('usuario')
            ->when($request->filled('usuario_id'), fn ($q) => $q->where('usuario_id', $request->usuario_id))
            ->when($request->filled('accion'), fn ($q) => $q->where('accion', $request->accion))
            ->when($request->filled('modelo'), fn ($q) => $q->where('modelo', $request->modelo))
            ->when($request->filled('buscar'), fn ($q) => $q->where('descripcion', 'like', '%' . $request->buscar . '%'))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('created_at', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('created_at', '<=', $request->hasta))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $usuarios = User::orderBy('name')->get(['id', 'name']);

        return view('bitacora.index', compact('registros', 'usuarios'));
    }

    // Limpieza manual, además de la automática diaria (bitacora:limpiar): por si el
    // cron del hosting no está configurado, o para forzarla antes de los 180 días.
    public function limpiar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'dias' => 'required|integer|min:1|max:3650',
        ]);

        $corte     = now()->subDays($data['dias']);
        $borrados  = Bitacora::where('created_at', '<', $corte)->delete();

        return redirect()->route('bitacora.index')
            ->with('success', "Se eliminaron {$borrados} registro(s) anteriores al " . $corte->format('d/m/Y') . ".");
    }

    // Versión web de app:recuperar-gastos-borrados, para cuando no hay acceso por SSH al
    // servidor. Primero se muestra lo que haría (--dry-run) y solo un administrador puede
    // aplicarlo. Usa la bitácora como fuente: hay que correrlo antes de limpiarla.
    // Se puede reparar todo de una vez o un solo expediente/trámite (?caso=expediente-5 /
    // ?caso=tramite-3), elegido de la lista de casos que tienen cobros sin gasto.
    public function recuperarGastos(Request $request): View
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $caso = $this->casoElegido($request);

        Artisan::call('app:recuperar-gastos-borrados', ['--dry-run' => true] + $caso['opciones']);

        return view('bitacora.recuperar-gastos', [
            'salida'   => $this->limpiarSalida(Artisan::output()),
            'aplicado' => session('recuperacion_aplicada'),
            'casos'    => $this->casosAfectados(),
            'caso'     => $caso['clave'],
        ] + $this->datosReparacionManual($caso));
    }

    // Cuando la bitácora no tiene los datos (cobros "que no figuran"), un administrador
    // decide a mano qué era cada cobro sin gasto: el gasto pendiente que en realidad pagó
    // (ej. un gasto que se borró y se volvió a crear), o el gasto borrado que hay que
    // restaurar, con su concepto.
    public function recuperarGastosManual(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $caso = $this->casoElegido($request);
        abort_unless($caso['clave'], 404);

        $datos = $this->datosReparacionManual($caso);
        $cobros = $datos['cobrosSueltos']->keyBy('id');
        $pendientePorGasto = $datos['gastosPendientes']->mapWithKeys(fn (Gasto $g) => [$g->id => $g->pendiente]);
        $seguimientos = $datos['actuaciones']->keyBy('id');

        $request->validate([
            'cobros'                => 'required|array',
            'cobros.*.accion'       => 'nullable|string',
            'cobros.*.concepto'     => 'nullable|string|max:200',
            'cobros.*.seguimiento_id' => 'nullable|integer',
        ]);

        $resultado = [];

        DB::transaction(function () use ($request, $cobros, &$pendientePorGasto, $seguimientos, &$resultado) {
            foreach ($request->input('cobros') as $cobroId => $fila) {
                $cobro  = $cobros->get((int) $cobroId);
                $accion = $fila['accion'] ?? '';

                if (! $cobro || $accion === '') {
                    continue;
                }

                if (preg_match('/^gasto-(\d+)$/', $accion, $m)) {
                    $gastoId = (int) $m[1];
                    $pendiente = $pendientePorGasto[$gastoId] ?? null;

                    if ($pendiente === null || (float) $cobro->monto > $pendiente + 0.004) {
                        throw ValidationException::withMessages([
                            'cobros' => "El cobro #{$cobro->id} (" . number_format($cobro->monto, 2) . ' Bs) es mayor que lo que le falta cobrar al gasto elegido.',
                        ]);
                    }

                    $cobro->update(['gasto_id' => $gastoId]);
                    $pendientePorGasto[$gastoId] = $pendiente - (float) $cobro->monto;
                    $resultado[] = "Cobro #{$cobro->id} (" . number_format($cobro->monto, 2) . " Bs): vinculado al gasto #{$gastoId}.";
                } elseif ($accion === 'restaurar') {
                    $concepto = trim($fila['concepto'] ?? '');

                    if ($concepto === '') {
                        throw ValidationException::withMessages([
                            'cobros' => "Escribí el concepto del gasto a restaurar para el cobro #{$cobro->id}.",
                        ]);
                    }

                    $seguimiento = $seguimientos->get((int) ($fila['seguimiento_id'] ?? 0));

                    // El gasto restaurado es el que se pagó con este cobro: mismo monto, y
                    // su fecha de alta es la del cobro (no hoy), para que no aparezca como
                    // un gasto nuevo en el Reporte de Pasantes. Le pertenece a quien
                    // registró la actuación, si se eligió una.
                    $gasto = Gasto::create([
                        'expediente_id'  => $cobro->expediente_id,
                        'tramite_id'     => $cobro->tramite_id,
                        'seguimiento_id' => $seguimiento?->id,
                        'usuario_id'     => $seguimiento?->usuario_id,
                        'concepto'       => $concepto,
                        'monto'          => $cobro->monto,
                        'fecha'          => $seguimiento?->fecha_actuacion ?? $cobro->fecha,
                    ]);
                    Gasto::whereKey($gasto->id)->update(['created_at' => $cobro->created_at]);

                    $cobro->update(['gasto_id' => $gasto->id]);
                    $resultado[] = "Cobro #{$cobro->id} (" . number_format($cobro->monto, 2) . " Bs): restaurado el gasto #{$gasto->id} \"{$concepto}\".";
                }
            }
        });

        return redirect()->route('bitacora.recuperar-gastos', ['caso' => $caso['clave']])
            ->with('success', $resultado ? 'Reparación manual aplicada.' : 'No se eligió ningún cambio.')
            ->with('recuperacion_aplicada', implode("\n", $resultado));
    }

    private function datosReparacionManual(array $caso): array
    {
        if (! $caso['clave']) {
            return ['cobrosSueltos' => collect(), 'gastosPendientes' => collect(), 'actuaciones' => collect()];
        }

        [$columna, $id] = [array_key_first($caso['opciones']) === '--expediente' ? 'expediente_id' : 'tramite_id', reset($caso['opciones'])];

        $gastosPendientes = Gasto::with(['cobros', 'seguimiento'])->where($columna, $id)->orderBy('fecha')->get()
            ->each(fn (Gasto $g) => $g->pendiente = round((float) $g->monto - $g->total_cobrado, 2))
            ->filter(fn (Gasto $g) => $g->pendiente > 0.004)
            ->values();

        return [
            'cobrosSueltos'    => Cobro::whereNull('gasto_id')->where($columna, $id)->orderBy('id')->get(),
            'gastosPendientes' => $gastosPendientes,
            'actuaciones'      => Seguimiento::where($columna, $id)->orderByDesc('fecha_actuacion')->get(['id', 'titulo', 'fecha_actuacion', 'usuario_id']),
        ];
    }

    public function recuperarGastosAplicar(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $caso = $this->casoElegido($request);

        DB::transaction(fn () => Artisan::call('app:recuperar-gastos-borrados', $caso['opciones']));

        return redirect()->route('bitacora.recuperar-gastos', array_filter(['caso' => $caso['clave']]))
            ->with('success', 'Reparación aplicada.')
            ->with('recuperacion_aplicada', $this->limpiarSalida(Artisan::output()));
    }

    // Expedientes y trámites que tienen cobros sin gasto, con cuánto suman.
    private function casosAfectados(): array
    {
        $casos = [];

        $sueltos = Cobro::whereNull('gasto_id')
            ->selectRaw('expediente_id, tramite_id, SUM(monto) as total, COUNT(*) as cantidad')
            ->groupBy('expediente_id', 'tramite_id')
            ->get();

        foreach ($sueltos as $fila) {
            $etiqueta = $fila->expediente_id
                ? 'Expediente ' . (Expediente::withTrashed()->find($fila->expediente_id)?->numero ?? "#{$fila->expediente_id}")
                : 'Trámite ' . (Tramite::find($fila->tramite_id)?->codigo ?? "#{$fila->tramite_id}");

            $clave = $fila->expediente_id ? "expediente-{$fila->expediente_id}" : "tramite-{$fila->tramite_id}";
            $casos[$clave] = "{$etiqueta} — {$fila->cantidad} cobro(s) sin gasto, " . number_format((float) $fila->total, 2) . ' Bs';
        }

        return $casos;
    }

    private function casoElegido(Request $request): array
    {
        if (preg_match('/^(expediente|tramite)-(\d+)$/', (string) $request->input('caso'), $m)) {
            return ['clave' => $m[0], 'opciones' => ["--{$m[1]}" => $m[2]]];
        }

        return ['clave' => null, 'opciones' => []];
    }

    private function limpiarSalida(string $salida): string
    {
        // Saca los códigos de color de la consola.
        return trim(preg_replace('/\e\[[\d;]*m/', '', $salida));
    }
}
