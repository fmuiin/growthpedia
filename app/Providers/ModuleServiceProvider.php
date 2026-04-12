<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $modulesPath = app_path('Modules');

        if (! File::isDirectory($modulesPath)) {
            return;
        }

        foreach (File::directories($modulesPath) as $moduleDir) {
            $providersPath = $moduleDir . '/Providers';

            if (! File::isDirectory($providersPath)) {
                continue;
            }

            foreach (File::glob($providersPath . '/*ServiceProvider.php') as $providerFile) {
                $moduleName = basename($moduleDir);
                $providerClass = basename($providerFile, '.php');
                $fqcn = "App\\Modules\\{$moduleName}\\Providers\\{$providerClass}";

                if (class_exists($fqcn)) {
                    $this->app->register($fqcn);
                }
            }
        }
    }

    public function boot(): void
    {
        //
    }
}
