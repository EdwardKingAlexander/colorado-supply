<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Order;
use App\Services\Stripe\StripeCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StripeCheckoutController extends Controller
{
    public function __construct(private StripeCheckoutService $checkout) {}

    public function store(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof Admin && $order->portal_user_id !== null && $order->portal_user_id !== $user->id) {
            abort(403);
        }

        try {
            $session = $this->checkout->createSessionForOrder($order);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'order' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'checkout_url' => $session->url,
        ]);
    }
}
