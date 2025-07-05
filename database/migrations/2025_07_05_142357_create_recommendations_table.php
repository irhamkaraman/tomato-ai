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
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('maturity_level'); // mentah, setengah_matang, matang, busuk
            $table->string('category'); // storage, handling, use, timeframe
            $table->text('content'); // isi rekomendasi
            $table->integer('order')->default(0); // urutan tampilan
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable(); // deskripsi tambahan
            $table->timestamps();
            
            // Indexes
            $table->index(['maturity_level', 'category']);
            $table->index('is_active');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommendations');
    }
};
