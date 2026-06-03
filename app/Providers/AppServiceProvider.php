<?php

namespace App\Providers;

use App\Enums\DeploymentEnvironment;
use App\Models\User;
use App\Services\PRMStorage\DiskPRMStorage;
use App\Services\PRMStorage\PRMStorageInterface;
use App\Services\PRMStorage\S3PRMStorage;
use App\Services\Remarks\RemarksHTTPServer;
use App\Services\Remarks\RemarksRunDockerContainer;
use App\Services\Remarks\RemarksService;
use App\Services\RMapi;
use Event;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Sentry\Laravel\Integration;
use URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->scoped(RMapi::class, function () {
            return new RMapi(Auth::user());
        });

        $this->app->bind(RemarksService::class, fn() => match (config('scrybble.host_runner')) {
            'docker' => new RemarksHTTPServer(),
            'bare-metal' => new RemarksRunDockerContainer()
        });

        $this->app->bind(PRMStorageInterface::class, fn() => match (config('scrybble.storage_platform')) {
            // TODO: Implement disk storage instead of stubbing
            'disk' => new DiskPRMStorage(),
            "s3" => new S3PRMStorage(),
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        DeploymentEnvironment::current();

        Blade::if('cloudflareTurnstile', function () {
            $configuredCorrectly = config('scrybble.cloudflare.secret_key') && config('scrybble.cloudflare.site_key');

            if ((config('app.env') === 'production') && !$configuredCorrectly) {
                Log::warning("The site is live, but Cloudflare turnstile is not configured correctly. Check the `site_key` and `secret_key`, see config/scrybble.php");
            }

            return $configuredCorrectly;
        });

        Passport::deviceUserCodeView("auth.device.user-code");
        Passport::deviceAuthorizationView('auth.device.authorize');

        if ($this->app->environment('production')) {
            URL::forceScheme('https');

            Event::listen(function (Authenticated $event) {
                /** @var User $user */
                $user = $event->user;

                Integration::configureScope(fn($scope) => $scope->setUser(['id' => $user->id]));
            });
        }
    }
}
