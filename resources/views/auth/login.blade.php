<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email o Nombre/DNI -->
        <div>
            <x-input-label for="email" value="Email (staff) o Nombre completo / C.I/NIT (clientes)" />
            <x-text-input id="email" class="block mt-1 w-full" type="text" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" value="Contraseña (los clientes: su C.I/NIT)" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">Recordarme</span>
            </label>
        </div>

        <div class="flex items-center justify-center mt-4">
            <x-primary-button>
                Iniciar sesión
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6 pt-4 border-t text-center">
        <a href="{{ route('rastreo.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
            ¿Querés consultar el estado de tu trámite sin iniciar sesión?
        </a>
    </div>
</x-guest-layout>
