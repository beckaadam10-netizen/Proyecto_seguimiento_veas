<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportePasanteGenerado extends Model
{
    use SoftDeletes;

    protected $table = 'reportes_pasantes_generados';

    protected $fillable = [
        'usuario_id',
        'desde',
        'hasta',
        'total',
        'revisado',
        'revisado_at',
        'hasta_original',
        'corregido_por',
        'corregido_at',
    ];

    protected $casts = [
        'desde'          => 'date',
        'hasta'          => 'date',
        'hasta_original' => 'date',
        'total'          => 'decimal:2',
        'revisado'       => 'boolean',
        'revisado_at'    => 'datetime',
        'corregido_at'   => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function corregidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corregido_por');
    }
}
