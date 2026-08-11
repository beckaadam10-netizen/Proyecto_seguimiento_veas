<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportePasanteGenerado extends Model
{
    protected $table = 'reportes_pasantes_generados';

    protected $fillable = [
        'usuario_id',
        'desde',
        'hasta',
        'total',
        'revisado',
        'revisado_at',
    ];

    protected $casts = [
        'desde'       => 'date',
        'hasta'       => 'date',
        'total'       => 'decimal:2',
        'revisado'    => 'boolean',
        'revisado_at' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
