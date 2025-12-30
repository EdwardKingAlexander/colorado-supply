<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SamOpportunity;
use App\Services\SamRagRetriever;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SamOpportunityRagController extends Controller
{
    public function __invoke(Request $request, SamOpportunity $samOpportunity, SamRagRetriever $retriever): JsonResponse
    {
        $this->authorizeAccess($request);

        $data = $request->validate([
            'query' => ['required', 'string', 'min:1'],
        ]);

        $result = $retriever->retrieve($samOpportunity, $data['query']);

        return response()->json($result, Response::HTTP_OK);
    }

    protected function authorizeAccess(Request $request): void
    {
        if (! $request->user()) {
            abort(Response::HTTP_UNAUTHORIZED, 'Unauthenticated');
        }
    }
}
