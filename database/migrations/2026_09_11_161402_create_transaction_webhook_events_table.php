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
        Schema::create('transaction_webhook_events', function (Blueprint $table) {
            $table->id()->primary();

            $table->string('gateway_payment_id');
            $table->string('event_type');

            $table->foreignUlid('transaction_id')->index()->constrained('transactions');
            $table->string('processing_status', 16)->default('received');
            $table->json('raw_payload')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->unique(['gateway_payment_id', 'event_type']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_webhook_events');
    }
};
