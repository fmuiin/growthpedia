<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions');
            $table->string('gateway_transaction_id', 255);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('IDR');
            $table->enum('status', ['success', 'failed', 'refunded', 'pending']);
            $table->enum('type', ['charge', 'renewal', 'refund', 'proration']);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
