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
        Schema::create('training_data', function (Blueprint $table) {
            $table->id();
            $table->integer('red_value')->comment('Nilai RGB merah (0-255)');
            $table->integer('green_value')->comment('Nilai RGB hijau (0-255)');
            $table->integer('blue_value')->comment('Nilai RGB biru (0-255)');
            $table->enum('maturity_class', ['mentah', 'setengah_matang', 'matang', 'busuk'])->comment('Kelas kematangan tomat');
            $table->text('description')->nullable()->comment('Deskripsi tambahan');
            $table->boolean('is_active')->default(true)->comment('Status aktif untuk training');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_data');
    }
};
