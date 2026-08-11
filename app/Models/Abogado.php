<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Abogado extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'matricula',
        'telefono',
        'email',
        'especialidad',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // ── Relaciones ────────────────────────────────────────────────
    public function expedientes(): HasMany
    {
        return $this->hasMany(Expediente::class);
    }

    public function audiencias(): HasMany
    {
        return $this->hasMany(Audiencia::class);
    }

    public function tramites(): HasMany
    {
        return $this->hasMany(Tramite::class, 'responsable_id');
    }

    // ── Scopes ────────────────────────────────────────────────────
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeBuscar($query, string $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombre', 'like', "%{$termino}%")
              ->orWhere('matricula', 'like', "%{$termino}%")
              ->orWhere('email', 'like', "%{$termino}%");
        });
    }
}
