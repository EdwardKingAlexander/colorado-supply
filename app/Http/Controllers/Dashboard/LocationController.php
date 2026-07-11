<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ArchiveLocationRequest;
use App\Http\Requests\Dashboard\StoreLocationRequest;
use App\Http\Requests\Dashboard\UpdateLocationRequest;
use App\Models\Location;
use App\Services\Organizations\LocationManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LocationController extends Controller
{
    public function __construct(private readonly LocationManagementService $locations) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user('web');
        Gate::authorize('viewAny', Location::class);

        $locations = $user->company->allLocations()
            ->with('parent:id,name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $locations->map(fn (Location $location) => $this->serialize($location)),
        ]);
    }

    public function store(StoreLocationRequest $request): JsonResponse
    {
        $location = $this->locations->create($request->user('web')->company, $request->validated());

        return response()->json(['data' => $this->serialize($location)], 201);
    }

    public function update(UpdateLocationRequest $request, Location $location): JsonResponse
    {
        $location = $this->locations->update($location, $request->validated());

        return response()->json(['data' => $this->serialize($location)]);
    }

    public function destroy(ArchiveLocationRequest $request, Location $location): JsonResponse
    {
        $this->locations->archive($location, $request->validated('child_strategy'));

        return response()->json(['archived' => true]);
    }

    public function restore(Request $request, Location $location): JsonResponse
    {
        Gate::authorize('restore', $location);
        $location = $this->locations->restore($location);

        return response()->json(['data' => $this->serialize($location)]);
    }

    private function serialize(Location $location): array
    {
        return [
            'id' => $location->getKey(),
            'parent_id' => $location->parent_id,
            'name' => $location->name,
            'slug' => $location->slug,
            'archived_at' => $location->deleted_at?->toISOString(),
        ];
    }
}
