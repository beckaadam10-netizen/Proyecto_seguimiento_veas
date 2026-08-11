<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdministracionController extends Controller
{
    public function index(Request $request): View
    {
        $usuarios = User::with('rol')
            ->when($request->filled('nombre'), fn ($q) => $q->where('name', 'like', '%' . $request->nombre . '%'))
            ->when($request->filled('role_id'), fn ($q) => $q->where('role_id', $request->role_id))
            ->when($request->filled('estado'), fn ($q) => $q->where('activo', $request->estado))
            ->orderBy('name')
            ->get();

        $rolesFiltro      = Rol::orderBy('nombre')->get();
        $roles            = Rol::withCount('usuarios')->with('permisos')->orderBy('nombre')->get();
        $permisos         = Permiso::orderBy('nombre')->get();
        $modulosPermisos  = Permiso::agrupadosPorModulo();

        return view('administracion.index', compact('usuarios', 'roles', 'permisos', 'modulosPermisos', 'rolesFiltro'));
    }
}
