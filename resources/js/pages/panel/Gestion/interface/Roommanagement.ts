import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

// ============================================
// INTERFACES Y TIPOS
// ============================================

export interface Room {
    id: number;
    room_number: string;
    room_type: string;
    status: RoomStatus;
    is_active: boolean;
    customer?: string;
    check_in?: string;
    check_out?: string;
}

export interface Floor {
    id: number;
    name: string;
    floor_number: number;
    total_rooms: number;
    available_rooms: number;
    rooms: Room[];
}

export type RoomStatus = 'available' | 'occupied' | 'maintenance' | 'cleaning';

export type LayoutType = 'list' | 'grid';

export interface StatusConfig {
    label: string;
    severity: 'success' | 'danger' | 'warn' | 'info' | null;
}

// ============================================
// CONSTANTES
// ============================================

export const STATUS_LABELS: Record<RoomStatus, string> = {
    available: 'Disponible',
    occupied: 'Ocupada',
    maintenance: 'Mantenimiento',
    cleaning: 'Limpieza'
};

export const STATUS_SEVERITIES: Record<RoomStatus, 'success' | 'danger' | 'warn' | 'info'> = {
    available: 'success',
    occupied: 'danger',
    maintenance: 'warn',
    cleaning: 'info'
};

// ============================================
// UTILIDADES DE TIEMPO
// ============================================

/**
 * Calcula el tiempo restante (regresivo) hasta el check-out
 * @param checkInTime - Hora de entrada
 * @param checkOutTime - Hora de salida
 * @param currentTime - Hora actual
 * @returns Tiempo formateado como HH:MM:SS (con signo negativo si expiró)
 */
export const calculateRemainingTime = (
    checkInTime: string | null,
    checkOutTime: string | null,
    currentTime: Date
): string => {
    if (!checkOutTime) {
        return '00:00:00';
    }
    
    const checkOut = new Date(checkOutTime);
    const diff = checkOut.getTime() - currentTime.getTime();
    
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

/**
 * Formatea la hora de entrada
 * @param checkInTime - Hora de entrada
 * @returns Hora formateada como "Entrada: HH:MM"
 */
export const formatCheckIn = (checkInTime: string | null): string => {
    if (!checkInTime) {
        return '-';
    }
    
    const date = new Date(checkInTime);
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `Entrada: ${hours}:${minutes}`;
};

/**
 * Formatea la hora de salida
 * @param checkOutTime - Hora de salida
 * @returns Hora formateada como "Salida: HH:MM"
 */
export const formatCheckOut = (checkOutTime: string | null): string => {
    if (!checkOutTime) {
        return '-';
    }
    
    const date = new Date(checkOutTime);
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `Salida: ${hours}:${minutes}`;
};

/**
 * Verifica si el checkout está próximo (5 minutos o menos)
 * @param checkOutTime - Hora de salida
 * @param currentTime - Hora actual
 * @returns true si faltan 5 minutos o menos
 */
export const isNearCheckout = (checkOutTime: string | null, currentTime: Date): boolean => {
    if (!checkOutTime) {
        return false;
    }
    
    const checkOut = new Date(checkOutTime);
    
    // Validar que la fecha sea válida
    if (isNaN(checkOut.getTime())) {
        return false;
    }
    
    const diff = checkOut.getTime() - currentTime.getTime();
    
    // Convertir a minutos
    const minutes = Math.floor(diff / (1000 * 60));
    
    // Alertar si faltan 5 minutos o menos para el checkout
    return minutes <= 5 && minutes > 0;
};

/**
 * Verifica si el tiempo de checkout ya expiró
 * @param checkOutTime - Hora de salida
 * @param currentTime - Hora actual
 * @returns true si el tiempo ya pasó
 */
export const isCheckoutExpired = (checkOutTime: string | null, currentTime: Date): boolean => {
    if (!checkOutTime) {
        return false;
    }
    
    const checkOut = new Date(checkOutTime);
    
    // Validar que la fecha sea válida
    if (isNaN(checkOut.getTime())) {
        return false;
    }
    
    const diff = checkOut.getTime() - currentTime.getTime();
    
    // Retorna true si el tiempo ya pasó (diff es negativo o cero)
    return diff <= 0;
};

/**
 * Verifica si el checkout tiene datos sospechosos (muy lejos en el futuro)
 * @param checkOutTime - Hora de salida
 * @param currentTime - Hora actual
 * @returns true si faltan más de 48 horas
 */
export const isSuspiciousCheckout = (checkOutTime: string | null, currentTime: Date): boolean => {
    if (!checkOutTime) {
        return false;
    }
    
    const checkOut = new Date(checkOutTime);
    const diff = checkOut.getTime() - currentTime.getTime();
    const hours = Math.floor(diff / (1000 * 60 * 60));
    
    // Si faltan más de 48 horas, es sospechoso
    return hours > 48;
};

// ============================================
// PINIA STORE
// ============================================

export const useRoomManagementStore = defineStore('roomManagement', () => {
    // Estado
    const floors = ref<Floor[]>([]);
    const layout = ref<LayoutType>('grid');
    const loading = ref<boolean>(true);
    const currentTime = ref<Date>(new Date());
    const showLiberarDialog = ref<boolean>(false);
    const showExtenderDialog = ref<boolean>(false);
    const showCobrarDialog = ref<boolean>(false);
    const showFinalizarDialog = ref<boolean>(false);
    const selectedRoomId = ref<number | null>(null);
    const selectedRoomNumber = ref<string | null>(null);

    // Computed
    const layoutOptions = computed(() => ['list', 'grid'] as const);

    // Acciones
    const fetchFloors = async (): Promise<void> => {
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

    const updateCurrentTime = (): void => {
        currentTime.value = new Date();
    };

    const openLiberarDialog = (roomId: number): void => {
        selectedRoomId.value = roomId;
        showLiberarDialog.value = true;
    };

    const openExtenderDialog = (roomId: number): void => {
        selectedRoomId.value = roomId;
        showExtenderDialog.value = true;
    };

    const openCobrarDialog = (roomId: number): void => {
        selectedRoomId.value = roomId;
        showCobrarDialog.value = true;
    };

    const openFinalizarDialog = (roomId: number, roomNumber: string): void => {
        selectedRoomId.value = roomId;
        selectedRoomNumber.value = roomNumber;
        showFinalizarDialog.value = true;
    };

    const closeAllDialogs = (): void => {
        showLiberarDialog.value = false;
        showExtenderDialog.value = false;
        showCobrarDialog.value = false;
        showFinalizarDialog.value = false;
        selectedRoomId.value = null;
        selectedRoomNumber.value = null;
    };

    const handleRoomLiberated = async (): Promise<void> => {
        await fetchFloors();
        showLiberarDialog.value = false;
        selectedRoomId.value = null;
    };

    const handleTimeExtended = async (): Promise<void> => {
        await fetchFloors();
        showExtenderDialog.value = false;
        selectedRoomId.value = null;
    };

    const handleExtraTimeCharged = async (): Promise<void> => {
        await fetchFloors();
        showCobrarDialog.value = false;
        selectedRoomId.value = null;
    };

    const handleBookingFinished = async (): Promise<void> => {
        await fetchFloors();
        showFinalizarDialog.value = false;
        selectedRoomId.value = null;
        selectedRoomNumber.value = null;
    };

    return {
        // Estado
        floors,
        layout,
        loading,
        currentTime,
        showLiberarDialog,
        showExtenderDialog,
        showCobrarDialog,
        showFinalizarDialog,
        selectedRoomId,
        selectedRoomNumber,
        
        // Computed
        layoutOptions,
        
        // Acciones
        fetchFloors,
        updateCurrentTime,
        openLiberarDialog,
        openExtenderDialog,
        openCobrarDialog,
        openFinalizarDialog,
        closeAllDialogs,
        handleRoomLiberated,
        handleTimeExtended,
        handleExtraTimeCharged,
        handleBookingFinished
    };
});

// ============================================
// COMPOSABLES
// ============================================

/**
 * Composable para obtener la etiqueta de estado de una habitación
 */
export const useStatusLabel = () => {
    const getStatusLabel = (status: RoomStatus): string => {
        return STATUS_LABELS[status] || status;
    };

    const getStatusSeverity = (status: RoomStatus): 'success' | 'danger' | 'warn' | 'info' => {
        return STATUS_SEVERITIES[status];
    };

    return {
        getStatusLabel,
        getStatusSeverity
    };
};

/**
 * Composable para manejar el tiempo de las habitaciones
 */
export const useRoomTimer = (currentTime: Date) => {
    const getRemainingTime = (checkIn: string | null, checkOut: string | null) => {
        return calculateRemainingTime(checkIn, checkOut, currentTime);
    };

    const isNear = (checkOut: string | null) => {
        return isNearCheckout(checkOut, currentTime);
    };

    const isExpired = (checkOut: string | null) => {
        return isCheckoutExpired(checkOut, currentTime);
    };

    const isSuspicious = (checkOut: string | null) => {
        return isSuspiciousCheckout(checkOut, currentTime);
    };

    return {
        getRemainingTime,
        isNear,
        isExpired,
        isSuspicious,
        formatCheckIn,
        formatCheckOut
    };
};