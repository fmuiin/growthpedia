<?php

declare(strict_types=1);

namespace App\Modules\Payment\Providers;

use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Services\StripePaymentGateway;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, StripePaymentGateway::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
    }
}
