<?php

namespace App\Domain\Crimes;

use App\Domain\City\CityHeatService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class CrimeService
{
    public function __construct(private readonly CityHeatService $cityHeatService)
    {
    }

    public function attemptCrime(int $playerId, int $crimeId): array
    {
        $lockKey = "crimes:lock:{$playerId}:{$crimeId}";
        $cooldownKey = "crimes:cooldown:{$playerId}:{$crimeId}";

        if (!Redis::set($lockKey, '1', 'NX', 'EX', 10)) {
            return [
                'status' => 429,
                'message' => 'Crime attempt already in progress.',
            ];
        }

        try {
            if (Redis::exists($cooldownKey)) {
                $ttl = Redis::ttl($cooldownKey);
                return [
                    'status' => 429,
                    'message' => 'Crime on cooldown.',
                    'cooldown_seconds' => max($ttl, 0),
                ];
            }

            return DB::transaction(function () use ($playerId, $crimeId, $cooldownKey) {
                $crime = DB::table('crimes')->where('id', $crimeId)->lockForUpdate()->first();
                if (!$crime) {
                    return [
                        'status' => 404,
                        'message' => 'Crime not found.',
                    ];
                }

                $player = DB::table('players')->where('id', $playerId)->lockForUpdate()->first();
                if (!$player) {
                    return [
                        'status' => 404,
                        'message' => 'Player not found.',
                    ];
                }

                $baseChance = (float) $crime->success_percentage;
                $heatModifier = $this->cityHeatService->getHeatModifier($player->current_city);
                $finalChance = max(1.0, min(100.0, $baseChance + $heatModifier));

                $roll = random_int(1, 100);
                $success = $roll <= $finalChance;

                if ($success) {
                    DB::table('players')
                        ->where('id', $playerId)
                        ->update([
                            'gold_balance' => DB::raw('gold_balance + ' . (int) $crime->base_reward),
                            'crimes_committed' => DB::raw('crimes_committed + 1'),
                            'updated_at' => now(),
                        ]);

                    $newChance = min(100.0, $baseChance + 0.5);
                } else {
                    DB::table('players')
                        ->where('id', $playerId)
                        ->update([
                            'crimes_committed' => DB::raw('crimes_committed + 1'),
                            'updated_at' => now(),
                        ]);

                    $newChance = max(1.0, $baseChance - 1.0);
                }

                DB::table('crimes')
                    ->where('id', $crime->id)
                    ->update([
                        'success_percentage' => $newChance,
                        'updated_at' => now(),
                    ]);

                Redis::setex($cooldownKey, (int) $crime->cooldown_seconds, '1');

                return [
                    'status' => 200,
                    'message' => $success ? 'Crime succeeded.' : 'Crime failed.',
                    'success' => $success,
                    'roll' => $roll,
                    'chance' => $finalChance,
                    'base_chance' => $baseChance,
                    'heat_modifier' => $heatModifier,
                    'cooldown_seconds' => (int) $crime->cooldown_seconds,
                ];
            });
        } finally {
            Redis::del($lockKey);
        }
    }
}
