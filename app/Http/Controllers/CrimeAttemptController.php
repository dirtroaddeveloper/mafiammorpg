<?php

namespace App\Http\Controllers;

use App\Domain\Crimes\CrimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrimeAttemptController
{
    public function __construct(private readonly CrimeService $crimeService)
    {
    }

    public function attempt(Request $request, int $crime): JsonResponse
    {
        $player = $request->user();
        $result = $this->crimeService->attemptCrime($player->getKey(), $crime);

        return response()->json($result, $result['status']);
    }
}
