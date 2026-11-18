<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbound_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained();
            $table->date('received_date');
            $table->decimal('shipping_cost', 15, 2)->default(0);    // ongkir masuk
            $table->decimal('other_cost', 15, 2)->default(0);       // bongkar muat, dll.
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('received_date');
        });

        Schema::create('inbound_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbound_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->unsignedBigInteger('qty_base');                 // jumlah dalam satuan dasar
            $table->decimal('unit_price', 15, 2);                   // harga beli per satuan dasar
            $table->decimal('allocated_cost', 15, 2)->default(0);   // porsi ongkir + biaya lain untuk item ini
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_items');
        Schema::dropIfExists('inbound_orders');
    }
};
