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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number', 30)->unique();
            $table->foreignId('admin_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name', 100)->nullable();
            $table->string('customer_phone', 20)->nullable();

            // Financial snapshot
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('change_due', 10, 2)->default(0);

            $table->string('payment_method', 20)->default('cash');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('sale_number');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
