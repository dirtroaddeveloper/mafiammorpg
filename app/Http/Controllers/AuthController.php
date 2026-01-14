<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController
{
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:32', 'alpha_dash', 'unique:players,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:players,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $player = Player::create([
            'username' => $validator->validated()['username'],
            'email' => $validator->validated()['email'],
            'password_hash' => Hash::make($validator->validated()['password']),
        ]);

        $token = $player->createToken('auth')->plainTextToken;

        return response()->json([
            'token' => $token,
            'player' => $player,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $player = Player::where('username', $validator->validated()['username'])->first();

        if (!$player || !Hash::check($validator->validated()['password'], $player->password_hash)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $player->forceFill(['last_login_at' => now()])->save();

        $token = $player->createToken('auth')->plainTextToken;

        return response()->json([
            'token' => $token,
            'player' => $player,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }
}
