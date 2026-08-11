<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Documento;
use App\Models\Expediente;
use App\Models\Seguimiento;
use App\Models\Tramite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentoController extends Controller
{
    public function ver(Request $request, Seguimiento $seguimiento): BinaryFileResponse
    {
        $this->autorizarPropioCliente($request, $seguimiento->expediente ?? $seguimiento->tramite ?? abort(403));

        return response()->file($this->rutaArchivo($seguimiento));
    }

    public function descargar(Request $request, Seguimiento $seguimiento): BinaryFileResponse
    {
        $this->autorizarPropioCliente($request, $seguimiento->expediente ?? $seguimiento->tramite ?? abort(403));

        return response()->download($this->rutaArchivo($seguimiento), basename($seguimiento->archivo_adjunto));
    }

    private function rutaArchivo(Seguimiento $seguimiento): string
    {
        abort_if(! $seguimiento->archivo_adjunto, 404);

        $ruta = storage_path('app/public/' . $seguimiento->archivo_adjunto);

        abort_unless(is_file($ruta), 404);

        return $ruta;
    }

    // Documento suelto, cargado directamente contra un expediente (sin pasar por un seguimiento).
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'expediente_id' => 'required|exists:expedientes,id',
            'titulo'        => 'required|string|max:255',
            'fojas'         => 'nullable|string|max:50',
            'archivo'       => 'required|file|max:10240|mimes:pdf,doc,docx,jpg,png',
        ]);

        $this->autorizarPropioCliente($request, Expediente::findOrFail($data['expediente_id']));

        $data['archivo']    = $request->file('archivo')->store('documentos', 'public');
        $data['usuario_id'] = auth()->id();

        Documento::create($data);

        return back()->with('success', 'Documento cargado correctamente.');
    }

    public function verDocumento(Request $request, Documento $documento): BinaryFileResponse
    {
        $this->autorizarPropioCliente($request, $documento->expediente);

        return response()->file($this->rutaArchivoDocumento($documento));
    }

    public function descargarDocumento(Request $request, Documento $documento): BinaryFileResponse
    {
        $this->autorizarPropioCliente($request, $documento->expediente);

        return response()->download($this->rutaArchivoDocumento($documento), basename($documento->archivo));
    }

    private function rutaArchivoDocumento(Documento $documento): string
    {
        $ruta = storage_path('app/public/' . $documento->archivo);

        abort_unless(is_file($ruta), 404);

        return $ruta;
    }

    public function eliminarDocumento(Request $request, Documento $documento): RedirectResponse
    {
        $this->autorizarPropioCliente($request, $documento->expediente);

        $ruta = storage_path('app/public/' . $documento->archivo);
        if (is_file($ruta)) {
            unlink($ruta);
        }

        $documento->delete();

        return back()->with('success', 'Documento eliminado correctamente.');
    }

    public function index(Request $request): View
    {
        $tipo = $request->input('tipo');

        $registros = collect();

        if ($tipo !== 'expediente') {
            $registros = $registros->concat(
                $this->queryTramites($request)->get()->map(fn (Tramite $t) => $this->normalizarTramite($t))
            );
        }

        if ($tipo !== 'tramite') {
            $registros = $registros->concat(
                $this->queryExpedientes($request)->get()->map(fn (Expediente $e) => $this->normalizarExpediente($e))
            );
        }

        $registros = $registros->sortByDesc(fn ($r) => $r->created_at)->values();

        $porPagina = 15;
        $pagina    = (int) $request->input('page', 1);

        $paginado = new LengthAwarePaginator(
            $registros->forPage($pagina, $porPagina)->values(),
            $registros->count(),
            $porPagina,
            $pagina,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('documentos.index', [
            'registros'  => $paginado,
            'clientes'   => Cliente::activos()->orderBy('nombre')->get(),
            'expedientes'=> Expediente::with('cliente')->activos()->orderByDesc('created_at')->get(),
            'tipo'       => $tipo,
        ]);
    }

    private function queryTramites(Request $request)
    {
        return Tramite::with(['cliente', 'seguimientos' => function ($q) {
                $q->whereNotNull('archivo_adjunto')->with(['tipoDocumento', 'usuario'])->orderByDesc('created_at');
            }])
            ->when($request->filled('buscar'), fn ($q) => $q->where(function ($qq) use ($request) {
                $qq->where('codigo', 'like', '%' . $request->buscar . '%')
                   ->orWhere('nombre', 'like', '%' . $request->buscar . '%');
            }))
            ->when($request->filled('cliente_id'), fn ($q) => $q->where('cliente_id', $request->cliente_id))
            ->when($request->user()->esCliente(), fn ($q) => $q->where('cliente_id', $request->user()->cliente_id));
    }

    private function queryExpedientes(Request $request)
    {
        return Expediente::with([
                'cliente',
                'seguimientos' => function ($q) {
                    $q->whereNotNull('archivo_adjunto')->with(['tipoDocumento', 'usuario'])->orderByDesc('created_at');
                },
                'documentos.usuario',
            ])
            ->when($request->filled('buscar'), fn ($q) => $q->where(function ($qq) use ($request) {
                $qq->where('numero', 'like', '%' . $request->buscar . '%')
                   ->orWhere('caratula', 'like', '%' . $request->buscar . '%');
            }))
            ->when($request->filled('cliente_id'), fn ($q) => $q->where('cliente_id', $request->cliente_id))
            ->when($request->user()->esCliente(), fn ($q) => $q->where('cliente_id', $request->user()->cliente_id));
    }

    private function normalizarTramite(Tramite $t): Tramite
    {
        $t->tipo_registro  = 'tramite';
        $t->codigo_display = $t->codigo;
        $t->titulo_display = $t->nombre;
        $t->ruta_show      = route('tramites.show', $t);
        $t->documentos     = $t->seguimientos;

        return $t;
    }

    private function normalizarExpediente(Expediente $e): Expediente
    {
        $documentosSueltos = $e->documentos;

        $e->tipo_registro  = 'expediente';
        $e->codigo_display = $e->numero;
        $e->titulo_display = $e->caratula;
        $e->ruta_show      = route('expedientes.show', $e);
        $e->documentos     = $e->seguimientos->concat($documentosSueltos)->sortByDesc('created_at')->values();

        return $e;
    }
}
