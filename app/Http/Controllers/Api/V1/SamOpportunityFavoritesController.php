<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SamOpportunityResource;
use App\Models\SamOpportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class SamOpportunityFavoritesController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $favorites = $user
            ->favoriteSamOpportunities()
            ->latest()
            ->paginate(15);

        return SamOpportunityResource::collection($favorites);
    }

    public function store(Request $request, SamOpportunity $samOpportunity): JsonResponse
    {
        $user = $request->user();

        if (! $user->favoriteSamOpportunities()->where('sam_opportunity_id', $samOpportunity->id)->exists()) {
            $user->favoriteSamOpportunities()->attach($samOpportunity->id);
        }

        return response()->json([
            'sam_opportunity_id' => $samOpportunity->id,
            'is_favorite' => true,
        ], Response::HTTP_CREATED);
    }

    public function destroy(Request $request, SamOpportunity $samOpportunity): JsonResponse
    {
        $user = $request->user();

        $user->favoriteSamOpportunities()->detach($samOpportunity->id);

        return response()->json([
            'sam_opportunity_id' => $samOpportunity->id,
            'is_favorite' => false,
        ]);
    }
}
