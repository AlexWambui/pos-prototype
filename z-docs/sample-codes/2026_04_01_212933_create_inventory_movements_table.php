<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('type'); // sale, restock, adjustment, return, waste
            $table->integer('quantity_change'); // Positive for in, negative for out
            $table->text('reason')->nullable(); // "stock take", "damaged"
            $table->string('reference_type')->nullable(); // "App\Models\|Sale"
            $table->foreignId('reference_id')->nullable(); // Links to purchese_order_id, sale_id
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at');
            $table->index(['product_id', 'reference_type', 'reference_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
