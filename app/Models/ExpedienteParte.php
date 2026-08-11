<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpedienteParte extends Model
{
    protected $fillable = [
        'expediente_id',
        'tipo',
        'nombre',
        'representante',
        'abogados_registrados',
        'orden',
    ];

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }
}
