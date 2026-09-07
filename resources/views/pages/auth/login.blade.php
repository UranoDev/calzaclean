<x-layouts::auth title="Entrar al Panel">
    <div class="flex flex-col gap-6">
        <x-auth-header title="Entra al Panel" description="Escribe tu correo y tu contraseña." />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify
            label="Entrar con una llave de acceso"
            loading-label="Comprobando…"
            separator="O con tu correo"
        />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                label="Correo"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="correo@ejemplo.com"
            />

            <!-- Password -->
            <flux:input
                name="password"
                label="Contraseña"
                type="password"
                required
                autocomplete="current-password"
                placeholder="Contraseña"
                viewable
            />

            <!-- Remember Me -->
            <flux:checkbox name="remember" label="Recordarme en este dispositivo" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    Entrar
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::auth>
