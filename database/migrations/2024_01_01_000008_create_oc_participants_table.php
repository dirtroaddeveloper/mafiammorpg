<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('oc_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('oc_id');
            $table->unsignedBigInteger('player_id');
            $table->string('role');
            $table->decimal('role_skill', 5, 2);
            $table->timestamps();

            $table->foreign('oc_id')->references('id')->on('organized_crimes')->cascadeOnDelete();
            $table->foreign('player_id')->references('id')->on('players')->cascadeOnDelete();
            $table->unique(['oc_id', 'role']);
            $table->unique(['oc_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oc_participants');
    }
};
