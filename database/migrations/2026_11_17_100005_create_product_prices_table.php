<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('product_units')->cascadeOnDelete();
            $table->unsignedInteger('min_qty')->default(1);         // jumlah minimum (dalam satuan unit_id)
            $table->decimal('price', 15, 2);                        // harga per satuan unit_id
            $table->string('tier_name', 50)->nullable();            // Ecer / Pack / Grosir
            $table->date('valid_from')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'unit_id', 'min_qty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
