<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function create(): View
    {
        $roles = Rol::activos()->orderBy('nombre')->get();

        return view('administracion.usuarios.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:150',
            'email'    => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role_id'  => 'nullable|exists:roles,id',
            'activo'   => 'boolean',
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['activo']   = $request->boolean('activo');

        User::create($data);

        return redirect()
            ->route('administracion.index', ['abrir' => 'usuarios'])
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario): View
    {
        $roles = Rol::activos()->orderBy('nombre')->get();

        return view('administracion.usuarios.edit', ['usuario' => $usuario, 'roles' => $roles]);
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:150',
            'email'    => 'required|email|max:150|unique:users,email,' . $usuario->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role_id'  => 'nullable|exists:roles,id',
            'activo'   => 'boolean',
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['activo'] = $request->boolean('activo');

        if ($usuario->id === auth()->id() && ! $data['activo']) {
            return back()->withInput()->with('error', 'No podés desactivar tu propio usuario.');
        }

        $usuario->update($data);

        return redirect()
            ->route('administracion.index', ['abrir' => 'usuarios'])
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $usuario): RedirectResponse
    {
        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No podés eliminar tu propio usuario.');
        }

        $usuario->delete();

        return redirect()
            ->route('administracion.index', ['abrir' => 'usuarios'])
            ->with('success', 'Usuario eliminado correctamente.');
    }
}
