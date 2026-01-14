<?php

namespace App\Http\Controllers;

use App\Domain\Crimes\CrimeService;
use App\Http\Requests\CrimeAttemptRequest;
use Illuminate\Http\JsonResponse;

class CrimeAttemptController
{
    public function __construct(private readonly CrimeService $crimeService)
    {
    }

    public function attempt(CrimeAttemptRequest $request, int $crime): JsonResponse
    {
        $player = $request->user();
        $result = $this->crimeService->attemptCrime($player->getKey(), $crime);

        return response()->json($result, $result['status']);
    }
}
