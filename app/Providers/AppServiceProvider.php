<?php

namespace App\Providers;

use App\Localization\LocaleManager;
use App\Models\OrganizationUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\DatabaseConfig;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Listeners;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(LocaleManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePasswordRules();
        $this->configureRateLimiting();
        $this->configureTenancyEvents();
        $this->configureTenantDatabaseNames();

        Inertia::share([
            'auth.user' => function (Request $request): ?array {
                $user = $request->user();

                if (! $user instanceof OrganizationUser) {
                    return null;
                }

                return [
                    'id' => (string) $user->getKey(),
                    'name' => $user->name,
                    'email' => $user->email,
                    'emailVerified' => $user->hasVerifiedEmail(),
                ];
            },
            'auth.tenant' => function (Request $request): ?array {
                $user = $request->user();

                if (! $user instanceof OrganizationUser) {
                    return null;
                }

                $tenant = $user->organization()->with('tenant')->first()?->tenant;

                if ($tenant === null) {
                    return null;
                }

                return [
                    'provisioningStatus' => $tenant->provisioning_status->value,
                    'available' => $tenant->isAvailable(),
                ];
            },
            'flash.status' => fn (Request $request): ?string => $request->session()->get('status'),
            'flash.error' => fn (Request $request): ?string => $request->session()->get('error'),
            'localization' => fn (): array => [
                'locale' => app()->currentLocale(),
                'languages' => app(LocaleManager::class)->activeLanguageOptions(),
                'translations' => trans('ui'),
            ],
        ]);
    }

    private function configureTenantDatabaseNames(): void
    {
        DatabaseConfig::generateDatabaseNamesUsing(
            static fn (TenantWithDatabase $tenant): string => 'netzero_'.str_replace(
                '-',
                '',
                (string) $tenant->getTenantKey(),
            ),
        );
    }

    private function configureTenancyEvents(): void
    {
        Event::listen(Events\TenancyInitialized::class, Listeners\BootstrapTenancy::class);
        Event::listen(Events\TenancyEnded::class, Listeners\RevertToCentralContext::class);
    }

    private function configurePasswordRules(): void
    {
        Password::defaults(function (): Password {
            $rule = Password::min(12);

            return $this->app->isProduction()
                ? $rule->uncompromised()
                : $rule;
        });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('password-reset-link', function (Request $request): array {
            return $this->passwordLimits(
                request: $request,
                scope: 'password-reset-link',
                emailAttempts: 1,
                ipAttempts: 5,
            );
        });

        RateLimiter::for('password-reset', function (Request $request): array {
            return $this->passwordLimits(
                request: $request,
                scope: 'password-reset',
                emailAttempts: 5,
                ipAttempts: 10,
            );
        });
    }

    /**
     * @return list<Limit>
     */
    private function passwordLimits(
        Request $request,
        string $scope,
        int $emailAttempts,
        int $ipAttempts,
    ): array {
        $limits = [
            Limit::perMinute($ipAttempts)->by($scope.':ip:'.$request->ip()),
        ];
        $email = OrganizationUser::normalizeEmail($request->string('email')->toString());

        if ($email !== '') {
            $limits[] = Limit::perMinute($emailAttempts)
                ->by($scope.':email:'.hash('sha256', $email));
        }

        return $limits;
    }
}
