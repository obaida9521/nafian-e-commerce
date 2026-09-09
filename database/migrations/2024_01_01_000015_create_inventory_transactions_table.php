<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->enum('type', [
                'purchase', 'sale', 'return', 'adjustment', 'reservation', 'reservation_release',
            ]);
            $table->integer('quantity_change'); // positive = add, negative = deduct
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reason', 255)->nullable();
            $table->string('created_by_type', 50)->nullable(); // 'admin' or 'system'
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('variant_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
