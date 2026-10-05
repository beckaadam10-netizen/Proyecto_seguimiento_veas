<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\User;
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
    public function recuperarGastos(): View
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        Artisan::call('app:recuperar-gastos-borrados', ['--dry-run' => true]);

        return view('bitacora.recuperar-gastos', [
            'salida'   => $this->limpiarSalida(Artisan::output()),
            'aplicado' => session('recuperacion_aplicada'),
        ]);
    }

    public function recuperarGastosAplicar(): RedirectResponse
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        DB::transaction(fn () => Artisan::call('app:recuperar-gastos-borrados'));

        return redirect()->route('bitacora.recuperar-gastos')
            ->with('success', 'Reparación aplicada.')
            ->with('recuperacion_aplicada', $this->limpiarSalida(Artisan::output()));
    }

    private function limpiarSalida(string $salida): string
    {
        // Saca los códigos de color de la consola.
        return trim(preg_replace('/\e\[[\d;]*m/', '', $salida));
    }
}
