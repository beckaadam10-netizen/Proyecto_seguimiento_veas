<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'activo',
        'cliente_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'role_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    // Gastos que cargó este usuario.
    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class, 'usuario_id');
    }

    // true cuando este usuario es el acceso de un cliente (no un usuario interno del estudio).
    public function esCliente(): bool
    {
        return ! is_null($this->cliente_id);
    }

    public function esAdmin(): bool
    {
        return $this->rol?->nombre === 'Administrador';
    }

    public function tienePermiso(string $permiso): bool
    {
        if (! $this->activo) {
            return false;
        }

        return $this->rol?->permisos->contains(fn (Permiso $p) => $p->nombre === $permiso && $p->activo) ?? false;
    }

    // Azúcar sintáctica para plantillas: $user->puede('clientes', 'crear')
    public function puede(string $modulo, string $accion = 'ver'): bool
    {
        return $this->tienePermiso("{$modulo}.{$accion}");
    }

    // Primer día que todavía puede entrar en un PDF nuevo del Reporte de Pasantes: el día
    // siguiente al "Hasta" más reciente de los PDFs que generó este usuario, o null si
    // todavía no generó ninguno. withTrashed(): un período eliminado igual ya se facturó.
    // Un PDF viejo pudo quedar con "Hasta" en el futuro (antes se podía elegir); para ese
    // se toma el día en que se generó, así no bloquea días que todavía no habían pasado.
    // Los gastos con fecha futura que ese PDF ya incluyó siguen excluidos igual, porque
    // queryGastosPasante() corta por cuándo se creó cada gasto.
    public function siguienteDesdeReporte(): ?Carbon
    {
        $ultimoHasta = ReportePasanteGenerado::withTrashed()
            ->where('usuario_id', $this->id)
            ->get(['hasta', 'created_at'])
            ->map(fn (ReportePasanteGenerado $r) => $r->hasta->copy()->min($r->created_at->copy()->startOfDay()))
            ->max();

        return $ultimoHasta ? $ultimoHasta->copy()->addDay()->startOfDay() : null;
    }

    // Cuenta de acceso de un cliente: usuario = su nombre completo, contraseña = su DNI.
    // La crea si todavía no existe, o la resincroniza con los datos actuales del cliente
    // si ya existía (ej. le cambiaron el nombre). Rol siempre "Cliente".
    public static function paraCliente(Cliente $cliente): self
    {
        $rolCliente = Rol::where('nombre', 'Cliente')->first();

        $user = static::firstOrNew(['cliente_id' => $cliente->id]);
        $user->name = $cliente->nombre_completo;
        $user->email = $cliente->email ?: "dni{$cliente->dni}@cliente.local";
        $user->password = Hash::make($cliente->dni);
        $user->role_id = $rolCliente?->id;
        $user->activo = $user->exists ? $user->activo : true;
        $user->email_verified_at = $user->email_verified_at ?? now();
        $user->save();

        return $user;
    }
}
