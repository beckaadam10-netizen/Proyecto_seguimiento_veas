<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tramite extends Model
{
    use HasFactory;

    public const FLUJO_ESTADOS = [
        'iniciado', 'en_proceso', 'presentado', 'observado', 'aprobado', 'finalizado',
    ];

    protected $fillable = [
        'codigo',
        'tipo_tramite_id',
        'nombre',
        'cliente_id',
        'responsable_id',
        'institucion_publica_id',
        'estado',
        'prioridad',
        'fecha_inicio',
        'fecha_fin_aproximada',
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio'          => 'date',
        'fecha_fin_aproximada'  => 'date',
    ];

    // ── Relaciones ────────────────────────────────────────────────
    public function tipoTramite(): BelongsTo
    {
        return $this->belongsTo(TipoTramite::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Abogado::class, 'responsable_id');
    }

    public function institucionPublica(): BelongsTo
    {
        return $this->belongsTo(InstitucionPublica::class);
    }

    public function seguimientos(): HasMany
    {
        return $this->hasMany(Seguimiento::class)->orderByDesc('fecha_actuacion');
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class)->orderByDesc('fecha');
    }

    public function cobros(): HasMany
    {
        return $this->hasMany(Cobro::class)->orderByDesc('fecha');
    }

    // ── Scopes ────────────────────────────────────────────────────
    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeActivos($query)
    {
        return $query->whereNotIn('estado', ['finalizado', 'cancelado', 'rechazado']);
    }

    // ── Helpers ───────────────────────────────────────────────────
    public static function generarCodigo(): string
    {
        $anio   = date('Y');
        $ultimo = self::whereYear('created_at', $anio)->max('id') ?? 0;

        return "TRAM-{$anio}-" . str_pad($ultimo + 1, 5, '0', STR_PAD_LEFT);
    }

    public function getEstadoColorAttribute(): string
    {
        return match ($this->estado) {
            'iniciado'   => 'amber',
            'en_proceso' => 'indigo',
            'presentado' => 'purple',
            'observado'  => 'yellow',
            'aprobado'   => 'green',
            'rechazado'  => 'red',
            'finalizado' => 'gray',
            'cancelado'  => 'red',
            default      => 'gray',
        };
    }

    public static function estadosLabels(): array
    {
        return [
            'iniciado'   => 'Iniciado',
            'en_proceso' => 'En proceso',
            'presentado' => 'Presentado',
            'observado'  => 'Observado',
            'aprobado'   => 'Aprobado',
            'rechazado'  => 'Rechazado',
            'finalizado' => 'Finalizado',
            'cancelado'  => 'Cancelado',
        ];
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::estadosLabels()[$this->estado] ?? ucfirst(str_replace('_', ' ', $this->estado));
    }

    // Próximo estado sugerido dentro del flujo normal (null si no aplica, ej. rechazado/cancelado/finalizado).
    public function getEstadoSiguienteAttribute(): ?string
    {
        $posicion = array_search($this->estado, self::FLUJO_ESTADOS, true);

        if ($posicion === false || $posicion === count(self::FLUJO_ESTADOS) - 1) {
            return null;
        }

        return self::FLUJO_ESTADOS[$posicion + 1];
    }

    public function getPrioridadColorAttribute(): string
    {
        return match ($this->prioridad) {
            'urgente' => 'red',
            'alta'    => 'orange',
            'media'   => 'yellow',
            'baja'    => 'green',
            default   => 'gray',
        };
    }

    public function getTotalGastosAttribute(): float
    {
        return (float) $this->gastos->sum('monto');
    }

    public function getTotalCobradoAttribute(): float
    {
        return (float) $this->cobros->sum('monto');
    }

    public function getSaldoPendienteAttribute(): float
    {
        return $this->total_gastos - $this->total_cobrado;
    }

    public function getEstadoPagoAttribute(): string
    {
        if ($this->total_gastos <= 0) {
            return 'sin_gastos';
        }

        if ($this->saldo_pendiente <= 0) {
            return 'pagado';
        }

        return $this->total_cobrado > 0 ? 'parcial' : 'pendiente';
    }

    public function getEstadoPagoLabelAttribute(): string
    {
        return match ($this->estado_pago) {
            'pagado'    => 'Pagado',
            'parcial'   => 'Parcialmente pagado',
            'pendiente' => 'Pendiente',
            default     => 'Sin gastos',
        };
    }

    public function getEstadoPagoColorAttribute(): string
    {
        return match ($this->estado_pago) {
            'pagado'    => 'emerald',
            'parcial'   => 'amber',
            'pendiente' => 'red',
            default     => 'gray',
        };
    }
}
