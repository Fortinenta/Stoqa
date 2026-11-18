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
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 20)->default('catalog');      // catalog | manual
            $table->string('customer_name')->nullable();            // untuk penjualan manual tanpa akun
            $table->foreignId('warehouse_id')->constrained();
            // MENUNGGU_ONGKIR, PENDING_PAYMENT, PAID, COMPLETED, REJECTED, CANCELLED, EXPIRED
            $table->string('status', 30);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);    // ongkir yang ditagihkan ke pembeli (diisi admin)
            $table->decimal('other_cost', 15, 2)->default(0);       // biaya lain yang ditagihkan
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('cogs_total', 15, 2)->nullable();       // HPP, diisi saat barang keluar
            $table->string('payment_status', 20)->default('unpaid');
            $table->string('invoice_no', 40)->nullable()->unique(); // INV/2026/11/0001, dibuat saat invoice terbit
            $table->timestamp('shipping_due_at')->nullable();       // batas admin mengisi ongkir / menolak (3 jam)
            $table->timestamp('invoiced_at')->nullable();
            $table->timestamp('expires_at')->nullable();            // batas bayar (12 jam sejak invoice)
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('user_id');
            $table->index('shipping_due_at');
            $table->index('expires_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
