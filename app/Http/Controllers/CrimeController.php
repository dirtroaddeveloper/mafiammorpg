<?php

namespace App\Http\Controllers;

use App\Domain\Crimes\CrimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrimeController
{
    public function __construct(private readonly CrimeService $crimeService)
    {
    }

    public function attempt(Request $request, int $crime): JsonResponse
    {
        $playerId = (int) $request->user()->getAuthIdentifier();
        $result = $this->crimeService->attemptCrime($playerId, $crime);

        return response()->json($result, $result['status']);
    }
}
