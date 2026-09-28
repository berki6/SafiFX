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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('status')->default('payment_submitted');

            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('amount_sent', 14, 2);
            $table->decimal('exchange_rate', 14, 6);
            $table->decimal('market_rate', 14, 6)->nullable();
            $table->decimal('fee', 12, 2)->default(0);
            $table->decimal('total_paid', 14, 2);
            $table->decimal('recipient_amount', 14, 2);

            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->string('recipient_network');

            $table->string('payment_reference');
            $table->string('payout_reference')->nullable();
            $table->foreignId('processed_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('payment_verified_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index(['from_currency', 'to_currency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
