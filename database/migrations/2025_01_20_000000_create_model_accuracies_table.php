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
        Schema::create('model_accuracies', function (Blueprint $table) {
            $table->id();
            $table->string('algorithm')->unique(); // decision_tree, knn, random_forest, ensemble
            $table->decimal('accuracy', 5, 2); // Akurasi dalam persen (0.00 - 100.00)
            $table->integer('data_count'); // Jumlah data training saat evaluasi
            $table->timestamp('calculated_at'); // Kapan akurasi dihitung
            $table->json('confusion_matrix')->nullable(); // Confusion matrix untuk analisis detail
            $table->json('detailed_metrics')->nullable(); // Precision, recall, F1-score per kelas
            $table->text('notes')->nullable(); // Catatan tambahan
            $table->timestamps();
            
            // Index untuk performa query
            $table->index(['algorithm', 'calculated_at']);
            $table->index('calculated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_accuracies');
    }
};