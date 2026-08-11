<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Autoregistro de clientes: solo se puede crear una cuenta si el DNI y el
     * email coinciden con un cliente activo ya cargado por el estudio. Así
     * evitamos que alguien cree una cuenta "en el aire" no vinculada a nadie,
     * o que se registre con el DNI de otra persona sin conocer también su
     * email real.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'dni' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $mensajeGenerico = 'No encontramos un cliente activo con ese DNI y ese email. Verificá los datos o contactá al estudio.';

        $cliente = Cliente::activos()
            ->where('dni', $request->dni)
            ->whereRaw('LOWER(email) = ?', [$request->email])
            ->first();

        if (! $cliente) {
            throw ValidationException::withMessages(['dni' => $mensajeGenerico]);
        }

        if (User::where('cliente_id', $cliente->id)->exists() || User::where('email', $cliente->email)->exists()) {
            throw ValidationException::withMessages([
                'dni' => 'Ya existe una cuenta para este cliente. Iniciá sesión o recuperá tu contraseña.',
            ]);
        }

        $rolCliente = Rol::where('nombre', 'Cliente')->first();

        $user = User::create([
            'name'       => $cliente->nombre_completo,
            'email'      => $cliente->email,
            'password'   => Hash::make($request->password),
            'cliente_id' => $cliente->id,
            'role_id'    => $rolCliente?->id,
            'activo'     => true,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
