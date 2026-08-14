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
        Schema::create('payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('order_id')->index()->constrained('orders')->cascadeOnDelete();
            $table->string('gateway_payment_id')->nullable()->unique();
            $table->string('status')->default('created'); // created, processing, succeeded, failed
            $table->string('payment_method')->nullable(); // bank_card, sbp, yoomoney
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
