<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organized_crimes', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('city');
            $table->string('status')->default('open');
            $table->json('required_roles');
            $table->string('outcome')->nullable();
            $table->decimal('heat_modifier_snapshot', 5, 2)->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('players')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organized_crimes');
    }
};
