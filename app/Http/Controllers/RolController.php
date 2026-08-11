<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RolController extends Controller
{
    public function create(): View
    {
        $modulosPermisos = Permiso::agrupadosPorModulo();

        return view('administracion.roles.create', compact('modulosPermisos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100|unique:roles,nombre',
            'descripcion' => 'nullable|string',
            'permisos'    => 'array',
            'permisos.*'  => 'exists:permisos,id',
        ]);

        $rol = Rol::create([
            'nombre'      => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
        ]);

        $rol->permisos()->sync($data['permisos'] ?? []);

        return redirect()
            ->route('administracion.index', ['abrir' => 'roles'])
            ->with('success', 'Rol creado correctamente.');
    }

    public function edit(Rol $rol): View
    {
        $modulosPermisos = Permiso::agrupadosPorModulo();
        $rol->load('permisos');

        return view('administracion.roles.edit', compact('rol', 'modulosPermisos'));
    }

    public function update(Request $request, Rol $rol): RedirectResponse
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100|unique:roles,nombre,' . $rol->id,
            'descripcion' => 'nullable|string',
            'activo'      => 'boolean',
            'permisos'    => 'array',
            'permisos.*'  => 'exists:permisos,id',
        ]);

        $rol->update([
            'nombre'      => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'activo'      => $request->boolean('activo'),
        ]);

        $rol->permisos()->sync($data['permisos'] ?? []);

        return redirect()
            ->route('administracion.index', ['abrir' => 'roles'])
            ->with('success', 'Rol actualizado correctamente.');
    }

    public function destroy(Rol $rol): RedirectResponse
    {
        $rol->delete();

        return redirect()
            ->route('administracion.index', ['abrir' => 'roles'])
            ->with('success', 'Rol eliminado correctamente.');
    }
}
