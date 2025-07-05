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
        Schema::create('decision_tree_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_name')->comment('Nama aturan decision tree');
            $table->string('node_type')->comment('Tipe node: condition, leaf');
            $table->integer('node_order')->default(0)->comment('Urutan evaluasi node');
            $table->string('condition_field')->nullable()->comment('Field yang dievaluasi: red, green, blue, ratio_red_green, dll');
            $table->string('condition_operator')->nullable()->comment('Operator: >, <, >=, <=, ==');
            $table->decimal('condition_value', 8, 2)->nullable()->comment('Nilai threshold untuk kondisi');
            $table->string('true_action')->nullable()->comment('Aksi jika kondisi true: next_node, classify');
            $table->string('false_action')->nullable()->comment('Aksi jika kondisi false: next_node, classify');
            $table->string('true_result')->nullable()->comment('Hasil jika true: node_id atau kelas kematangan');
            $table->string('false_result')->nullable()->comment('Hasil jika false: node_id atau kelas kematangan');
            $table->enum('maturity_class', ['mentah', 'setengah_matang', 'matang', 'busuk'])->nullable()->comment('Kelas kematangan untuk leaf node');
            $table->text('description')->nullable()->comment('Deskripsi aturan');
            $table->boolean('is_active')->default(true)->comment('Status aktif aturan');
            $table->timestamps();
            
            $table->index(['node_order', 'is_active']);
            $table->index(['rule_name', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decision_tree_rules');
    }
};
