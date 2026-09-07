<x-layouts::auth title="Recuperar el acceso">
    <div class="flex flex-col gap-6">
        <x-auth-header
            title="Recupera tu acceso"
            description="Escribe el correo de tu cuenta. Te mandamos un enlace para poner una contraseña nueva."
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

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

            <flux:button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                Enviar el enlace
            </flux:button>
        </form>

        <div class="text-center text-sm">
            <flux:link :href="route('login')">Volver a entrar</flux:link>
        </div>
    </div>
</x-layouts::auth>
