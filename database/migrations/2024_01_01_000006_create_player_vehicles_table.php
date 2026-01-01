<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('player_vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->string('origin_city');
            $table->string('current_city');
            $table->unsignedTinyInteger('damage_percent')->default(0);
            $table->boolean('armored')->default(false);
            $table->timestamps();

            $table->foreign('player_id')->references('id')->on('players')->cascadeOnDelete();
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_vehicles');
    }
};
