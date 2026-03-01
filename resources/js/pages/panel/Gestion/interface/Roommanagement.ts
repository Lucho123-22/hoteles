import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

// ============================================
// INTERFACES Y TIPOS
// ============================================

export interface Room {
    id: string;
    room_number: string;
    room_type: string;
    status: RoomStatus;
    is_active: boolean;
    customer?: string;
    check_in?: string;
    check_out?: string;
    booking_id?: string;
    booking_code?: string;
    elapsed_time?: string;
    elapsed_minutes?: number;
    remaining_time?: string;  // "02:31:39" - viene directo del API
    remaining_seconds?: number;
    total_hours_contracted?: number;
    rate_type?: string;
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
// UTILIDADES DE TIEMPO SIMPLIFICADAS
// ============================================

/**
 * Parsea un string de tiempo "HH:MM:SS" o "-HH:MM:SS" a segundos totales
 */
export const parseTimeToSeconds = (timeString: string | null | undefined): number => {
    if (!timeString) return 0;
    
    const isNegative = timeString.startsWith('-');
    const cleanTime = timeString.replace('-', '');
    const parts = cleanTime.split(':');
    
    if (parts.length !== 3) return 0;
    
    const hours = parseInt(parts[0]) || 0;
    const minutes = parseInt(parts[1]) || 0;
    const seconds = parseInt(parts[2]) || 0;
    
    const totalSeconds = (hours * 3600) + (minutes * 60) + seconds;
    
    return isNegative ? -totalSeconds : totalSeconds;
};

/**
 * Convierte segundos a formato "HH:MM:SS" o "-HH:MM:SS"
 */
export const secondsToTimeString = (totalSeconds: number): string => {
    const isNegative = totalSeconds < 0;
    const absSeconds = Math.abs(totalSeconds);
    
    const hours = Math.floor(absSeconds / 3600);
    const minutes = Math.floor((absSeconds % 3600) / 60);
    const seconds = Math.floor(absSeconds % 60);
    
    const timeString = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    
    return isNegative ? `-${timeString}` : timeString;
};

/**
 * Detecta si el tiempo está cerca de vencer (últimos 5 minutos positivos)
 */
export const isNearCheckout = (remainingTime: string | null | undefined): boolean => {
    if (!remainingTime) return false;
    
    const seconds = parseTimeToSeconds(remainingTime);
    const minutes = Math.floor(seconds / 60);
    
    return minutes <= 5 && minutes > 0;
};

/**
 * Detecta si el tiempo ya venció (negativo o 0)
 */
export const isCheckoutExpired = (remainingTime: string | null | undefined): boolean => {
    if (!remainingTime) return false;
    
    const seconds = parseTimeToSeconds(remainingTime);
    return seconds <= 0;
};

/**
 * Detecta datos sospechosos (más de 48 horas)
 */
export const isSuspiciousCheckout = (remainingTime: string | null | undefined): boolean => {
    if (!remainingTime) return false;
    
    const seconds = parseTimeToSeconds(remainingTime);
    const hours = Math.floor(seconds / 3600);
    
    return hours > 48;
};

export const formatCheckIn = (checkInTime: string | null): string => {
    if (!checkInTime) {
        return '-';
    }
    
    const date = new Date(checkInTime);
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `Entrada: ${hours}:${minutes}`;
};

export const formatCheckOut = (checkOutTime: string | null): string => {
    if (!checkOutTime) {
        return '-';
    }
    
    const date = new Date(checkOutTime);
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `Salida: ${hours}:${minutes}`;
};

// ============================================
// PINIA STORE
// ============================================

export const useRoomManagementStore = defineStore('roomManagement', () => {
    // Estado
    const floors = ref<Floor[]>([]);
    const layout = ref<LayoutType>('grid');
    const loading = ref<boolean>(true);
    
    // Mapa para almacenar los segundos restantes de cada habitación
    // Clave: room.id, Valor: segundos restantes
    const roomTimers = ref<Map<string, number>>(new Map());
    
    // Diálogos
    const liberarDialog = ref<boolean>(false);
    const extenderDialog = ref<boolean>(false);
    const showCobrarDialog = ref<boolean>(false);
    const finalizarDialog = ref<boolean>(false);
    
    // Selección
    const selectedRoomId = ref<string | null>(null);
    const selectedBookingId = ref<string | null>(null);
    const selectedRoomNumber = ref<string | null>(null);
    
    // Variable para el intervalo
    let timeInterval: ReturnType<typeof setInterval> | null = null;

    // Computed
    const layoutOptions = computed(() => ['list', 'grid'] as const);

    // ============================================
    // ACCIONES - FETCH
    // ============================================

    const fetchFloors = async (): Promise<void> => {
        try {
            loading.value = true;
            const response = await axios.get('/floors-rooms');
            floors.value = response.data.data;
            
            // Inicializar los timers con los valores del API
            floors.value.forEach(floor => {
                floor.rooms.forEach(room => {
                    if (room.status === 'occupied' && room.remaining_time) {
                        const seconds = parseTimeToSeconds(room.remaining_time);
                        roomTimers.value.set(room.id, seconds);
                    }
                });
            });
        } catch (error) {
            console.error('Error al cargar pisos y habitaciones:', error);
            floors.value = [];
        } finally {
            loading.value = false;
        }
    };

    // ============================================
    // ACCIONES - TIEMPO
    // ============================================

    /**
     * Actualiza los timers restando 1 segundo a cada habitación ocupada
     */
    const updateTimers = (): void => {
        roomTimers.value.forEach((seconds, roomId) => {
            // Restar 1 segundo
            roomTimers.value.set(roomId, seconds - 1);
        });
    };

    /**
     * Obtiene el tiempo restante formateado para una habitación
     */
    const getRemainingTime = (roomId: string): string => {
        const seconds = roomTimers.value.get(roomId);
        if (seconds === undefined) return '00:00:00';
        return secondsToTimeString(seconds);
    };

    const startTimeInterval = (): void => {
        if (timeInterval) {
            clearInterval(timeInterval);
        }
        
        // Actualizar cada segundo
        timeInterval = setInterval(() => {
            updateTimers();
        }, 1000);
    };

    const stopTimeInterval = (): void => {
        if (timeInterval) {
            clearInterval(timeInterval);
            timeInterval = null;
        }
    };

    // ============================================
    // ACCIONES - ABRIR DIÁLOGOS
    // ============================================

    const openLiberarDialog = (roomId: string, roomNumber: string): void => {
        selectedRoomId.value = roomId;
        selectedRoomNumber.value = roomNumber;
        liberarDialog.value = true;
    };

    const openExtenderDialog = (bookingId: string, roomNumber: string): void => {
        selectedBookingId.value = bookingId;
        selectedRoomNumber.value = roomNumber;
        extenderDialog.value = true;
    };

    const openCobrarDialog = (bookingId: string, roomNumber: string): void => {
        selectedBookingId.value = bookingId;
        selectedRoomNumber.value = roomNumber;
        showCobrarDialog.value = true;
    };

    const openFinalizarDialog = (bookingId: string, roomNumber: string): void => {
        selectedBookingId.value = bookingId;
        selectedRoomNumber.value = roomNumber;
        finalizarDialog.value = true;
    };

    const closeAllDialogs = (): void => {
        liberarDialog.value = false;
        extenderDialog.value = false;
        showCobrarDialog.value = false;
        finalizarDialog.value = false;
        selectedRoomId.value = null;
        selectedBookingId.value = null;
        selectedRoomNumber.value = null;
    };

    // ============================================
    // ACCIONES - API CALLS
    // ============================================

    const liberarHabitacion = async (roomId: string): Promise<void> => {
        try {
            const { data } = await axios.post(`/cuarto/${roomId}/liberar`);
            
            // Actualiza la habitación en el estado local
            floors.value.forEach(floor => {
                const room = floor.rooms.find(r => r.id === roomId);
                if (room) {
                    room.status = 'available';
                    // Eliminar el timer de esta habitación
                    roomTimers.value.delete(roomId);
                }
            });

            return data;
        } catch (error) {
            console.error('Error al liberar habitación:', error);
            throw error;
        }
    };

    // ============================================
    // ACCIONES - HANDLERS DE DIÁLOGOS
    // ============================================

    const handleRoomLiberated = async (): Promise<void> => {
        await fetchFloors();
        liberarDialog.value = false;
        selectedRoomId.value = null;
        selectedRoomNumber.value = null;
    };

    const handleTimeExtended = async (): Promise<void> => {
        await fetchFloors();
        extenderDialog.value = false;
        selectedBookingId.value = null;
        selectedRoomNumber.value = null;
    };

    const handleExtraTimeCharged = async (): Promise<void> => {
        await fetchFloors();
        showCobrarDialog.value = false;
        selectedBookingId.value = null;
        selectedRoomNumber.value = null;
    };

    const handleBookingFinished = async (): Promise<void> => {
        await fetchFloors();
        finalizarDialog.value = false;
        selectedBookingId.value = null;
        selectedRoomNumber.value = null;
    };

    return {
        // Estado
        floors,
        layout,
        loading,
        roomTimers,
        liberarDialog,
        extenderDialog,
        showCobrarDialog,
        finalizarDialog,
        selectedRoomId,
        selectedBookingId,
        selectedRoomNumber,
        
        // Computed
        layoutOptions,
        
        // Acciones - Fetch
        fetchFloors,
        
        // Acciones - Tiempo
        getRemainingTime,
        startTimeInterval,
        stopTimeInterval,
        
        // Acciones - Diálogos
        openLiberarDialog,
        openExtenderDialog,
        openCobrarDialog,
        openFinalizarDialog,
        closeAllDialogs,
        
        // Acciones - API
        liberarHabitacion,
        
        // Handlers
        handleRoomLiberated,
        handleTimeExtended,
        handleExtraTimeCharged,
        handleBookingFinished
    };
});

// ============================================
// COMPOSABLES
// ============================================

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
 * Composable para trabajar con los timers de las habitaciones
 * IMPORTANTE: Ahora usa roomId en lugar de check_in/check_out
 */
export const useRoomTimer = () => {
    const store = useRoomManagementStore();
    
    /**
     * Obtiene el tiempo restante formateado para una habitación por ID
     */
    const getRemainingTime = (roomId: string): string => {
        return store.getRemainingTime(roomId);
    };
    
    /**
     * Verifica si el tiempo está cerca de vencer
     */
    const isNear = (roomId: string): boolean => {
        const timeString = store.getRemainingTime(roomId);
        return isNearCheckout(timeString);
    };
    
    /**
     * Verifica si el tiempo ya venció
     */
    const isExpired = (roomId: string): boolean => {
        const timeString = store.getRemainingTime(roomId);
        return isCheckoutExpired(timeString);
    };
    
    /**
     * Verifica si los datos son sospechosos
     */
    const isSuspicious = (roomId: string): boolean => {
        const timeString = store.getRemainingTime(roomId);
        return isSuspiciousCheckout(timeString);
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