<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('booking_consumptions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Puede ser NULL o un UUID válido
            $table->foreignUuid('booking_id')
                  ->nullable()
                  ->constrained('bookings')
                  ->nullOnDelete();

            $table->foreignUuid('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete();

            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->dateTime('consumed_at');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'paid'])->default('pending');

            // Auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['booking_id', 'deleted_at']);
            $table->index(['consumed_at', 'deleted_at']);
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('booking_consumptions');
    }
};