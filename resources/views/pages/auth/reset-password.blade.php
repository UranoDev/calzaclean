<x-layouts::auth title="Contraseña nueva">
    <div class="flex flex-col gap-6">
        <x-auth-header
            title="Pon una contraseña nueva"
            description="Escríbela dos veces. Con la nueva vuelves a entrar al Panel."
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-6">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <flux:input
                name="email"
                label="Correo"
                :value="old('email', $request->query('email'))"
                type="email"
                required
                autocomplete="email"
                readonly
            />

            <flux:input
                name="password"
                label="Contraseña nueva"
                type="password"
                required
                autofocus
                autocomplete="new-password"
                placeholder="{{ \App\Support\ReglaDeContrasena::enPalabras() }}"
                viewable
            />

            <flux:input
                name="password_confirmation"
                label="Repite la contraseña"
                type="password"
                required
                autocomplete="new-password"
                placeholder="La misma de arriba"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="reset-password-button">
                Guardar la contraseña
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
