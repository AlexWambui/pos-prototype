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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->timestamp("opened_at");
            $table->timestamp("closed_at")->nullable();
            $table->decimal("opening_cash", 12, 2)->nullable();
            $table->decimal("closing_cash", 12, 2)->nullable();
            $table->foreignId("user_id")->constrained()->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'opened_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
