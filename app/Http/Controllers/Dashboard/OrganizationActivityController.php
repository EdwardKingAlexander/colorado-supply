<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Services\Organizations\OrganizationActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrganizationActivityController extends Controller
{
    public function __invoke(Request $request, OrganizationActivityService $activities): JsonResponse
    {
        Gate::authorize('viewAny', Location::class);
        $user = $request->user('web');

        return response()->json(
            $activities->forCompany($user->company, $request->integer('per_page', 25)),
        );
    }
}
