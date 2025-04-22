<?php

namespace App\Providers;

use App\Services\PRMStorage\DiskPRMStorage;
use App\Services\PRMStorage\PRMStorageInterface;
use App\Services\PRMStorage\S3PRMStorage;
use App\Services\Remarks\RemarksHTTPServer;
use App\Services\Remarks\RemarksRunDockerContainer;
use App\Services\Remarks\RemarksService;
use App\Services\RMapi;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
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
        $this->app->singleton(RMapi::class, function () {
            return new RMapi();
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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        if (config('scrybble.storage_platform') === "disk") {
            Storage::disk('efs')->buildTemporaryUrlsUsing(fn($path, $expiration, $options) =>
                URL::temporarySignedRoute("prmdownload", $expiration, array_merge($options, ['path' => $path]))
            );
        }
    }
}
