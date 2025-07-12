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
        Schema::create('classifications', function (Blueprint $table) {
            $table->id();
            $table->integer('red_value')->comment('Nilai RGB Merah (0-255)');
            $table->integer('green_value')->comment('Nilai RGB Hijau (0-255)');
            $table->integer('blue_value')->comment('Nilai RGB Biru (0-255)');
            $table->integer('clear_value')->comment('Nilai Clear dari sensor TCS34725');
            $table->enum('actual_status', ['Mentah', 'Setengah Matang', 'Matang', 'Busuk'])
                  ->comment('Status kematangan tomat yang sebenarnya');
            $table->enum('predicted_status', ['Mentah', 'Setengah Matang', 'Matang', 'Busuk'])
                  ->comment('Status yang diprediksi oleh AI');
            $table->enum('classification_result', ['Benar', 'Salah'])
                  ->comment('Apakah prediksi AI benar atau salah');
            $table->text('notes')->nullable()->comment('Catatan tambahan');
            $table->string('device_id')->nullable()->comment('ID perangkat yang melakukan pengukuran');
            $table->boolean('is_verified')->default(false)->comment('Apakah data sudah diverifikasi');
            $table->timestamps();
            
            // Index untuk pencarian yang lebih cepat
            $table->index(['classification_result']);
            $table->index(['actual_status']);
            $table->index(['predicted_status']);
            $table->index(['is_verified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classifications');
    }
};