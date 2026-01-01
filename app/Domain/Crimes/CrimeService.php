<?php

namespace App\Domain\Crimes;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class CrimeService
{
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

            $crime = DB::table('crimes')->where('id', $crimeId)->first();
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

            $result = DB::transaction(function () use ($playerId, $crime, $cooldownKey) {
                $chance = (float) $crime->success_percentage;
                $roll = random_int(1, 100);
                $success = $roll <= $chance;

                if ($success) {
                    DB::table('players')
                        ->where('id', $playerId)
                        ->update([
                            'gold_balance' => DB::raw('gold_balance + ' . (int) $crime->base_reward),
                            'crimes_committed' => DB::raw('crimes_committed + 1'),
                            'updated_at' => now(),
                        ]);

                    $newChance = min(100, $chance + 0.5);
                } else {
                    DB::table('players')
                        ->where('id', $playerId)
                        ->update([
                            'crimes_committed' => DB::raw('crimes_committed + 1'),
                            'updated_at' => now(),
                        ]);

                    $newChance = max(1, $chance - 1.0);
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
                    'chance' => $chance,
                    'cooldown_seconds' => (int) $crime->cooldown_seconds,
                ];
            });

            return $result;
        } finally {
            Redis::del($lockKey);
        }
    }
}
