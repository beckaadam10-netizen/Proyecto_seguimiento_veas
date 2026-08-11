<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expediente extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero',
        'caratula',
        'cliente_id',
        'abogado_id',
        'tipo_causa',
        'tipo_proceso',
        'procedimiento',
        'estado_expediente_id',
        'juzgado',
        'piso',
        'direccion',
        'encargado_actual_id',
        'enc_anterior_id',
        'fecha_recepcion',
        'monto_reclamado',
        'descripcion',
        'representante_cliente',
        'abogados_cliente',
    ];

    protected $casts = [
        'fecha_recepcion'  => 'date',
        'monto_reclamado'  => 'decimal:2',
        'abogados_cliente' => 'array',
    ];

    // ── Relaciones ────────────────────────────────────────────────
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function abogado(): BelongsTo
    {
        return $this->belongsTo(Abogado::class, 'abogado_id');
    }

    public function estadoExpediente(): BelongsTo
    {
        return $this->belongsTo(EstadoExpediente::class);
    }

    public function encargadoActual(): BelongsTo
    {
        return $this->belongsTo(User::class, 'encargado_actual_id');
    }

    public function encargadoAnterior(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enc_anterior_id');
    }

    public function seguidores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'expediente_seguidores')->withTimestamps();
    }

    public function seguimientos(): HasMany
    {
        return $this->hasMany(Seguimiento::class)->orderByDesc('fecha_actuacion');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class)->orderByDesc('created_at');
    }

    public function audiencias(): HasMany
    {
        return $this->hasMany(Audiencia::class)->orderBy('fecha_hora');
    }

    public function partes(): HasMany
    {
        return $this->hasMany(ExpedienteParte::class)->orderBy('orden');
    }

    public function demandantes(): HasMany
    {
        return $this->partes()->where('tipo', 'demandante');
    }

    public function demandados(): HasMany
    {
        return $this->partes()->where('tipo', 'demandado');
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
    // Depende de que exista un estado llamado exactamente "Activo" en el catálogo (el que
    // ya viene sembrado por defecto): si lo renombran o lo borran, esto deja de encontrar
    // nada — es el único acoplamiento que queda con el catálogo, ahora que es editable.
    public function scopeActivos($query)
    {
        return $query->whereHas('estadoExpediente', fn ($q) => $q->where('nombre', 'Activo'));
    }

    public function scopePorEstado($query, int $estadoExpedienteId)
    {
        return $query->where('estado_expediente_id', $estadoExpedienteId);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo_causa', $tipo);
    }

    public function scopeBuscar($query, string $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('numero', 'like', "%{$termino}%")
              ->orWhere('caratula', 'like', "%{$termino}%")
              ->orWhere('juzgado', 'like', "%{$termino}%");
        });
    }

    // ── Helpers ───────────────────────────────────────────────────
    public function getEstadoColorAttribute(): string
    {
        return $this->estadoExpediente?->color ?? 'gray';
    }

    public static function generarNumero(): string
    {
        $anio   = date('Y');
        $ultimo = self::whereYear('created_at', $anio)->max('id') ?? 0;
        return "EXP-{$anio}-" . str_pad($ultimo + 1, 5, '0', STR_PAD_LEFT);
    }

    public function getEstadoLabelAttribute(): string
    {
        return $this->estadoExpediente?->nombre ?? '—';
    }

    public function getProximaAudienciaAttribute(): ?Audiencia
    {
        return $this->audiencias()
            ->where('estado', 'programada')
            ->where('fecha_hora', '>=', now())
            ->orderBy('fecha_hora')
            ->first();
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
