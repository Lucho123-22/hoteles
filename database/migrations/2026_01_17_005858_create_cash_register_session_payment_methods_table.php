<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_register_session_payment_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('cash_register_session_id');
            $table->uuid('payment_method_id'); // 👈 UUID, NO bigint

            $table->decimal('system_amount', 12, 2)->default(0);
            $table->decimal('counted_amount', 12, 2)->nullable();
            $table->decimal('difference_amount', 12, 2)->nullable();

            $table->timestamps();

            $table->foreign('cash_register_session_id')
                ->references('id')
                ->on('cash_register_sessions')
                ->cascadeOnDelete();

            $table->foreign('payment_method_id')
                ->references('id')
                ->on('payment_methods');

            $table->unique(
                ['cash_register_session_id', 'payment_method_id'],
                'session_payment_method_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_register_session_payment_methods');
    }
};
