<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tomat_readings', function (Blueprint $table) {
            $table->id();
            $table->string('device_id');
            $table->integer('red_value');
            $table->integer('green_value');
            $table->integer('blue_value');
            $table->integer('clear_value')->nullable();
            $table->float('temperature')->nullable();
            $table->float('humidity')->nullable();
            $table->string('maturity_level')->nullable();
            $table->string('status')->nullable();
            $table->float('confidence_score')->nullable();
            $table->json('recommendations')->nullable();
            $table->json('raw_sensor_data')->nullable();
            $table->json('ml_analysis')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tomat_readings');
    }
};
