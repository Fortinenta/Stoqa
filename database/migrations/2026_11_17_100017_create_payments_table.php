<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('method', 20);                           // manual | xendit | midtrans
            $table->string('provider', 20)->nullable();
            $table->string('external_ref')->nullable();             // id transaksi di gateway
            $table->decimal('amount', 15, 2);
            $table->string('status', 20)->default('pending');       // pending | paid | failed | expired
            $table->string('proof_url')->nullable();                // bukti transfer (pembayaran manual)
            $table->json('payload')->nullable();                    // payload webhook
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->unique(['provider', 'external_ref']);           // cegah webhook ganda
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
