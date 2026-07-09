<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Paypal\PaypalCaptureService;
use App\Services\Paypal\PaypalPaymentSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class StorePaypalReturnController extends Controller
{
    public function __construct(
        private PaypalCaptureService $capture,
        private PaypalPaymentSyncService $paymentSync,
    ) {}

    public function return(Request $request, Order $order): RedirectResponse
    {
        $user = Auth::guard('web')->user() ?? Auth::guard('admin')->user();

        if (! $user) {
            abort(401);
        }

        if ($order->portal_user_id !== null && $order->portal_user_id !== $user->id) {
            abort(403);
        }

        $token = $request->query('token');

        $payment = $order->payments()
            ->where('gateway', 'paypal')
            ->where('status', PaymentStatus::Pending)
            ->where('gateway_session_id', $token)
            ->latest()
            ->first();

        if (! $payment) {
            return redirect()->route('store.checkout.cancel', ['order' => $order->id])
                ->with('error', 'We could not find a matching PayPal payment for this order.');
        }

        try {
            $captureId = $this->capture->captureOrder($payment);
        } catch (RuntimeException $e) {
            return redirect()->route('store.checkout.cancel', ['order' => $order->id])
                ->with('error', $e->getMessage());
        }

        $this->paymentSync->markPaymentAndOrderPaid($payment, $captureId);

        return redirect()->route('store.checkout.success', ['order' => $order->id]);
    }
}
