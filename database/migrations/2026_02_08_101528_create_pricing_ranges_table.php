<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pricing_ranges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Relaciones principales
            $table->uuid('sub_branch_id')->comment('Sucursal/sede');
            $table->uuid('room_type_id')->comment('Tipo de habitación');
            $table->uuid('rate_type_id')->comment('Tipo de tarifa');
            
            // Rango de tiempo (SOLO para rate_type = "HOURLY")
            $table->integer('time_from_minutes')->nullable()
                  ->comment('Desde cuántos minutos (NULL si no es por horas)');
            
            $table->integer('time_to_minutes')->nullable()
                  ->comment('Hasta cuántos minutos (NULL si no es por horas)');
            
            // EL PRECIO
            $table->decimal('price', 10, 2)->comment('Precio de la tarifa');
            
            // Vigencia (temporadas, promociones)
            $table->date('effective_from')->comment('Fecha de inicio de vigencia');
            $table->date('effective_to')->nullable()->comment('Fecha de fin de vigencia (NULL = sin límite)');
            
            // Estado
            $table->boolean('is_active')->default(true);
            
            // Auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            
            // Foreign keys
            $table->foreign('sub_branch_id', 'fk_pricing_ranges_sub_branch')
                  ->references('id')
                  ->on('sub_branches')
                  ->onDelete('cascade');
            
            $table->foreign('room_type_id', 'fk_pricing_ranges_room_type')
                  ->references('id')
                  ->on('room_types')
                  ->onDelete('cascade');
            
            $table->foreign('rate_type_id', 'fk_pricing_ranges_rate_type')
                  ->references('id')
                  ->on('rate_types')
                  ->onDelete('cascade');
            
            // Índices optimizados para consultas rápidas
            $table->index([
                'sub_branch_id', 
                'room_type_id', 
                'rate_type_id',
                'is_active',
                'deleted_at'
            ], 'idx_pricing_lookup');
            
            $table->index(['time_from_minutes', 'time_to_minutes'], 'idx_pricing_time_range');
            $table->index(['effective_from', 'effective_to'], 'idx_pricing_effective_dates');
            $table->index('created_by', 'idx_pricing_created_by');
            $table->index('updated_by', 'idx_pricing_updated_by');
            
            // Restricción única: no duplicar configuraciones exactas
            $table->unique([
                'sub_branch_id',
                'room_type_id',
                'rate_type_id',
                'time_from_minutes',
                'time_to_minutes',
                'effective_from',
                'deleted_at'
            ], 'unique_pricing_config');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pricing_ranges');
    }
};
