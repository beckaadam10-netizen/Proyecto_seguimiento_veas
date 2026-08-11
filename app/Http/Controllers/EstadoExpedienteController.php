<?php

namespace App\Http\Controllers;

use App\Models\EstadoExpediente;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EstadoExpedienteController extends Controller
{
    // Paleta acotada: son las mismas clases de Tailwind ya usadas en otros badges de la
    // app, para no terminar con un color inventado que no tenga las clases bg-*/text-*
    // reconocibles en ningún lado.
    public const COLORES = ['green', 'yellow', 'gray', 'red', 'emerald', 'amber', 'blue', 'purple', 'cyan', 'orange'];

    // El listado real vive embebido en el panel de Parámetros (como el resto de los
    // catálogos, ver parametros/index.blade.php) — esta ruta solo existe porque el
    // helper recursoConPermisos-like de routes/web.php la registra junto a las demás.
    public function index(): RedirectResponse
    {
        return redirect()->route('parametros.index', ['abrir' => 'estado-expediente']);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('parametros.index', ['abrir' => 'estado-expediente']);
    }

    public function edit(EstadoExpediente $estado_expediente): RedirectResponse
    {
        return redirect()->route('parametros.index', ['abrir' => 'estado-expediente']);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100|unique:estados_expediente,nombre',
            'descripcion' => 'nullable|string',
            'color'       => 'required|in:' . implode(',', self::COLORES),
        ]);

        $estado = EstadoExpediente::create($data);

        if ($request->wantsJson()) {
            return response()->json($estado);
        }

        return redirect()
            ->route('parametros.index', ['abrir' => 'estado-expediente'])
            ->with('success', 'Estado de expediente creado correctamente.');
    }

    public function update(Request $request, EstadoExpediente $estado_expediente): RedirectResponse
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100|unique:estados_expediente,nombre,' . $estado_expediente->id,
            'descripcion' => 'nullable|string',
            'color'       => 'required|in:' . implode(',', self::COLORES),
            'activo'      => 'boolean',
        ]);

        $estado_expediente->update($data);

        return redirect()
            ->route('parametros.index', ['abrir' => 'estado-expediente'])
            ->with('success', 'Estado de expediente actualizado correctamente.');
    }

    // A diferencia del resto de los catálogos, acá SÍ hace falta cuidarse: el estado es
    // obligatorio en cada expediente (FK not-null a nivel de aplicación), así que borrar
    // uno que está en uso rompería la relación. La base ya lo impide (constraint), acá
    // solo se traduce ese error en un mensaje entendible en vez de un 500.
    public function destroy(EstadoExpediente $estado_expediente): RedirectResponse
    {
        try {
            $estado_expediente->delete();
        } catch (QueryException $e) {
            return redirect()
                ->route('parametros.index', ['abrir' => 'estado-expediente'])
                ->with('error', 'No se puede eliminar: hay expedientes con este estado. Cambiales el estado primero.');
        }

        return redirect()
            ->route('parametros.index', ['abrir' => 'estado-expediente'])
            ->with('success', 'Estado de expediente eliminado correctamente.');
    }
}
