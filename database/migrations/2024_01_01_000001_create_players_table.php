<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedTinyInteger('vip_tier')->default(0);
            $table->unsignedBigInteger('gold_balance')->default(0);
            $table->unsignedInteger('level')->default(1);
            $table->string('rank')->default('Rookie');
            $table->unsignedTinyInteger('experience_percent')->default(0);
            $table->unsignedInteger('days_played')->default(0);
            $table->string('current_city')->default('Chicago');
            $table->unsignedBigInteger('family_id')->nullable();
            $table->unsignedBigInteger('marriage_id')->nullable();
            $table->unsignedInteger('kills')->default(0);
            $table->unsignedInteger('deaths')->default(0);
            $table->unsignedInteger('crimes_committed')->default(0);
            $table->unsignedInteger('oc_participation')->default(0);
            $table->unsignedInteger('bounty_collected')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
