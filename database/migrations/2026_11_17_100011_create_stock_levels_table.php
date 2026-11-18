<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('on_hand')->default(0);              // stok fisik
            $table->bigInteger('reserved')->default(0);             // stok yang di-hold
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id']);
        });
        // available = on_hand - reserved; tidak pernah minus
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE stock_levels ADD CONSTRAINT stock_levels_valid CHECK (on_hand >= 0 AND reserved >= 0 AND reserved <= on_hand)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_levels');
    }
};
