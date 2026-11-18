<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->string('type', 20);                             // IN, OUT, ADJUST
            $table->bigInteger('qty_base');                         // bertanda: + masuk, - keluar
            $table->string('ref_type', 40)->nullable();             // inbound_order, order, opname
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();          // append-only: tanpa updated_at

            $table->index(['product_id', 'warehouse_id', 'created_at']);
            $table->index(['ref_type', 'ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
