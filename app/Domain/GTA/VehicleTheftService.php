<?php

namespace App\Domain\GTA;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class VehicleTheftService
{
    public function attemptTheft(int $playerId, int $vehicleId): array
    {
        $lockKey = "gta:lock:{$playerId}:{$vehicleId}";
        $cooldownKey = "gta:cooldown:{$playerId}";

        if (!Redis::set($lockKey, '1', 'NX', 'EX', 10)) {
            return [
                'status' => 429,
                'message' => 'Vehicle theft already in progress.',
            ];
        }

        try {
            if (Redis::exists($cooldownKey)) {
                $ttl = Redis::ttl($cooldownKey);
                return [
                    'status' => 429,
                    'message' => 'Vehicle theft on cooldown.',
                    'cooldown_seconds' => max($ttl, 0),
                ];
            }

            return DB::transaction(function () use ($playerId, $vehicleId, $cooldownKey) {
                $player = DB::table('players')->where('id', $playerId)->lockForUpdate()->first();
                if (!$player) {
                    return [
                        'status' => 404,
                        'message' => 'Player not found.',
                    ];
                }

                $vehicle = DB::table('vehicles')->where('id', $vehicleId)->lockForUpdate()->first();
                if (!$vehicle) {
                    return [
                        'status' => 404,
                        'message' => 'Vehicle not found.',
                    ];
                }

                $chance = (float) $vehicle->success_percentage;
                $roll = random_int(1, 100);
                $success = $roll <= $chance;

                if ($success) {
                    DB::table('player_vehicles')->insert([
                        'player_id' => $playerId,
                        'vehicle_id' => $vehicleId,
                        'origin_city' => $player->current_city,
                        'current_city' => $player->current_city,
                        'damage_percent' => 0,
                        'armored' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $newChance = min(100.0, $chance + 0.5);
                } else {
                    $newChance = max(1.0, $chance - 1.0);
                }

                DB::table('vehicles')
                    ->where('id', $vehicleId)
                    ->update([
                        'success_percentage' => $newChance,
                        'updated_at' => now(),
                    ]);

                Redis::setex($cooldownKey, (int) $vehicle->cooldown_seconds, '1');

                return [
                    'status' => 200,
                    'message' => $success ? 'Vehicle stolen.' : 'Vehicle theft failed.',
                    'success' => $success,
                    'roll' => $roll,
                    'chance' => $chance,
                    'cooldown_seconds' => (int) $vehicle->cooldown_seconds,
                ];
            });
        } finally {
            Redis::del($lockKey);
        }
    }
}
