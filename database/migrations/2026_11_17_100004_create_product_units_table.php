<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('unit_name', 20);                        // pcs, pack, karton
            $table->unsignedInteger('conversion_to_base');          // pcs=1, pack=12, karton=120
            $table->timestamps();

            $table->unique(['product_id', 'unit_name']);
        });
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE product_units ADD CONSTRAINT product_units_conversion_positive CHECK (conversion_to_base >= 1)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_units');
    }
};
