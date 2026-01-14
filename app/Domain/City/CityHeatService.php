<?php

namespace App\Domain\City;

use Illuminate\Support\Facades\DB;

class CityHeatService
{
    public function getHeatModifier(string $city): float
    {
        $heat = DB::table('city_heat')
            ->where('city', $city)
            ->value('heat_modifier');

        return $heat !== null ? (float) $heat : 0.0;
    }
}
