<?php

namespace App\Http\Controllers;

use App\Domain\OC\OrganizedCrimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrganizedCrimeController
{
    public function __construct(private readonly OrganizedCrimeService $organizedCrimeService)
    {
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', 'string', 'max:64'],
            'city' => ['required', 'string', 'max:64'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $player = $request->user();
        $result = $this->organizedCrimeService->create(
            $player->getKey(),
            $validator->validated()['type'],
            $validator->validated()['city']
        );

        return response()->json($result, $result['status']);
    }

    public function join(Request $request, int $oc): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role' => ['required', 'string', 'max:32'],
            'role_skill' => ['required', 'numeric', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $player = $request->user();
        $result = $this->organizedCrimeService->join(
            $player->getKey(),
            $oc,
            $validator->validated()['role'],
            (float) $validator->validated()['role_skill']
        );

        return response()->json($result, $result['status']);
    }

    public function start(Request $request, int $oc): JsonResponse
    {
        $player = $request->user();
        $result = $this->organizedCrimeService->start($player->getKey(), $oc);

        return response()->json($result, $result['status']);
    }
}
