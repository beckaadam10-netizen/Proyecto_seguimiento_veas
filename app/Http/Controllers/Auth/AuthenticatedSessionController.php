<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        $puedeVerDashboard = $user->tienePermiso('dashboard.ver');
        $destino = $puedeVerDashboard
            ? route('dashboard', absolute: false)
            : route('seguimientos.index', absolute: false);

        // Si antes de loguearse intentó entrar al panel de control, Laravel guarda
        // esa URL como "intended" para volver ahí tras el login. Si el usuario no
        // tiene permiso para verlo, esa URL guardada pisaría el destino calculado
        // arriba, así que la descartamos en ese caso puntual.
        if (! $puedeVerDashboard && url($request->session()->get('url.intended', '')) === url('/')) {
            $request->session()->forget('url.intended');
        }

        return redirect()->intended($destino);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
