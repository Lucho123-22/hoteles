<template>
    <div class="mb-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="flex items-center justify-center w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-lg border-2 border-primary-300 dark:border-primary-700">
                        <span class="text-2xl font-bold text-primary-700 dark:text-primary-300">
                            {{ roomData?.room_number }}
                        </span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                            Habitación {{ roomData?.room_number }}
                        </h2>
                        <p class="text-surface-600 dark:text-surface-400 text-sm mt-1">
                            {{ roomData?.full_name }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Piso -->
            <div class="p-4 bg-surface-50 dark:bg-surface-800 rounded-lg border border-surface-200 dark:border-surface-700">
                <div class="flex items-center gap-3">
                    <i class="pi pi-building text-2xl text-primary-500"></i>
                    <div>
                        <p class="text-sm text-surface-600 dark:text-surface-400">Piso</p>
                        <p class="font-semibold text-surface-900 dark:text-surface-0">
                            {{ roomData?.floor?.name }}
                        </p>
                        <p class="text-xs text-surface-500 dark:text-surface-400">
                            Nivel {{ roomData?.floor?.floor_number }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tipo de Habitación -->
            <div class="p-4 bg-surface-50 dark:bg-surface-800 rounded-lg border border-surface-200 dark:border-surface-700">
                <div class="flex items-center gap-3">
                    <i class="pi pi-home text-2xl text-primary-500"></i>
                    <div>
                        <p class="text-sm text-surface-600 dark:text-surface-400">Tipo de Habitación</p>
                        <p class="font-semibold text-surface-900 dark:text-surface-0">
                            {{ roomData?.room_type?.name }}
                        </p>
                        <p class="text-xs text-surface-500 dark:text-surface-400">
                            Capacidad: {{ roomData?.room_type?.capacity }} persona(s)
                        </p>
                    </div>
                </div>
            </div>

            <!-- Moneda seleccionada -->
            <div 
                v-if="selectedCurrency"
                class="p-4 bg-surface-50 dark:bg-surface-800 rounded-lg border-2 cursor-pointer transition-all border-green-500 bg-green-50 dark:bg-green-900/30 shadow-lg"
            >
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-3">
                        <i class="pi pi-dollar text-2xl text-primary-500"></i>
                        <div>
                            <p class="text-sm text-surface-600 dark:text-surface-400">Moneda</p>
                            <p class="font-semibold text-surface-900 dark:text-surface-0">
                                {{ selectedCurrency?.name }}
                            </p>
                            <p class="text-xs text-surface-500 dark:text-surface-400">
                                {{ selectedCurrency?.code }} — {{ selectedCurrency?.symbol }}
                            </p>
                        </div>
                    </div>
                    <i class="pi pi-check-circle text-green-500 text-xl"></i>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { RoomData, Currency } from '../interface/Useroomservicestore';

interface Props {
    roomData?: RoomData | null;
    selectedCurrency?: Currency | null;
}

defineProps<Props>();
</script>