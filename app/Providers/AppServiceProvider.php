<?php

namespace App\Providers;

use App\Support\ReglaDeContrasena;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // La regla vive en ReglaDeContrasena, junto al mínimo y al texto que
        // lo explica, para que no puedan quedar diciendo cosas distintas.
        Password::defaults(fn (): ?Password => ReglaDeContrasena::regla());
    }
}
