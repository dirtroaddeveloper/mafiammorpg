<?php

namespace App\Domain\Crimes;

use App\Domain\City\CityHeatService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class CrimeService
{
    private const SUCCESS_HEAT_LEVEL_INCREASE = 1;
    private const FAILURE_HEAT_LEVEL_INCREASE = 3;
    private const SUCCESS_HEAT_MODIFIER_INCREASE = -0.5;
    private const FAILURE_HEAT_MODIFIER_INCREASE = -1.5;
    private const MAX_FINAL_CHANCE = 95.0;

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

                $playerCrime = DB::table('player_crimes')
                    ->where('player_id', $playerId)
                    ->where('crime_id', $crimeId)
                    ->lockForUpdate()
                    ->first();

                if (!$playerCrime) {
                    DB::table('player_crimes')->insert([
                        'player_id' => $playerId,
                        'crime_id' => $crimeId,
                        'success_percentage' => (float) $crime->success_percentage,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $playerCrime = (object) [
                        'success_percentage' => (float) $crime->success_percentage,
                    ];
                }

                $baseChance = (float) $playerCrime->success_percentage;
                $heatModifier = $this->cityHeatService->getHeatModifier($player->current_city);
                $finalChance = max(1.0, min(self::MAX_FINAL_CHANCE, $baseChance + $heatModifier));

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
                    $heatLevelIncrease = self::SUCCESS_HEAT_LEVEL_INCREASE;
                    $heatModifierIncrease = self::SUCCESS_HEAT_MODIFIER_INCREASE;
                } else {
                    DB::table('players')
                        ->where('id', $playerId)
                        ->update([
                            'crimes_committed' => DB::raw('crimes_committed + 1'),
                            'updated_at' => now(),
                        ]);

                    $newChance = max(1.0, $baseChance - 1.0);
                    $heatLevelIncrease = self::FAILURE_HEAT_LEVEL_INCREASE;
                    $heatModifierIncrease = self::FAILURE_HEAT_MODIFIER_INCREASE;
                }

                DB::table('player_crimes')
                    ->where('player_id', $playerId)
                    ->where('crime_id', $crimeId)
                    ->update([
                        'success_percentage' => $newChance,
                        'updated_at' => now(),
                    ]);

                $cityHeat = DB::table('city_heat')
                    ->where('city', $player->current_city)
                    ->lockForUpdate()
                    ->first();

                if (!$cityHeat) {
                    DB::table('city_heat')->insert([
                        'city' => $player->current_city,
                        'heat_level' => $heatLevelIncrease,
                        'heat_modifier' => $heatModifierIncrease,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('city_heat')
                        ->where('city', $player->current_city)
                        ->update([
                            'heat_level' => DB::raw('heat_level + ' . $heatLevelIncrease),
                            'heat_modifier' => DB::raw('heat_modifier + ' . $heatModifierIncrease),
                            'updated_at' => now(),
                        ]);
                }

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
