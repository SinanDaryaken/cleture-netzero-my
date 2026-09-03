<?php

namespace App\Providers;

use App\Localization\LocaleManager;
use App\Models\OrganizationUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

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
            'flash.status' => fn (Request $request): ?string => $request->session()->get('status'),
            'localization' => fn (): array => [
                'locale' => app()->currentLocale(),
                'languages' => app(LocaleManager::class)->activeLanguageOptions(),
                'translations' => trans('ui'),
            ],
        ]);
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
