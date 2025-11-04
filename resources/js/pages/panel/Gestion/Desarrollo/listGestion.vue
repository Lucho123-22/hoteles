<template>
    <div class="">
        <DataView :value="floors" :layout="layout">
            <template #header>
                <div class="flex justify-between items-center">
                    <div class="text-xl font-semibold">Gestión de Habitaciones</div>
                    <SelectButton v-model="layout" :options="options" :allowEmpty="false">
                        <template #option="{ option }">
                            <i :class="[option === 'list' ? 'pi pi-bars' : 'pi pi-table']" />
                        </template>
                    </SelectButton>
                </div>
            </template>

            <template #list="slotProps">
                <div class="flex flex-col gap-6">
                    <div v-for="(floor, floorIndex) in slotProps.items" :key="floor.id">
                        <div class="bg-primary-50 dark:bg-primary-900/20 p-4 rounded-t-lg border-b-2 border-primary-500">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-bold text-primary-700 dark:text-primary-300">
                                        {{ floor.name }}
                                    </h3>
                                    <p class="text-sm text-surface-600 dark:text-surface-400 mt-1">
                                        {{ floor.available_rooms }}/{{ floor.total_rooms }} habitaciones disponibles
                                    </p>
                                </div>
                                <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                    Piso {{ floor.floor_number }}
                                </div>
                            </div>
                        </div>

                        <div class="border border-t-0 border-surface-200 dark:border-surface-700 rounded-b-lg">
                            <div v-for="(room, roomIndex) in floor.rooms" :key="room.id">
                                <div 
                                    class="flex flex-col sm:flex-row sm:items-center p-6 gap-4 hover:bg-surface-50 dark:hover:bg-surface-800/50 transition-colors"
                                    :class="{ 'border-t border-surface-200 dark:border-surface-700': roomIndex !== 0 }"
                                >
                                    <div class="flex items-center justify-center w-20 h-20 bg-primary-100 dark:bg-primary-900/30 rounded-lg border-2 border-primary-300 dark:border-primary-700">
                                        <span class="text-2xl font-bold text-primary-700 dark:text-primary-300">
                                            {{ room.room_number }}
                                        </span>
                                    </div>

                                    <div class="flex flex-col md:flex-row justify-between md:items-center flex-1 gap-6">
                                        <div class="flex flex-col gap-3">
                                            <div>
                                                <span class="font-medium text-surface-500 dark:text-surface-400 text-sm">
                                                    Tipo de Habitación
                                                </span>
                                                <div class="text-lg font-semibold mt-1">{{ room.room_type }}</div>
                                            </div>
                                            <div class="flex items-center gap-3 flex-wrap">
                                                <Tag 
                                                    :value="getStatusLabel(room.status)" 
                                                    :severity="getStatusSeverity(room.status)"
                                                />
                                                <Badge 
                                                    :value="room.is_active ? 'Activa' : 'Inactiva'" 
                                                    :severity="room.is_active ? 'success' : 'secondary'"
                                                />
                                            </div>
                                        </div>

                                        <div class="flex flex-col md:flex-row md:items-center gap-4">
                                            <div v-if="room.status === 'occupied' && room.customer" class="flex flex-col gap-1 text-sm md:text-right">
                                                <div class="flex items-center gap-2 text-surface-600 dark:text-surface-400">
                                                    <i class="pi pi-user text-xs"></i>
                                                    <span class="font-medium">{{ room.customer }}</span>
                                                </div>
                                                <div v-if="room.check_in" class="flex items-center gap-2 text-surface-500 dark:text-surface text-xs">
                                                    <i class="pi pi-sign-in"></i>
                                                    <span>{{ formatCheckIn(room.check_in) }}</span>
                                                </div>
                                                <div v-if="room.check_out" class="flex items-center gap-2 text-surface-500 dark:text-surface-500 text-xs">
                                                    <i class="pi pi-sign-out"></i>
                                                    <span>{{ formatCheckOut(room.check_out) }}</span>
                                                </div>
                                            </div>

                                            <!-- Timer REGRESIVO con alerta de tiempo vencido -->
                                            <div 
                                                v-if="room.status === 'occupied'"
                                                class="bg-surface-100 dark:bg-surface-700 px-4 py-2 rounded-lg transition-all duration-300"
                                                :class="{ 
                                                    'animate-pulse bg-orange-100 dark:bg-orange-900/30 border-2 border-orange-400': isNearCheckout(room.check_out),
                                                    'animate-bounce bg-red-100 dark:bg-red-900/30 border-2 border-red-500': isCheckoutExpired(room.check_out),
                                                    'bg-yellow-100 dark:bg-yellow-900/30 border-2 border-yellow-400': isSuspiciousCheckout(room.check_out)
                                                }"
                                            >
                                                <div class="flex items-center gap-2">
                                                    <i class="pi pi-clock text-lg" :class="{
                                                        'text-orange-600': isNearCheckout(room.check_out) && !isCheckoutExpired(room.check_out),
                                                        'text-red-600': isCheckoutExpired(room.check_out),
                                                        'text-yellow-600': isSuspiciousCheckout(room.check_out),
                                                        'text-primary-600': !isNearCheckout(room.check_out) && !isCheckoutExpired(room.check_out) && !isSuspiciousCheckout(room.check_out)
                                                    }"></i>
                                                    <span class="font-mono text-lg font-semibold" :class="{
                                                        'text-orange-600 dark:text-orange-400': isNearCheckout(room.check_out) && !isCheckoutExpired(room.check_out),
                                                        'text-red-600 dark:text-red-400': isCheckoutExpired(room.check_out),
                                                        'text-yellow-600 dark:text-yellow-400': isSuspiciousCheckout(room.check_out),
                                                        'text-primary-700 dark:text-primary-300': !isNearCheckout(room.check_out) && !isCheckoutExpired(room.check_out) && !isSuspiciousCheckout(room.check_out)
                                                    }">
                                                        {{ calculateRemainingTime(room.check_in, room.check_out) }}
                                                    </span>
                                                </div>
                                                <div v-if="isCheckoutExpired(room.check_out)" class="text-xs text-red-600 dark:text-red-400 font-semibold mt-1">
                                                    ¡TIEMPO VENCIDO!
                                                </div>
                                                <div v-else-if="isNearCheckout(room.check_out)" class="text-xs text-orange-600 dark:text-orange-400 font-semibold mt-1">
                                                    ¡Próximo a vencer!
                                                </div>
                                                <div v-else-if="isSuspiciousCheckout(room.check_out)" class="text-xs text-yellow-600 dark:text-yellow-400 font-semibold mt-1">
                                                    ⚠️ Datos sospechosos
                                                </div>
                                                <div v-else class="text-xs text-surface-500 dark:text-surface-400 mt-1">
                                                    Tiempo restante
                                                </div>
                                            </div>
                                            
                                            <div class="flex gap-2" v-if="room.status !== 'maintenance'">
                                                <Button 
                                                    icon="pi pi-eye" 
                                                    severity="info"
                                                    outlined
                                                    size="small"
                                                    @click="viewRoomDetails(room.id)"
                                                    v-tooltip.top="'Ver detalles'"
                                                />
                                                
                                                <!-- Botones para habitación OCUPADA -->
                                                <template v-if="room.status === 'occupied'">
                                                    <Button 
                                                        v-if="isCheckoutExpired(room.check_out)"
                                                        icon="pi pi-clock" 
                                                        severity="warning"
                                                        outlined
                                                        size="small"
                                                        @click="extendTime(room.id)"
                                                        v-tooltip.top="'Extender tiempo'"
                                                    />
                                                    <Button 
                                                        v-if="isCheckoutExpired(room.check_out)"
                                                        icon="pi pi-dollar" 
                                                        severity="success"
                                                        outlined
                                                        size="small"
                                                        @click="chargeExtraTime(room.id)"
                                                        v-tooltip.top="'Cobrar tiempo extra'"
                                                    />
                                                    <Button 
                                                        icon="pi pi-sign-out" 
                                                        severity="danger"
                                                        outlined
                                                        size="small"
                                                        @click="finishBooking(room.id, room.room_number)"
                                                        v-tooltip.top="'Finalizar reserva'"
                                                    />
                                                </template>

                                                <!-- Botón para habitación en LIMPIEZA -->
                                                <Button 
                                                    v-if="room.status === 'cleaning'"
                                                    icon="pi pi-check-circle" 
                                                    severity="success"
                                                    outlined
                                                    size="small"
                                                    @click="openLiberarDialog(room.id)"
                                                    v-tooltip.top="'Liberar habitación'"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #grid="slotProps">
                <div class="flex flex-col gap-6">
                    <div v-for="floor in slotProps.items" :key="floor.id">
                        <div class="bg-primary-50 dark:bg-primary-900/20 p-4 rounded-lg border border-primary-200 dark:border-primary-800">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-bold text-primary-700 dark:text-primary-300">
                                        {{ floor.name }}
                                    </h3>
                                    <p class="text-sm text-surface-600 dark:text-surface-400 mt-1">
                                        {{ floor.available_rooms }}/{{ floor.total_rooms }} disponibles
                                    </p>
                                </div>
                                <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                    Piso {{ floor.floor_number }}
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-12 gap-4">
                            <div 
                                v-for="room in floor.rooms" 
                                :key="room.id" 
                                class="col-span-12 sm:col-span-6 md:col-span-4 xl:col-span-3"
                            >
                                <div class="p-6 border border-surface-200 dark:border-surface-700 bg-surface-0 dark:bg-surface-900 rounded-lg hover:shadow-lg transition-shadow h-full flex flex-col">
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="flex items-center justify-center w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-lg border-2 border-primary-300 dark:border-primary-700">
                                            <span class="text-xl font-bold text-primary-700 dark:text-primary-300">
                                                {{ room.room_number }}
                                            </span>
                                        </div>
                                        <Tag 
                                            :value="getStatusLabel(room.status)" 
                                            :severity="getStatusSeverity(room.status)"
                                        />
                                    </div>

                                    <div class="mb-4 flex-1">
                                        <span class="font-medium text-surface-500 dark:text-surface-400 text-sm">
                                            Tipo de Habitación
                                        </span>
                                        <div class="text-lg font-semibold mt-1">{{ room.room_type }}</div>
                                    </div>

                                    <div class="mb-4">
                                        <Badge 
                                            :value="room.is_active ? 'Activa' : 'Inactiva'" 
                                            :severity="room.is_active ? 'success' : 'secondary'"
                                        />
                                    </div>

                                    <div v-if="room.status === 'occupied' && room.customer" class="mb-4 text-sm space-y-2">
                                        <div class="flex items-start gap-2 text-surface-600 dark:text-surface-400">
                                            <i class="pi pi-user text-xs mt-1"></i>
                                            <span class="font-medium">{{ room.customer }}</span>
                                        </div>
                                        <div v-if="room.check_in" class="flex items-center gap-2 text-surface-500 dark:text-surface-500 text-xs">
                                            <i class="pi pi-sign-in"></i>
                                            <span>{{ formatCheckIn(room.check_in) }}</span>
                                        </div>
                                        <div v-if="room.check_out" class="flex items-center gap-2 text-surface-500 dark:text-surface-500 text-xs">
                                            <i class="pi pi-sign-out"></i>
                                            <span>{{ formatCheckOut(room.check_out) }}</span>
                                        </div>
                                    </div>

                                    <!-- Timer REGRESIVO con alerta de tiempo vencido -->
                                    <div 
                                        v-if="room.status === 'occupied'"
                                        class="bg-surface-100 dark:bg-surface-700 px-4 py-3 rounded-lg mb-4 transition-all duration-300"
                                        :class="{ 
                                            'animate-pulse bg-orange-100 dark:bg-orange-900/30 border-2 border-orange-400': isNearCheckout(room.check_out),
                                            'animate-bounce bg-red-100 dark:bg-red-900/30 border-2 border-red-500': isCheckoutExpired(room.check_out),
                                            'bg-yellow-100 dark:bg-yellow-900/30 border-2 border-yellow-400': isSuspiciousCheckout(room.check_out)
                                        }"
                                    >
                                        <div class="flex items-center justify-center gap-2">
                                            <i class="pi pi-clock text-lg" :class="{
                                                'text-orange-600': isNearCheckout(room.check_out) && !isCheckoutExpired(room.check_out),
                                                'text-red-600': isCheckoutExpired(room.check_out),
                                                'text-yellow-600': isSuspiciousCheckout(room.check_out),
                                                'text-primary-600': !isNearCheckout(room.check_out) && !isCheckoutExpired(room.check_out) && !isSuspiciousCheckout(room.check_out)
                                            }"></i>
                                            <span class="font-mono text-lg font-semibold" :class="{
                                                'text-orange-600 dark:text-orange-400': isNearCheckout(room.check_out) && !isCheckoutExpired(room.check_out),
                                                'text-red-600 dark:text-red-400': isCheckoutExpired(room.check_out),
                                                'text-yellow-600 dark:text-yellow-400': isSuspiciousCheckout(room.check_out),
                                                'text-primary-700 dark:text-primary-300': !isNearCheckout(room.check_out) && !isCheckoutExpired(room.check_out) && !isSuspiciousCheckout(room.check_out)
                                            }">
                                                {{ calculateRemainingTime(room.check_in, room.check_out) }}
                                            </span>
                                        </div>
                                        <div v-if="isCheckoutExpired(room.check_out)" class="text-xs text-center text-red-600 dark:text-red-400 font-semibold mt-1">
                                            ¡TIEMPO VENCIDO!
                                        </div>
                                        <div v-else-if="isNearCheckout(room.check_out)" class="text-xs text-center text-orange-600 dark:text-orange-400 font-semibold mt-1">
                                            ¡Próximo a vencer!
                                        </div>
                                        <div v-else-if="isSuspiciousCheckout(room.check_out)" class="text-xs text-center text-yellow-600 dark:text-yellow-400 font-semibold mt-1">
                                            ⚠️ Datos sospechosos
                                        </div>
                                        <div v-else class="text-xs text-center text-surface-500 dark:text-surface-400 mt-1">
                                            Tiempo restante
                                        </div>
                                    </div>

                                    <div class="flex gap-2 flex-wrap" v-if="room.status !== 'maintenance'">
                                        <Button 
                                            icon="pi pi-eye" 
                                            severity="info"
                                            outlined
                                            class="flex-1"
                                            size="small"
                                            @click="viewRoomDetails(room.id)"
                                            v-tooltip.top="'Ver detalles'"
                                        />
                                        
                                        <!-- Botones para habitación OCUPADA -->
                                        <template v-if="room.status === 'occupied'">
                                            <Button 
                                                v-if="isCheckoutExpired(room.check_out)"
                                                icon="pi pi-clock" 
                                                severity="warning"
                                                outlined
                                                class="flex-1"
                                                size="small"
                                                @click="extendTime(room.id)"
                                                v-tooltip.top="'Extender tiempo'"
                                            />
                                            <Button 
                                                v-if="isCheckoutExpired(room.check_out)"
                                                icon="pi pi-dollar" 
                                                severity="success"
                                                outlined
                                                class="flex-1"
                                                size="small"
                                                @click="chargeExtraTime(room.id)"
                                                v-tooltip.top="'Cobrar tiempo extra'"
                                            />
                                            <Button 
                                                icon="pi pi-sign-out" 
                                                severity="danger"
                                                outlined
                                                class="flex-1"
                                                size="small"
                                                @click="finishBooking(room.id, room.room_number)"
                                                v-tooltip.top="'Finalizar reserva'"
                                            />
                                        </template>

                                        <!-- Botón para habitación en LIMPIEZA -->
                                        <Button 
                                            v-if="room.status === 'cleaning'"
                                            icon="pi pi-check-circle" 
                                            severity="success"
                                            outlined
                                            class="flex-1"
                                            size="small"
                                            @click="openLiberarDialog(room.id)"
                                            v-tooltip.top="'Liberar habitación'"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #empty>
                <div v-if="loading">
                    <div v-if="layout === 'list'" class="flex flex-col">
                        <div v-for="i in 6" :key="i">
                            <div class="flex flex-col xl:flex-row xl:items-start p-6 gap-6" :class="{ 'border-t border-surface-200 dark:border-surface-700': i !== 0 }">
                                <Skeleton class="w-9/12 sm:w-64 xl:w-40 h-24 mx-auto" />
                                <div class="flex flex-col sm:flex-row justify-between items-center xl:items-start flex-1 gap-6">
                                    <div class="flex flex-col items-center sm:items-start gap-4">
                                        <Skeleton width="8rem" height="2rem" />
                                        <Skeleton width="6rem" height="1rem" />
                                        <div class="flex items-center gap-4">
                                            <Skeleton width="6rem" height="1rem" />
                                            <Skeleton width="3rem" height="1rem" />
                                        </div>
                                    </div>
                                    <div class="flex sm:flex-col items-center sm:items-end gap-4 sm:gap-2">
                                        <Skeleton width="4rem" height="2rem" />
                                        <Skeleton size="3rem" shape="circle" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="grid grid-cols-12 gap-4">
                        <div v-for="i in 6" :key="i" class="col-span-12 sm:col-span-6 xl:col-span-4 p-2">
                            <div class="p-6 border border-surface-200 dark:border-surface-700 bg-surface-0 dark:bg-surface-900 rounded">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <Skeleton width="6rem" height="2rem" />
                                    <Skeleton width="3rem" height="1rem" />
                                </div>
                                <div class="flex flex-col items-center gap-4 py-8">
                                    <Skeleton width="75%" height="10rem" />
                                    <Skeleton width="8rem" height="2rem" />
                                    <Skeleton width="6rem" height="1rem" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <Skeleton width="4rem" height="2rem" />
                                    <Skeleton width="6rem" height="1rem" shape="circle" size="3rem" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center p-6">
                    <i class="pi pi-inbox text-4xl text-surface-400 mb-3"></i>
                    <p class="text-surface-600 dark:text-surface-400">No hay habitaciones disponibles</p>
                </div>
            </template>
        </DataView>

        <!-- Diálogos -->
        <LiberarRoom 
            v-model:visible="showLiberarDialog" 
            :roomId="selectedRoomId"
            @room-liberated="handleRoomLiberated"
        />

        <ExtenderTiempo 
            v-model:visible="showExtenderDialog" 
            :roomId="selectedRoomId"
            @time-extended="handleTimeExtended"
        />

        <CobrarTiempoExtra 
            v-model:visible="showCobrarDialog" 
            :roomId="selectedRoomId"
            @extra-time-charged="handleExtraTimeCharged"
        />

        <FinalizarReserva 
            v-model:visible="showFinalizarDialog" 
            :roomId="selectedRoomId"
            :roomNumber="selectedRoomNumber"
            @booking-finished="handleBookingFinished"
        />
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { router } from '@inertiajs/vue3';
import DataView from 'primevue/dataview';
import SelectButton from 'primevue/selectbutton';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import Button from 'primevue/button';
import Skeleton from 'primevue/skeleton';
import LiberarRoom from './liberarRoom.vue';
import ExtenderTiempo from './extenderTiempo.vue';
import CobrarTiempoExtra from './cobrarTiempoExtra.vue';
import FinalizarReserva from './finalizarReserva.vue';

const floors = ref([]);
const layout = ref('grid');
const options = ref(['list', 'grid']);
const loading = ref(true);
const currentTime = ref(new Date());
const showLiberarDialog = ref(false);
const showExtenderDialog = ref(false);
const showCobrarDialog = ref(false);
const showFinalizarDialog = ref(false);
const selectedRoomId = ref(null);
const selectedRoomNumber = ref(null);
let timerInterval = null;

onMounted(async () => {
    await fetchFloors();
    
    // Actualizar el tiempo cada segundo para que el timer sea dinámico
    timerInterval = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    // Limpiar el interval cuando el componente se desmonte
    if (timerInterval) {
        clearInterval(timerInterval);
    }
});

const fetchFloors = async () => {
    try {
        loading.value = true;
        const response = await fetch('/floors-rooms');
        const result = await response.json();
        floors.value = result.data;
    } catch (error) {
        console.error('Error al cargar pisos y habitaciones:', error);
        floors.value = [];
    } finally {
        loading.value = false;
    }
};

/**
 * Calcula el tiempo RESTANTE (regresivo) hasta el check-out
 * Si el tiempo ya expiró, muestra valores negativos (ej: -00:15:30)
 */
const calculateRemainingTime = (checkInTime, checkOutTime) => {
    if (!checkOutTime) {
        return '00:00:00';
    }
    
    const checkOut = new Date(checkOutTime);
    const diff = checkOut - currentTime.value;
    
    // Si el tiempo ya expiró (diff negativo), mostrar con signo negativo
    const isExpired = diff < 0;
    const absDiff = Math.abs(diff);
    
    // Convertir a horas, minutos y segundos
    const hours = Math.floor(absDiff / (1000 * 60 * 60));
    const minutes = Math.floor((absDiff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((absDiff % (1000 * 60)) / 1000);
    
    // Formatear con ceros a la izquierda y signo negativo si aplica
    const sign = isExpired ? '-' : '';
    return `${sign}${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
};

const formatCheckIn = (checkInTime) => {
    if (!checkInTime) {
        return '-';
    }
    
    const date = new Date(checkInTime);
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `Entrada: ${hours}:${minutes}`;
};

const formatCheckOut = (checkOutTime) => {
    if (!checkOutTime) {
        return '-';
    }
    
    const date = new Date(checkOutTime);
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `Salida: ${hours}:${minutes}`;
};

const isNearCheckout = (checkOutTime) => {
    if (!checkOutTime) {
        return false;
    }
    
    const checkOut = new Date(checkOutTime);
    
    // Validar que la fecha sea válida
    if (isNaN(checkOut.getTime())) {
        return false;
    }
    
    const diff = checkOut - currentTime.value;
    
    // Convertir a minutos
    const minutes = Math.floor(diff / (1000 * 60));
    
    // Alertar si faltan 5 minutos o menos para el checkout
    return minutes <= 5 && minutes > 0;
};

const isCheckoutExpired = (checkOutTime) => {
    if (!checkOutTime) {
        return false;
    }
    
    const checkOut = new Date(checkOutTime);
    
    // Validar que la fecha sea válida
    if (isNaN(checkOut.getTime())) {
        return false;
    }
    
    const diff = checkOut - currentTime.value;
    
    // Retorna true si el tiempo ya pasó (diff es negativo o cero)
    return diff <= 0;
};

/**
 * Verifica si el check_out tiene datos sospechosos (muy lejos en el futuro)
 */
const isSuspiciousCheckout = (checkOutTime) => {
    if (!checkOutTime) return false;
    
    const checkOut = new Date(checkOutTime);
    const diff = checkOut - currentTime.value;
    const hours = Math.floor(diff / (1000 * 60 * 60));
    
    // Si faltan más de 48 horas, es sospechoso
    return hours > 48;
};

const finishBooking = (roomId, roomNumber) => {
    selectedRoomId.value = roomId;
    selectedRoomNumber.value = roomNumber;
    showFinalizarDialog.value = true;
};

const extendTime = (roomId) => {
    selectedRoomId.value = roomId;
    showExtenderDialog.value = true;
};

const chargeExtraTime = (roomId) => {
    selectedRoomId.value = roomId;
    showCobrarDialog.value = true;
};

const openLiberarDialog = (roomId) => {
    selectedRoomId.value = roomId;
    showLiberarDialog.value = true;
};

const handleRoomLiberated = async () => {
    await fetchFloors();
    showLiberarDialog.value = false;
    selectedRoomId.value = null;
};

const handleTimeExtended = async () => {
    await fetchFloors();
    showExtenderDialog.value = false;
    selectedRoomId.value = null;
};

const handleExtraTimeCharged = async () => {
    await fetchFloors();
    showCobrarDialog.value = false;
    selectedRoomId.value = null;
};

const handleBookingFinished = async () => {
    await fetchFloors();
    showFinalizarDialog.value = false;
    selectedRoomId.value = null;
    selectedRoomNumber.value = null;
};

const getStatusLabel = (status) => {
    const labels = {
        'available': 'Disponible',
        'occupied': 'Ocupada',
        'maintenance': 'Mantenimiento',
        'cleaning': 'Limpieza'
    };
    return labels[status] || status;
};

const getStatusSeverity = (status) => {
    const severities = {
        'available': 'success',
        'occupied': 'danger',
        'maintenance': 'warn',
        'cleaning': 'info'
    };
    return severities[status] || null;
};

const viewRoomDetails = (roomId) => {
    console.log('Navegando a habitación:', roomId);
    router.visit(`/panel/cuarto/${roomId}`);
};
</script>