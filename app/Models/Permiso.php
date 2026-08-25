<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permiso extends Model
{
    use HasFactory;

    protected $table = 'permisos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Módulos del sistema, en el orden en que se muestran en la matriz de permisos.
    public const MODULOS = [
        'dashboard'      => ['label' => 'Panel de Control', 'icon' => 'fa-chart-pie'],
        'clientes'       => ['label' => 'Clientes', 'icon' => 'fa-users'],
        'abogados'       => ['label' => 'Abogados', 'icon' => 'fa-user-tie'],
        'expedientes'    => ['label' => 'Expedientes', 'icon' => 'fa-folder-open'],
        'tramites'       => ['label' => 'Trámites', 'icon' => 'fa-file-circle-check'],
        'gastos_cobros'  => ['label' => 'Gastos y Cobros', 'icon' => 'fa-hand-holding-dollar'],
        'seguimientos'   => ['label' => 'Seguimientos', 'icon' => 'fa-list-check'],
        'historial_revision' => ['label' => 'Historial de Revisión', 'icon' => 'fa-clipboard-check'],
        'actualizaciones'=> ['label' => 'Actualizaciones', 'icon' => 'fa-comment-dots'],
        'audiencias'     => ['label' => 'Audiencias', 'icon' => 'fa-gavel'],
        'documentos'     => ['label' => 'Documentos', 'icon' => 'fa-folder-open'],
        'parametros'     => ['label' => 'Parámetros', 'icon' => 'fa-sliders'],
        'reportes'       => ['label' => 'Reportes', 'icon' => 'fa-chart-column'],
        'administracion' => ['label' => 'Administración', 'icon' => 'fa-user-shield'],
        'bitacora'       => ['label' => 'Bitácora', 'icon' => 'fa-clipboard-list'],
    ];

    // Acciones que existen en todo el sistema. No todos los módulos tienen todas: por ejemplo
    // "cobrar" solo aplica a Gastos y Cobros, "descargar" solo a Documentos, "pasantes"
    // solo a Reportes (Reporte Pasantes, separado de "ver" para poder dárselo aparte del
    // resto de los reportes), "modificar_fecha" solo a Seguimientos (poder elegir la
    // fecha de actuación al registrar/editar, en vez de que quede fija en hoy), y "revisar"
    // también solo a Seguimientos (marcar una actuación como revisada en Historial de
    // Revisión) — en la matriz esas celdas quedan vacías (agrupadosPorModulo() solo arma
    // las que existen).
    public const ACCIONES = [
        'ver'             => 'Ver',
        'crear'           => 'Crear',
        'modificar'       => 'Modificar',
        'modificar_fecha' => 'Modif. Fecha',
        'revisar'         => 'Revisar',
        'cobrar'          => 'Cobrar',
        'descargar'       => 'Descargar',
        'pasantes'        => 'Rep. Pasantes',
        'eliminar'        => 'Eliminar',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'permiso_rol', 'permiso_id', 'rol_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // Agrupa los permisos activos por módulo, listos para pintar la matriz Ver/Gestionar/Eliminar.
    public static function agrupadosPorModulo()
    {
        $todos = static::activos()->get()->keyBy('nombre');

        $grupos = [];
        foreach (self::MODULOS as $modulo => $meta) {
            $permisos = [];
            foreach (array_keys(self::ACCIONES) as $accion) {
                $nombre = "{$modulo}.{$accion}";
                if ($todos->has($nombre)) {
                    $permisos[$accion] = $todos->get($nombre);
                }
            }

            if ($permisos) {
                $grupos[$modulo] = [
                    'label'    => $meta['label'],
                    'icon'     => $meta['icon'],
                    'permisos' => $permisos,
                ];
            }
        }

        return $grupos;
    }
}
