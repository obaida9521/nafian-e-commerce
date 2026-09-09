<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variant_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained('attribute_definitions');
            $table->string('value', 100);
            $table->timestamps();

            $table->unique(['variant_id', 'attribute_id']);
            $table->index('variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_attribute_values');
    }
};
