<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'apellido',
        'dni',
        'email',
        'telefono',
        'direccion',
        'tipo',
        'razon_social',
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

    public function tramites(): HasMany
    {
        return $this->hasMany(Tramite::class);
    }

    // ── Accessors ─────────────────────────────────────────────────
    public function getNombreCompletoAttribute(): string
    {
        // Algunos clientes jurídicos antiguos quedaron sin razón social (era opcional
        // antes de exigirla en el formulario), así que hace falta un respaldo para
        // que el accessor nunca devuelva null pese al tipo de retorno string.
        if ($this->tipo === 'persona_juridica') {
            return $this->razon_social ?: trim("{$this->nombre} {$this->apellido}") ?: 'Sin nombre';
        }

        return trim("{$this->nombre} {$this->apellido}") ?: 'Sin nombre';
    }

    // Teléfono normalizado para enlaces wa.me: solo dígitos, con código de país (591) si falta.
    public function getTelefonoWhatsappAttribute(): ?string
    {
        if (! $this->telefono) {
            return null;
        }

        $digitos = preg_replace('/\D+/', '', $this->telefono);

        if (strlen($digitos) === 8) {
            $digitos = '591' . $digitos;
        }

        return $digitos ?: null;
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
              ->orWhere('apellido', 'like', "%{$termino}%")
              ->orWhere('dni', 'like', "%{$termino}%")
              ->orWhere('razon_social', 'like', "%{$termino}%")
              ->orWhere('email', 'like', "%{$termino}%");
        });
    }
}
