<?php

namespace App\Http\Controllers;

use App\Support\PushDevices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A buyer's devices with push notifications turned on
 * (ai/modules/will-call-notifications, phase 5). Admins manage theirs on the
 * My notifications page instead.
 */
class PushSubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['devices' => PushDevices::list($request->user())]);
    }

    public function store(Request $request): JsonResponse
    {
        PushDevices::save($request->user(), PushDevices::validate($request->all()));

        return response()->json(['devices' => PushDevices::list($request->user())], 201);
    }

    /**
     * Remove one device by id (device list) or by endpoint ("turn off on
     * this device", where the browser only knows its endpoint).
     */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'required_without:endpoint'],
            'endpoint' => ['nullable', 'string', 'max:500', 'required_without:id'],
        ]);

        PushDevices::remove($request->user(), $data['id'] ?? null, $data['endpoint'] ?? null);

        return response()->json(['devices' => PushDevices::list($request->user())]);
    }
}
