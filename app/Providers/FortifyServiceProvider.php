<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
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
        $this->configureViews();
        $this->configurePasswordReset();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('pages::auth.login'));
        Fortify::verifyEmailView(fn () => view('pages::auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('pages::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('pages::auth.confirm-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('pages::auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('pages::auth.reset-password', [
            'request' => $request,
        ]));
    }

    /**
     * Configure password resets.
     *
     * El correo se arma aquí, con vista propia, porque el de Laravel sale en
     * inglés y traducirlo por partes deja la despedida y el pie originales.
     */
    private function configurePasswordReset(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        ResetPassword::toMailUsing(function (User $cuenta, string $token): MailMessage {
            return (new MailMessage)
                ->subject('Cambia la contraseña de tu cuenta del Panel')
                ->view('correo.restablecer-contrasena', [
                    'nombre' => $cuenta->name,
                    'enlace' => route('password.reset', [
                        'token' => $token,
                        'email' => $cuenta->getEmailForPasswordReset(),
                    ]),
                    'minutos' => (int) config('auth.passwords.'.config('fortify.passwords').'.expire'),
                ]);
        });
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });
    }
}
