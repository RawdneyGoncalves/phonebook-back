<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\ContactRepository;
use App\Services\ContactService;
use App\Services\ImageUploadService;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ContactRepository::class, function ($app) {
            return new ContactRepository($app->make(\App\Models\Contact::class));
        });

        $this->app->singleton(ContactService::class, function ($app) {
            return new ContactService($app->make(ContactRepository::class));
        });

        $this->app->singleton(ImageUploadService::class, function () {
            return new ImageUploadService();
        });
    }

    public function boot(): void
    {
        //
    }
}
