<?php

namespace App\Http\Requests\Auth;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Puede ser el email del staff o el DNI de un cliente, así que no exigimos formato email.
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $identificador = trim((string) $this->string('email'));

        $autenticado = str_contains($identificador, '@')
            ? Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))
            : $this->autenticarComoCliente($identificador);

        if (! $autenticado) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if (! Auth::user()->activo) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Tu usuario está inactivo. Contactá a un administrador.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Login simplificado para clientes: usuario = su nombre completo (o su DNI,
     * como alternativa sin ambigüedad) y contraseña = su DNI.
     * Si todavía no tiene cuenta vinculada, se la crea en este mismo momento.
     */
    private function autenticarComoCliente(string $identificador): bool
    {
        $cliente = ctype_digit($identificador)
            ? Cliente::activos()->where('dni', $identificador)->first()
            : $this->buscarClientePorNombre($identificador);

        if (! $cliente || $this->input('password') !== $cliente->dni) {
            return false;
        }

        Auth::login(User::paraCliente($cliente), $this->boolean('remember'));

        return true;
    }

    /**
     * El nombre no es un campo único en la base: si dos clientes activos
     * coinciden exactamente en su nombre completo, no adivinamos cuál es —
     * ninguno puede entrar por nombre (sí pueden con su DNI, que es único).
     */
    private function buscarClientePorNombre(string $nombre): ?Cliente
    {
        $normalizar = fn (string $texto) => trim(preg_replace('/\s+/', ' ', mb_strtolower($texto)));
        $nombreNormalizado = $normalizar($nombre);

        $coincidencias = Cliente::activos()->get()->filter(
            fn (Cliente $c) => $normalizar($c->nombre_completo) === $nombreNormalizado
        );

        return $coincidencias->count() === 1 ? $coincidencias->first() : null;
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
