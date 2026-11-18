<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('product_units')->cascadeOnDelete();
            $table->unsignedInteger('min_qty')->default(1);
            $table->decimal('price', 15, 2);                        // harga khusus pelanggan langganan
            $table->date('valid_from')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'product_id', 'unit_id', 'min_qty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_prices');
    }
};
