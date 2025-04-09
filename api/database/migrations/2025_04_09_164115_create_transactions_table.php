<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('origin_account_id')->nullable()->constrained('accounts');
            $table->foreignId('destination_account_id')->nullable()->constrained('accounts');
            $table->decimal('amount', 12, 2);
            $table->enum('type', ['transfer', 'deposit', 'withdraw']);
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
