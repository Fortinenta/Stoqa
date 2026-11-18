<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('inbound_item_id')->nullable()->constrained()->nullOnDelete(); // null untuk batch hasil opname
            $table->unsignedBigInteger('qty_initial');
            $table->unsignedBigInteger('qty_remaining');
            $table->decimal('unit_cost_landed', 15, 4);             // modal per satuan dasar (landed cost)
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id', 'received_at']);
        });
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE stock_batches ADD CONSTRAINT stock_batches_remaining_valid CHECK (qty_remaining >= 0 AND qty_remaining <= qty_initial)');
        }

        // Indeks parsial untuk antrean FIFO (hanya batch yang masih ada isinya) - PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX stock_batches_fifo_idx ON stock_batches (product_id, warehouse_id, received_at, id) WHERE qty_remaining > 0');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
