<?php

declare(strict_types=1);

namespace App\Modules\Discussion\Providers;

use Illuminate\Support\ServiceProvider;

class DiscussionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
    }
}
