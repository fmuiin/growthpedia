<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Requests\RefundRequest;
use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Modules\Payment\Exceptions\PaymentGatewayException;
use App\Modules\Payment\Models\PaymentTransaction;
use App\Modules\Subscription\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class RefundController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway,
    ) {}

    public function refund(int $subscriptionId, RefundRequest $request): RedirectResponse
    {
        $subscription = Subscription::find($subscriptionId);

        if ($subscription === null) {
            return redirect()->back()
                ->with('error', 'Subscription not found.');
        }

        $transaction = PaymentTransaction::where('subscription_id', $subscriptionId)
            ->where('status', 'success')
            ->where('type', 'charge')
            ->latest('created_at')
            ->first();

        if ($transaction === null) {
            return redirect()->back()
                ->with('error', 'No successful payment transaction found for this subscription.');
        }

        $amountInCents = $request->validated('amount') !== null
            ? (int) round((float) $request->validated('amount') * 100)
            : (int) round((float) $transaction->amount * 100);

        try {
            $result = $this->paymentGateway->refund(
                $transaction->gateway_transaction_id,
                $amountInCents,
            );
        } catch (PaymentGatewayException $e) {
            Log::error('Admin refund gateway error', [
                'subscription_id' => $subscriptionId,
                'transaction_id' => $transaction->gateway_transaction_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Payment gateway error: ' . $e->getMessage());
        }

        if (!$result->success) {
            return redirect()->back()
                ->with('error', 'Refund failed: ' . ($result->errorMessage ?? 'Unknown error.'));
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        Log::info('Admin refund processed successfully', [
            'subscription_id' => $subscriptionId,
            'refund_id' => $result->refundId,
            'amount_cents' => $amountInCents,
            'reason' => $request->validated('reason'),
        ]);

        return redirect()->back()
            ->with('success', 'Refund processed successfully. Subscription has been cancelled.');
    }
}
