<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Seguimiento extends Model
{
    use HasFactory;

    protected $fillable = [
        'expediente_id',
        'tramite_id',
        'usuario_id',
        'tipo_actuacion_id',
        'titulo',
        'descripcion',
        'fecha_actuacion',
        'fecha_vencimiento',
        'requiere_respuesta',
        'respondido',
        'fecha_respuesta',
        'prioridad',
        'archivo_adjunto',
        'tipo_documento_id',
        'observaciones',
        'revisado',
        'revisado_at',
    ];

    protected $casts = [
        'fecha_actuacion'    => 'date',
        'fecha_vencimiento'  => 'date',
        'fecha_respuesta'    => 'date',
        'requiere_respuesta' => 'boolean',
        'respondido'         => 'boolean',
        'revisado'           => 'boolean',
        'revisado_at'        => 'datetime',
    ];

    // ── Relaciones ────────────────────────────────────────────────
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function tramite(): BelongsTo
    {
        return $this->belongsTo(Tramite::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'usuario_id');
    }

    public function tipoActuacion(): BelongsTo
    {
        return $this->belongsTo(TipoActuacion::class);
    }

    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class);
    }

    public function gasto(): HasOne
    {
        return $this->hasOne(Gasto::class);
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class);
    }

    // ── Scopes ────────────────────────────────────────────────────
    public function scopePendientes($query)
    {
        return $query->where('requiere_respuesta', true)->where('respondido', false);
    }

    public function scopeVencidos($query)
    {
        return $query->pendientes()->where('fecha_vencimiento', '<', today());
    }

    public function scopePorVencer($query, int $dias = 7)
    {
        return $query->pendientes()
            ->where('fecha_vencimiento', '>=', today())
            ->where('fecha_vencimiento', '<=', today()->addDays($dias));
    }

    public function scopeUrgentes($query)
    {
        return $query->whereIn('prioridad', ['alta', 'urgente']);
    }

    // ── Helpers ───────────────────────────────────────────────────
    public function getPrioridadColorAttribute(): string
    {
        return match($this->prioridad) {
            'urgente' => 'red',
            'alta'    => 'orange',
            'media'   => 'yellow',
            'baja'    => 'green',
            default   => 'gray',
        };
    }

    public function estaVencido(): bool
    {
        return $this->requiere_respuesta
            && ! $this->respondido
            && $this->fecha_vencimiento
            && $this->fecha_vencimiento->isPast();
    }

    public function getClienteAttribute(): ?Cliente
    {
        return $this->expediente?->cliente ?? $this->tramite?->cliente;
    }

    public function getIconoArchivoAttribute(): string
    {
        return match (strtolower(pathinfo($this->archivo_adjunto ?? '', PATHINFO_EXTENSION))) {
            'pdf'         => 'fa-file-pdf',
            'doc', 'docx' => 'fa-file-word',
            'jpg', 'jpeg', 'png' => 'fa-file-image',
            default       => 'fa-file',
        };
    }
}
