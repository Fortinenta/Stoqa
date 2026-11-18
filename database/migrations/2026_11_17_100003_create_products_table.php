<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 64)->unique();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('base_unit', 20);                        // satuan dasar, mis. pcs
            $table->unsignedBigInteger('min_stock')->default(0);    // ambang peringatan (satuan dasar)
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_price_public')->default(false);   // harga tampil ke pengunjung tanpa login
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
