<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->unsignedBigInteger('qty_base');
            $table->string('status', 20)->default('ACTIVE');        // ACTIVE | CONSUMED | RELEASED | EXPIRED
            $table->timestamp('expires_at');                        // awal = shipping_due_at, diperbarui saat invoice terbit
            $table->timestamps();

            $table->index(['status', 'expires_at']);                // dipakai job kedaluwarsa
            $table->index('order_id');
            $table->index(['product_id', 'warehouse_id', 'status']);
        });
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE stock_reservations ADD CONSTRAINT stock_reservations_qty_positive CHECK (qty_base > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
