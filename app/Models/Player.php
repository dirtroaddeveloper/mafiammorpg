<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Player extends Model
{
    use HasApiTokens;
    use HasFactory;

    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'vip_tier',
        'gold_balance',
        'level',
        'rank',
        'experience_percent',
        'days_played',
        'current_city',
        'family_id',
        'marriage_id',
        'kills',
        'deaths',
        'crimes_committed',
        'oc_participation',
        'bounty_collected',
    ];

    protected $hidden = [
        'password_hash',
    ];
}
