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
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable();
            $table->decimal('balance_after', 15, 2);
            $table->foreignId('sender_wallet_id')->nullable()->constrained('wallets');
            $table->foreignId('receiver_wallet_id')->nullable()->constrained('wallets');
            $table->enum('type', ['deposit', 'withdraw', 'transfer_out', 'transfer_in']);
            $table->timestamps();
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
