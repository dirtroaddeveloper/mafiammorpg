<?php

namespace App\Domain\OC;

use App\Domain\City\CityHeatService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class OrganizedCrimeService
{
    private const REQUIRED_ROLES = [
        'Leader',
        'Driver',
        'Explosives',
        'Lookout',
        'Muscle',
        'Hacker',
    ];

    public function __construct(private readonly CityHeatService $cityHeatService)
    {
    }

    public function create(int $playerId, string $type, string $city): array
    {
        $lockKey = "oc:create:{$playerId}";

        if (!Redis::set($lockKey, '1', 'NX', 'EX', 10)) {
            return [
                'status' => 429,
                'message' => 'OC creation already in progress.',
            ];
        }

        try {
            $ocId = DB::table('organized_crimes')->insertGetId([
                'type' => $type,
                'city' => $city,
                'status' => 'open',
                'required_roles' => json_encode(self::REQUIRED_ROLES),
                'created_by' => $playerId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'status' => 201,
                'oc_id' => $ocId,
                'message' => 'OC created.',
            ];
        } finally {
            Redis::del($lockKey);
        }
    }

    public function join(int $playerId, int $ocId, string $role, float $roleSkill): array
    {
        $lockKey = "oc:join:{$ocId}:{$playerId}";

        if (!Redis::set($lockKey, '1', 'NX', 'EX', 10)) {
            return [
                'status' => 429,
                'message' => 'OC join already in progress.',
            ];
        }

        try {
            return DB::transaction(function () use ($playerId, $ocId, $role, $roleSkill) {
                $oc = DB::table('organized_crimes')->where('id', $ocId)->lockForUpdate()->first();
                if (!$oc || $oc->status !== 'open') {
                    return [
                        'status' => 409,
                        'message' => 'OC is not open.',
                    ];
                }

                $requiredRoles = json_decode($oc->required_roles, true) ?? [];
                if (!in_array($role, $requiredRoles, true)) {
                    return [
                        'status' => 422,
                        'message' => 'Invalid role for this OC.',
                    ];
                }

                $existing = DB::table('oc_participants')
                    ->where('oc_id', $ocId)
                    ->where('player_id', $playerId)
                    ->first();
                if ($existing) {
                    return [
                        'status' => 409,
                        'message' => 'Player already joined.',
                    ];
                }

                $roleTaken = DB::table('oc_participants')
                    ->where('oc_id', $ocId)
                    ->where('role', $role)
                    ->exists();
                if ($roleTaken) {
                    return [
                        'status' => 409,
                        'message' => 'Role already filled.',
                    ];
                }

                DB::table('oc_participants')->insert([
                    'oc_id' => $ocId,
                    'player_id' => $playerId,
                    'role' => $role,
                    'role_skill' => $roleSkill,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return [
                    'status' => 200,
                    'message' => 'Joined OC.',
                ];
            });
        } finally {
            Redis::del($lockKey);
        }
    }

    public function start(int $playerId, int $ocId): array
    {
        $lockKey = "oc:start:{$ocId}";

        if (!Redis::set($lockKey, '1', 'NX', 'EX', 10)) {
            return [
                'status' => 429,
                'message' => 'OC start already in progress.',
            ];
        }

        try {
            return DB::transaction(function () use ($playerId, $ocId) {
                $oc = DB::table('organized_crimes')->where('id', $ocId)->lockForUpdate()->first();
                if (!$oc || $oc->status !== 'open') {
                    return [
                        'status' => 409,
                        'message' => 'OC is not open.',
                    ];
                }

                if ((int) $oc->created_by !== $playerId) {
                    return [
                        'status' => 403,
                        'message' => 'Only the creator can start the OC.',
                    ];
                }

                $participants = DB::table('oc_participants')
                    ->where('oc_id', $ocId)
                    ->get();

                $requiredRoles = json_decode($oc->required_roles, true) ?? [];
                $rolesPresent = $participants->pluck('role')->all();

                foreach ($requiredRoles as $requiredRole) {
                    if (!in_array($requiredRole, $rolesPresent, true)) {
                        return [
                            'status' => 422,
                            'message' => 'Missing required roles.',
                        ];
                    }
                }

                DB::table('organized_crimes')
                    ->where('id', $ocId)
                    ->update([
                        'status' => 'queued',
                        'started_at' => now(),
                        'updated_at' => now(),
                    ]);

                \App\Jobs\ResolveOrganizedCrime::dispatch($ocId);

                return [
                    'status' => 202,
                    'message' => 'OC queued for resolution.',
                ];
            });
        } finally {
            Redis::del($lockKey);
        }
    }

    public function resolve(int $ocId): array
    {
        $lockKey = "oc:resolve:{$ocId}";

        if (!Redis::set($lockKey, '1', 'NX', 'EX', 30)) {
            return [
                'status' => 429,
                'message' => 'OC resolution already in progress.',
            ];
        }

        try {
            return DB::transaction(function () use ($ocId) {
                $oc = DB::table('organized_crimes')->where('id', $ocId)->lockForUpdate()->first();
                if (!$oc || $oc->status !== 'queued') {
                    return [
                        'status' => 409,
                        'message' => 'OC is not queued.',
                    ];
                }

                $participants = DB::table('oc_participants')
                    ->where('oc_id', $ocId)
                    ->get();

                $totalSkill = $participants->sum('role_skill');
                $avgSkill = $participants->count() > 0 ? $totalSkill / $participants->count() : 0.0;

                $penalty = 0.0;
                foreach ($participants as $participant) {
                    if ((float) $participant->role_skill < 40.0) {
                        $penalty += 5.0;
                    }
                }

                $aggregatedChance = max(1.0, min(100.0, $avgSkill - $penalty));
                $heatModifier = $this->cityHeatService->getHeatModifier($oc->city);
                $finalChance = max(1.0, min(100.0, $aggregatedChance + $heatModifier));

                $roll = random_int(1, 100);
                $outcome = $roll <= $finalChance ? 'success' : ($roll <= ($finalChance + 10) ? 'partial' : 'failure');

                DB::table('organized_crimes')
                    ->where('id', $ocId)
                    ->update([
                        'status' => 'resolved',
                        'outcome' => $outcome,
                        'heat_modifier_snapshot' => $heatModifier,
                        'resolved_at' => now(),
                        'updated_at' => now(),
                    ]);

                return [
                    'status' => 200,
                    'message' => 'OC resolved.',
                    'outcome' => $outcome,
                    'roll' => $roll,
                    'chance' => $finalChance,
                    'aggregated_chance' => $aggregatedChance,
                    'heat_modifier' => $heatModifier,
                ];
            });
        } finally {
            Redis::del($lockKey);
        }
    }
}
