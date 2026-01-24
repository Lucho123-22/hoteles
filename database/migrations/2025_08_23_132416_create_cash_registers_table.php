<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sub_branch_id');

            $table->string('name');
            $table->boolean('is_active')->default(true);

            $table->uuid('current_session_id')->nullable();

            // Auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Relaciones
            $table->foreign('sub_branch_id')
                ->references('id')
                ->on('sub_branches')
                ->cascadeOnDelete();

            $table->index(['sub_branch_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};
