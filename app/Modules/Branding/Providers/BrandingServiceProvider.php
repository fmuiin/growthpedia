<?php

declare(strict_types=1);

namespace App\Modules\Branding\Providers;

use App\Modules\Branding\Contracts\BrandingServiceInterface;
use App\Modules\Branding\Services\BrandingService;
use Illuminate\Support\ServiceProvider;

class BrandingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BrandingServiceInterface::class, BrandingService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
    }
}
