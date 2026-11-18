<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('unit_id')->constrained('product_units');
            $table->unsignedInteger('qty');                         // jumlah dalam satuan yang dipilih
            $table->unsignedBigInteger('qty_base');                 // qty x konversi
            $table->decimal('unit_price', 15, 2);                   // harga per satuan terpilih saat order (snapshot)
            $table->string('price_source', 20)->default('tier');    // tier | customer | manual
            $table->decimal('line_total', 15, 2);
            $table->decimal('cogs_total', 15, 2)->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
        });

        // Jejak batch FIFO yang terpakai tiap item (untuk audit HPP)
        Schema::create('order_item_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_batch_id')->constrained();
            $table->unsignedBigInteger('qty_base');
            $table->decimal('unit_cost', 15, 4);
            $table->timestamps();

            $table->index('order_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_batches');
        Schema::dropIfExists('order_items');
    }
};
