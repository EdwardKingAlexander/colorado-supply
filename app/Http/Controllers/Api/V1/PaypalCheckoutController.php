<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Paypal\PaypalCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PaypalCheckoutController extends Controller
{
    public function __construct(private PaypalCheckoutService $checkout) {}

    public function store(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if ($order->portal_user_id !== null && $order->portal_user_id !== $user->id) {
            abort(403);
        }

        try {
            $result = $this->checkout->createOrderForOrder($order);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'order' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'approve_url' => $result['approve_url'],
        ]);
    }
}
