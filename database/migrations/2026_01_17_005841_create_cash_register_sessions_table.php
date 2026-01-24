<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_register_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cash_register_id');

            $table->enum('status', ['abierta', 'cerrada', 'bloqueada'])->default('abierta');

            $table->unsignedBigInteger('opened_by');
            $table->unsignedBigInteger('closed_by')->nullable();

            $table->decimal('opening_amount', 12, 2)->default(0);

            // Totales generales
            $table->decimal('system_total_amount', 12, 2)->default(0);
            $table->decimal('counted_total_amount', 12, 2)->nullable();
            $table->decimal('difference_amount', 12, 2)->nullable();

            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();

            // Auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Relaciones
            $table->foreign('cash_register_id')
                ->references('id')
                ->on('cash_registers')
                ->cascadeOnDelete();

            $table->index(['cash_register_id', 'status']);
            $table->index(['opened_by', 'closed_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_register_sessions');
    }
};
