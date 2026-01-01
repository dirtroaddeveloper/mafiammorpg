<?php

namespace App\Http\Controllers;

use App\Domain\GTA\VehicleTheftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleTheftController
{
    public function __construct(private readonly VehicleTheftService $vehicleTheftService)
    {
    }

    public function attempt(Request $request, int $vehicle): JsonResponse
    {
        $player = $request->user();
        $result = $this->vehicleTheftService->attemptTheft($player->getKey(), $vehicle);

        return response()->json($result, $result['status']);
    }
}
