<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crimes', function (Blueprint $table) {
            $table->id();
            $table->string('crime_tier');
            $table->unsignedBigInteger('base_reward');
            $table->decimal('success_percentage', 5, 2)->default(50.00);
            $table->unsignedInteger('cooldown_seconds');
            $table->unsignedInteger('heat_generated')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crimes');
    }
};
