<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Audiencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'expediente_id',
        'abogado_id',
        'titulo',
        'tipo_audiencia_id',
        'fecha_hora',
        'modalidad',
        'duracion_estimada',
        'lugar',
        'sala',
        'estado',
        'resultado',
        'proxima_fecha',
        'notificado_cliente',
    ];

    protected $casts = [
        'fecha_hora'         => 'datetime',
        'proxima_fecha'      => 'date',
        'notificado_cliente' => 'boolean',
        'duracion_estimada'  => 'integer',
    ];

    // ── Relaciones ────────────────────────────────────────────────
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function abogado(): BelongsTo
    {
        return $this->belongsTo(Abogado::class, 'abogado_id');
    }

    public function tipoAudiencia(): BelongsTo
    {
        return $this->belongsTo(TipoAudiencia::class);
    }

    // ── Scopes ────────────────────────────────────────────────────
    public function scopeProximas($query, int $dias = 30)
    {
        return $query->whereIn('estado', ['programada', 'confirmada'])
            ->where('fecha_hora', '>=', now())
            ->where('fecha_hora', '<=', now()->addDays($dias))
            ->orderBy('fecha_hora');
    }

    public function scopeHoy($query)
    {
        return $query->whereDate('fecha_hora', today())->orderBy('fecha_hora');
    }

    public function scopePorAbogado($query, int $abogadoId)
    {
        return $query->where('abogado_id', $abogadoId);
    }

    // ── Helpers ───────────────────────────────────────────────────
    public function getFechaFormateadaAttribute(): string
    {
        return $this->fecha_hora->format('d/m/Y H:i');
    }

    public function getEstadoColorAttribute(): string
    {
        return match($this->estado) {
            'programada'   => 'purple',
            'confirmada'   => 'green',
            'realizada'    => 'gray',
            'suspendida'   => 'yellow',
            'reprogramada' => 'orange',
            'cancelada'    => 'red',
            default        => 'gray',
        };
    }
}
