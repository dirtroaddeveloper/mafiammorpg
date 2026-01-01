<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('player_crimes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('crime_id');
            $table->decimal('success_percentage', 5, 2)->default(10.00);
            $table->timestamps();

            $table->foreign('player_id')->references('id')->on('players')->cascadeOnDelete();
            $table->foreign('crime_id')->references('id')->on('crimes')->cascadeOnDelete();
            $table->unique(['player_id', 'crime_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_crimes');
    }
};
