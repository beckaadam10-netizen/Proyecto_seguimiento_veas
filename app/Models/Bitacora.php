<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bitacora extends Model
{
    protected $table = 'bitacoras';

    protected $fillable = [
        'usuario_id',
        'accion',
        'modelo',
        'modelo_id',
        'descripcion',
        'datos_anteriores',
        'datos_nuevos',
        'ip',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos'     => 'array',
    ];

    public const ACCIONES = [
        'creado'         => 'Creación',
        'actualizado'    => 'Actualización',
        'eliminado'      => 'Eliminación',
        'inicio_sesion'  => 'Inicio de sesión',
        'cierre_sesion'  => 'Cierre de sesión',
    ];

    // Módulo (nombre de clase) => etiqueta legible, para el filtro y la columna "Módulo".
    public const MODELOS = [
        'Cliente'      => 'Clientes',
        'Expediente'   => 'Expedientes',
        'Tramite'      => 'Trámites',
        'Seguimiento'  => 'Seguimientos',
        'Gasto'        => 'Gastos',
        'Cobro'        => 'Cobros',
        'Documento'    => 'Documentos',
        'Audiencia'    => 'Audiencias',
        'User'         => 'Usuarios',
        'Rol'          => 'Roles',
    ];

    // Nombre técnico de columna => etiqueta legible para mostrar en el detalle.
    private const ETIQUETAS_CAMPO = [
        'nombre'                 => 'Nombre',
        'apellido'               => 'Apellido',
        'dni'                    => 'DNI',
        'email'                  => 'Email',
        'telefono'               => 'Teléfono',
        'direccion'              => 'Dirección',
        'tipo'                   => 'Tipo',
        'razon_social'           => 'Razón social',
        'activo'                 => 'Activo',
        'numero'                 => 'Número',
        'caratula'               => 'Carátula',
        'tipo_causa'             => 'Tipo de causa',
        'tipo_proceso'           => 'Tipo de proceso',
        'procedimiento'          => 'Procedimiento',
        'estado'                 => 'Estado',
        'juzgado'                => 'Juzgado',
        'piso'                   => 'Piso',
        'fecha_recepcion'        => 'Fecha de recepción',
        'monto_reclamado'        => 'Monto reclamado',
        'descripcion'            => 'Descripción',
        'representante_cliente'  => 'Representante del cliente',
        'abogados_cliente'       => 'Abogados asignados',
        'codigo'                 => 'Código',
        'prioridad'              => 'Prioridad',
        'fecha_inicio'           => 'Fecha de inicio',
        'fecha_fin_aproximada'   => 'Fecha fin aproximada',
        'observaciones'          => 'Observaciones',
        'titulo'                 => 'Título',
        'fecha_actuacion'        => 'Fecha de actuación',
        'fecha_vencimiento'      => 'Fecha de vencimiento',
        'requiere_respuesta'     => 'Requiere respuesta',
        'respondido'             => 'Respondido',
        'fecha_respuesta'        => 'Fecha de respuesta',
        'archivo_adjunto'        => 'Archivo adjunto',
        'revisado'               => 'Revisado',
        'revisado_at'            => 'Revisado el',
        'concepto'               => 'Concepto',
        'monto'                  => 'Monto',
        'fecha'                  => 'Fecha',
        'metodo_pago'            => 'Método de pago',
        'fojas'                  => 'Fojas',
        'archivo'                => 'Archivo',
        'fecha_hora'             => 'Fecha y hora',
        'modalidad'              => 'Modalidad',
        'duracion_estimada'      => 'Duración estimada (min)',
        'lugar'                  => 'Lugar',
        'sala'                   => 'Sala',
        'resultado'              => 'Resultado',
        'proxima_fecha'          => 'Próxima fecha',
        'notificado_cliente'     => 'Notificó al cliente',
        'name'                   => 'Nombre',

        // Campos "_id" (el valor se resuelve al nombre real vía RESOLVER_FK, esto es
        // solo la etiqueta de la fila).
        'cliente_id'             => 'Cliente',
        'abogado_id'             => 'Abogado',
        'encargado_actual'       => 'Encargado actual',
        'enc_anterior'           => 'Encargado anterior',
        'responsable_id'         => 'Responsable',
        'tipo_tramite_id'        => 'Tipo de trámite',
        'institucion_publica_id' => 'Institución pública',
        'expediente_id'          => 'Expediente',
        'tramite_id'             => 'Trámite',
        'usuario_id'             => 'Usuario',
        'tipo_actuacion_id'      => 'Tipo de actuación',
        'tipo_documento_id'      => 'Tipo de documento',
        'seguimiento_id'         => 'Seguimiento',
        'tipo_gasto_id'          => 'Tipo de gasto',
        'gasto_id'               => 'Gasto',
        'tipo_audiencia_id'      => 'Tipo de audiencia',
        'role_id'                => 'Rol',
    ];

    // Campos "_id" => [Modelo relacionado, atributo a mostrar en su lugar]. Se resuelve
    // contra el estado ACTUAL del registro relacionado (si ya no existe, se avisa así).
    private const RESOLVER_FK = [
        'cliente_id'             => [Cliente::class, 'nombre_completo'],
        'abogado_id'             => [Abogado::class, 'nombre'],
        'responsable_id'         => [Abogado::class, 'nombre'],
        'tipo_tramite_id'        => [TipoTramite::class, 'nombre'],
        'institucion_publica_id' => [InstitucionPublica::class, 'nombre'],
        'expediente_id'          => [Expediente::class, 'numero'],
        'tramite_id'             => [Tramite::class, 'codigo'],
        'usuario_id'             => [User::class, 'name'],
        'tipo_actuacion_id'      => [TipoActuacion::class, 'nombre'],
        'tipo_documento_id'      => [TipoDocumento::class, 'nombre'],
        'seguimiento_id'         => [Seguimiento::class, 'titulo'],
        'tipo_gasto_id'          => [TipoGasto::class, 'nombre'],
        'gasto_id'               => [Gasto::class, 'concepto'],
        'tipo_audiencia_id'      => [TipoAudiencia::class, 'nombre'],
        'role_id'                => [Rol::class, 'nombre'],
    ];

    // Campos que se guardan como 0/1 y se leen mejor como Sí/No que como número.
    private const CAMPOS_BOOLEANOS = ['activo', 'requiere_respuesta', 'respondido', 'revisado', 'notificado_cliente'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function getAccionLabelAttribute(): string
    {
        return self::ACCIONES[$this->accion] ?? ucfirst($this->accion);
    }

    public function getModeloLabelAttribute(): ?string
    {
        return $this->modelo ? (self::MODELOS[$this->modelo] ?? $this->modelo) : null;
    }

    public function getDatosAnterioresLegiblesAttribute(): array
    {
        return $this->legible($this->datos_anteriores);
    }

    public function getDatosNuevosLegiblesAttribute(): array
    {
        return $this->legible($this->datos_nuevos);
    }

    // El id propio del registro no aporta nada leyendo el historial, y los campos "_id"
    // se cambian por el nombre real del expediente/trámite/usuario/etc. al que apuntan.
    private function legible(?array $datos): array
    {
        if (! $datos) {
            return [];
        }

        $resultado = [];
        foreach ($datos as $campo => $valor) {
            if ($campo === 'id') {
                continue;
            }

            $etiqueta = self::ETIQUETAS_CAMPO[$campo] ?? ucfirst(str_replace('_', ' ', $campo));

            if ($valor !== null && isset(self::RESOLVER_FK[$campo])) {
                [$modeloClase, $atributo] = self::RESOLVER_FK[$campo];
                $query       = in_array(SoftDeletes::class, class_uses_recursive($modeloClase), true)
                    ? $modeloClase::withTrashed()
                    : $modeloClase::query();
                $relacionado = $query->find($valor);
                $valor       = $relacionado ? $relacionado->{$atributo} : "(eliminado, id {$valor})";
            } elseif (in_array($campo, self::CAMPOS_BOOLEANOS, true)) {
                $valor = $valor ? 'Sí' : 'No';
            }

            $resultado[$etiqueta] = $valor;
        }

        return $resultado;
    }
}
