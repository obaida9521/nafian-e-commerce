<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_email', 150)->nullable();
            $table->string('guest_phone', 20)->nullable();
            $table->enum('status', [
                'pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded',
            ])->default('pending');

            // Address snapshot (copied at order time, NOT FK)
            $table->string('shipping_name', 100);
            $table->string('shipping_phone', 20);
            $table->string('shipping_address', 255);
            $table->string('shipping_city', 100);
            $table->string('shipping_district', 100);

            // Financial snapshot
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('delivery_charge', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);

            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('coupon_code', 50)->nullable();

            $table->enum('payment_method', ['cod', 'online']);
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('cancelled_reason')->nullable();

            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index('order_number');
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
