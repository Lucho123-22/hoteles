import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

// ==========================================
// INTERFACES Y TIPOS
// ==========================================

export interface Customer {
    id: string | number;
    name: string;
    document_number?: string;
    email?: string;
    phone?: string;
}

export interface Product {
    id: string | number;
    nombre: string;
    cantidad: number;
    precio_venta: number;
    quantity: number;
    price: number;
    status?: string;
    consumed_at?: string;
    consumption_id?: string | number;
}

export interface Currency {
    id: string | number;
    name: string;
    code: string;
    symbol: string;
}

export interface PaymentMethod {
    id: string | number;
    name: string;
    requires_reference: boolean;
}

export interface RateType {
    id: string | number;
    code: string;
    name: string;
    display_name?: string;
    icon?: string;
    requires_time_range?: boolean;
}

export interface PricingRange {
    id: string | number;
    sub_branch_id: string | number;
    room_type_id: string | number;
    rate_type_id: string | number;
    time_from_minutes: number;
    time_to_minutes: number;
    formatted_time_range: string;
    duration_hours: number;
    price: number;
    effective_from: string;
    effective_to: string;
    is_effective: boolean;
    is_active: boolean;
    is_hourly_rate: boolean;
    is_daily_rate: boolean;
    is_nightly_rate: boolean;
    rate_type: RateType;
    price_per_hour?: number;
    created_at: string;
    updated_at: string;
}

export interface SubBranchPolicies {
    time_settings: {
        max_allowed_time: number;
        extra_tolerance: number;
        apply_tolerance: boolean;
    } | null;
    penalty_settings: {
        penalty_active: boolean;
        charge_interval_minutes: number;
        amount_per_interval: number;
        penalty_type: 'fixed' | 'percentage';
    } | null;
    checkin_settings: {
        checkin_time: string;
        checkout_time: string;
        early_checkin_cost: number;
        late_checkout_cost: number;
    } | null;
    tax_settings: {
        tax_percentage: number;
        tax_included: boolean;
    } | null;
}

export interface CurrentBooking {
    booking_id: string | number;
    booking_code: string;
    booking_rate_per_unit: number;
    guest_name: string;
    guest_client_id: string | number;
    guest_document?: string;
    check_in: string;
    check_out: string;
    total_hours: number;
    rate_type: string;
    rate_type_id: string | number;
    remaining_time: string;
    remaining_seconds: number;
    is_time_expired: boolean;
    estimated_checkout: string;
    elapsed_minutes: number;
    current_price?: number;
    price_per_minute?: number;
    applicable_pricing_range?: {
        id: string | number;
        time_from_minutes: number;
        time_to_minutes: number;
        formatted_time_range: string;
        price: number;
        rate_type: string;
        rate_type_code: string;
    };
    penalty_amount: number;
    penalty_minutes: number;
    voucher_type: VoucherType;
    consumptions: Product[];
}

export interface CashRegister {
    id: string | number;
    name: string;
}

export interface RoomData {
    id: string | number;
    room_number: string;
    name?: string | null;
    description?: string | null;
    full_name: string;
    status: 'available' | 'occupied' | 'maintenance' | 'cleaning';
    is_active: boolean;
    floor?: {
        id: string | number;
        name: string;
        floor_number: number;
    };
    room_type?: {
        id: string | number;
        name: string;
        code: string;
        description?: string | null;
        capacity: number;
        max_capacity: number;
        category: string;
        is_active: boolean;
        created_at: string;
        updated_at: string;
    };
    available_pricing_ranges?: PricingRange[];
    sub_branch_policies?: SubBranchPolicies;
    current_booking?: CurrentBooking | null;
    created_at: string;
    updated_at: string;
}

export interface StartServicePayload {
    paymentMethod: PaymentMethod;
    operationNumber: string;
}

export interface FinishServicePayload {
    paymentMethod: PaymentMethod | null;
    operationNumber: string;
    notes: string;
}

export type VoucherType = 'boleta' | 'ticket' | 'factura';

// ==========================================
// STORE DEFINITION
// ==========================================

export const useRoomServiceStore = defineStore('roomService', () => {
    const toast = useToast();

    // ==========================================
    // STATE
    // ==========================================

    // Room & Service State
    const roomData = ref<RoomData | null>(null);
    const selectedRate = ref<RateType | null>(null);
    const selectedPricingRange = ref<PricingRange | null>(null);
    const timeAmount = ref<number>(1);
    const voucherType = ref<VoucherType>('boleta');

    // Customer & Products
    const selectedClient = ref<Customer | null>(null);
    const products = ref<Product[]>([]);

    // Timer State
    const isTimerRunning = ref<boolean>(false);
    const remainingSeconds = ref<number>(0);
    const totalSeconds = ref<number>(0);
    const timerInterval = ref<NodeJS.Timeout | null>(null);
    const syncInterval = ref<NodeJS.Timeout | null>(null);

    // Penalty State
    const penaltyAmount = ref<number>(0);
    const penaltyMinutes = ref<number>(0);
    const roomSubtotal = ref<number>(0); // ✅ AGREGAR
    // Booking State
    const currentBookingId = ref<string | null>(null);

    // Data Collections
    const currencies = ref<Currency[]>([]);
    const selectedCurrency = ref<Currency | null>(null);
    const rateTypes = ref<RateType[]>([]);
    const paymentMethods = ref<PaymentMethod[]>([]);
    const userCashRegister = ref<CashRegister | null>(null);

    // UI State
    const showStartDialog = ref<boolean>(false);
    const processingPayment = ref<boolean>(false);
    const showFinishDialog = ref<boolean>(false);
    const processingFinish = ref<boolean>(false);

    // ==========================================
    // COMPUTED PROPERTIES
    // ==========================================

    const formattedTime = computed(() => {
        // 🔥 FIX: Asegurar que todos los valores sean números enteros
        const totalSecs = Math.abs(Math.floor(remainingSeconds.value));
        const hours = Math.floor(totalSecs / 3600);
        const minutes = Math.floor((totalSecs % 3600) / 60);
        const seconds = Math.floor(totalSecs % 60);
        
        const sign = remainingSeconds.value < 0 ? '-' : '';
        return `${sign}${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    });

    const progressPercentage = computed(() => {
        if (totalSeconds.value === 0) return 0;
        const percentage = (remainingSeconds.value / totalSeconds.value) * 100;
        return Math.max(0, Math.min(100, percentage));
    });

    const currentRoomPrice = computed(() => {
        if (!selectedPricingRange.value) return 0;
        return selectedPricingRange.value.price;
    });

    const availablePricingRanges = computed(() => {
        return roomData.value?.available_pricing_ranges || [];
    });

    const filteredPricingRanges = computed(() => {
        if (!selectedRate.value) return [];
        
        return availablePricingRanges.value.filter(range => 
            range.rate_type.id === selectedRate.value?.id
        );
    });

    const subBranchPolicies = computed(() => {
        return roomData.value?.sub_branch_policies || null;
    });

    const hasToleranceEnabled = computed(() => {
        const policies = subBranchPolicies.value;
        return policies?.time_settings?.apply_tolerance
            || policies?.time?.apply_tolerance
            || false;
    });

    const toleranceMinutes = computed(() => {
        if (!hasToleranceEnabled.value) return 0;
        const policies = subBranchPolicies.value;
        return policies?.time_settings?.extra_tolerance
            || policies?.time?.extra_tolerance
            || 0;
    });

    const calculatePenalty = computed(() => {
    if (!hasExtraTime.value) return { amount: 0, minutes: 0 };

    const policies = subBranchPolicies.value;
    const penaltySettings = policies?.penalty_settings || policies?.penalty;
    if (!penaltySettings?.penalty_active) return { amount: 0, minutes: 0 };

    const exceededSeconds  = Math.abs(remainingSeconds.value);
    const exceededMinutes  = Math.ceil(exceededSeconds / 60);

    // ✅ Descontar tolerancia — durante este tiempo NO se cobra nada
    const toleranceMins    = toleranceMinutes.value;
    const minutosCobrables = exceededMinutes - toleranceMins;

    // ✅ Aún dentro de tolerancia → sin penalización
    if (minutosCobrables <= 0) return { amount: 0, minutes: 0 };

    const intervalMinutes = penaltySettings.charge_interval_minutes || 15;
    const intervals       = Math.ceil(minutosCobrables / intervalMinutes);
    const penaltyMins     = intervals * intervalMinutes;

    let penaltyAmt = 0;

    if (penaltySettings.penalty_type === 'fixed') {
        penaltyAmt = intervals * penaltySettings.amount_per_interval;
    } else if (penaltySettings.penalty_type === 'percentage') {
        const basePrice = currentRoomPrice.value;
        penaltyAmt      = basePrice * (penaltySettings.amount_per_interval / 100) * intervals;
    }

    return {
        amount:  Math.round(penaltyAmt * 100) / 100,
        minutes: penaltyMins
    };
});

    const productsTotal = computed(() => {
        return products.value.reduce((sum, p) => {
            const quantity = parseFloat(String(p.quantity || p.cantidad || 0));
            const price = parseFloat(String(p.precio_venta || p.price || 0));
            return sum + (quantity * price);
        }, 0);
    });

    const roomTotal = computed(() => {
        return currentRoomPrice.value * timeAmount.value;
    });

    const totalAmount = computed(() => {
        let total = roomTotal.value + productsTotal.value + penaltyAmount.value;
        
        // Aplicar impuestos si está configurado
        const taxSettings = subBranchPolicies.value?.tax_settings;
        if (taxSettings && !taxSettings.tax_included) {
            const taxPercentage = taxSettings.tax_percentage / 100;
            total = total * (1 + taxPercentage);
        }
        
        return total.toFixed(2);
    });

    const hasExtraTime = computed(() => {
        return remainingSeconds.value < 0;
    });

    const extraTimeFormatted = computed(() => {
        if (!hasExtraTime.value) return '0h 0m';
        const extraSeconds = Math.abs(remainingSeconds.value);
        const hours = Math.floor(extraSeconds / 3600);
        const minutes = Math.floor((extraSeconds % 3600) / 60);
        return `${hours}h ${minutes}m`;
    });

    const canStartService = computed(() => {
        return (
            roomData.value?.status === 'available' &&
            selectedClient.value !== null &&
            selectedRate.value !== null &&
            selectedPricingRange.value !== null &&
            selectedCurrency.value !== null
        );
    });

    // ==========================================
    // HELPER METHODS
    // ==========================================

    const getRateLabel = (rate: RateType | null): string => {
        return rate?.display_name || rate?.name || '';
    };

    const getTimeUnit = (rate: RateType | null): string => {
        if (!rate) return '';
        
        if (rate.code === 'HOURLY') return 'Hora(s)';
        if (rate.code === 'DAILY') return 'Día(s)';
        if (rate.code === 'NIGHTLY') return 'Noche(s)';
        
        return 'Unidad(es)';
    };

    const getStatusLabel = (status: string): string => {
        const labels: Record<string, string> = {
            available: 'Disponible',
            occupied: 'Ocupada',
            maintenance: 'Mantenimiento',
            cleaning: 'Limpieza'
        };
        return labels[status] || status;
    };

    const getStatusSeverity = (status: string): string => {
        const severities: Record<string, string> = {
            available: 'success',
            occupied: 'danger',
            maintenance: 'warn',
            cleaning: 'info'
        };
        return severities[status] || 'secondary';
    };

    const calculateTotalSeconds = (): number => {
        if (!selectedPricingRange.value) return 0;
        
        const durationHours = selectedPricingRange.value.duration_hours;
        // 🔥 FIX: Asegurar que el resultado sea un número entero
        return Math.floor(durationHours * 3600 * timeAmount.value);
    };

    const getRateTypeId = (): string | number => {
        if (!selectedRate.value) {
            throw new Error('No se ha seleccionado una tarifa');
        }
        
        return selectedRate.value.id;
    };

    // ==========================================
    // API METHODS
    // ==========================================

    const loadNecessaryData = async (): Promise<void> => {
        try {
            const [currenciesRes, paymentMethodsRes, cashRegisterRes, rateTypesRes] = await Promise.all([
                axios.get('/currencies'),
                axios.get('/payments/methods'),
                axios.get('/payments/user-cash-register'),
                axios.get('/rate-types')
            ]);

            currencies.value = currenciesRes.data.data || currenciesRes.data;
            paymentMethods.value = paymentMethodsRes.data.data || paymentMethodsRes.data;
            const cashData = cashRegisterRes.data.data;
userCashRegister.value = cashData?.cash_register ?? null; // ✅
            rateTypes.value = rateTypesRes.data.data || rateTypesRes.data;

            if (currencies.value.length > 0 && !selectedCurrency.value) {
                selectedCurrency.value = currencies.value[0];
            }
        } catch (error: any) {
            console.error('Error al cargar datos:', error);
            
            if (error.response?.status === 404) {
                throw new Error('No tienes una caja abierta. Debes aperturar una caja primero.');
            } else {
                throw new Error('No se pudieron cargar los datos necesarios');
            }
        }
    };

    const syncWithBackend = async (): Promise<void> => {
    if (!roomData.value?.id) return;

    try {
        const response = await axios.get(`/rooms/${roomData.value.id}`);
        const roomInfo = response.data.data;

        // Actualizar pricing ranges y políticas
        if (roomInfo.available_pricing_ranges && roomData.value) {
            roomData.value.available_pricing_ranges = roomInfo.available_pricing_ranges;
        }

        if (roomInfo.sub_branch_policies && roomData.value) {
            roomData.value.sub_branch_policies = roomInfo.sub_branch_policies;
        }

        // ─────────────────────────────────────────────
        // HAY BOOKING ACTIVO
        // ─────────────────────────────────────────────
        if (roomInfo.current_booking) {
            const booking = roomInfo.current_booking;

            if (booking.remaining_seconds !== undefined) {

                // 1. Tiempo restante directo del backend
                remainingSeconds.value = Math.floor(booking.remaining_seconds);
                isTimerRunning.value   = true;
                currentBookingId.value = booking.booking_id;

                // 2. Penalización
                penaltyAmount.value  = booking.penalty_amount  || 0;
                penaltyMinutes.value = booking.penalty_minutes || 0;
                roomSubtotal.value = parseFloat(String(booking.room_subtotal || 0));
                // 3. Cliente
                if (booking.guest_name && booking.guest_client_id) {
                    selectedClient.value = {
                        id:              booking.guest_client_id,
                        name:            booking.guest_name,
                        document_number: booking.guest_document
                    };
                }

                // 4. Tipo de tarifa
                const rateType = roomInfo.available_pricing_ranges?.find(
                    (r: PricingRange) => r.rate_type.id === booking.rate_type_id
                )?.rate_type;

                if (rateType) {
                    selectedRate.value = rateType;
                }

                // 5. Rango de precio aplicable
                if (booking.applicable_pricing_range) {
                    const applicableRange = roomInfo.available_pricing_ranges?.find(
                        (r: PricingRange) => r.id === booking.applicable_pricing_range.id
                    );
                    if (applicableRange) {
                        selectedPricingRange.value = applicableRange;
                    }
                }

                // 6. timeAmount = quantity del booking (cuántas unidades contrató)
                timeAmount.value = booking.quantity ?? 1;

                // 7. totalSeconds: calculado desde check_in y check_out
                // Esto es exacto sin importar si son minutos, horas o días
                // check_in + check_out vienen del backend y son la fuente de verdad
                if (booking.check_in && booking.check_out) {
                    const checkInMs  = new Date(booking.check_in).getTime();
                    const checkOutMs = new Date(booking.check_out).getTime();
                    totalSeconds.value = Math.floor((checkOutMs - checkInMs) / 1000);
                } else if (booking.applicable_pricing_range) {
                    // Fallback: usar time_to_minutes del rango aplicable
                    totalSeconds.value = booking.applicable_pricing_range.time_to_minutes
                        * (booking.quantity ?? 1)
                        * 60;
                } else {
                    // Último fallback: elapsed + remaining
                    totalSeconds.value = Math.floor(
                        (booking.elapsed_minutes * 60) + booking.remaining_seconds
                    );
                }

                // 8. Comprobante
                voucherType.value = booking.voucher_type || 'boleta';

                // 9. Consumos/productos
                if (booking.consumptions && booking.consumptions.length > 0) {
                    products.value = booking.consumptions.map((c: any) => ({
                        id:             c.product_id,
                        nombre:         c.product_name,
                        cantidad:       c.quantity,
                        precio_venta:   c.unit_price,
                        quantity:       c.quantity,
                        price:          c.unit_price,
                        status:         c.status,
                        consumed_at:    c.consumed_at,
                        consumption_id: c.id
                    }));
                }

                // 10. Iniciar cronómetro local si no está corriendo
                if (!timerInterval.value) {
                    startLocalTimer();
                }
            }

            // Actualizar estado de la habitación
            if (roomData.value) {
                roomData.value.status = roomInfo.status;
            }

        } else {
            // ─────────────────────────────────────────────
            // NO HAY BOOKING ACTIVO → limpiar todo
            // ─────────────────────────────────────────────
            stopLocalTimer();
            isTimerRunning.value   = false;
            currentBookingId.value = null;
            penaltyAmount.value    = 0;
            penaltyMinutes.value   = 0;
            roomSubtotal.value     = 0;
        }

    } catch (error: any) {
        console.error('Error al sincronizar con backend:', error);
    }
};

    // ==========================================
    // TIMER METHODS
    // ==========================================

    const startLocalTimer = (): void => {
        if (timerInterval.value) return;

        timerInterval.value = setInterval(() => {
            // 🔥 FIX: Asegurar que el decremento sea de 1 segundo exacto
            remainingSeconds.value = Math.floor(remainingSeconds.value - 1);
            
            // Actualizar penalización en tiempo real
            const penalty = calculatePenalty.value;
            penaltyAmount.value = penalty.amount;
            penaltyMinutes.value = penalty.minutes;
            
            if (remainingSeconds.value === 0) {
                toast.add({
                    severity: 'warn',
                    summary: '⚠️ Tiempo Contratado Agotado',
                    detail: 'A partir de ahora se cobrará tiempo extra.',
                    life: 8000
                });
            }
            
            // Alerta cuando se exceda la tolerancia
            const toleranceSecs = toleranceMinutes.value * 60;
            if (remainingSeconds.value === -toleranceSecs && hasToleranceEnabled.value) {
                toast.add({
                    severity: 'error',
                    summary: '🚨 Tolerancia Excedida',
                    detail: 'Se aplicarán cargos por penalización.',
                    life: 8000
                });
            }
        }, 1000);
    };

    const stopLocalTimer = (): void => {
        if (timerInterval.value) {
            clearInterval(timerInterval.value);
            timerInterval.value = null;
        }
    };

    const startSyncInterval = (): void => {
        if (syncInterval.value) return;

        syncInterval.value = setInterval(() => {
            syncWithBackend();
        }, 30000); // Cada 30 segundos
    };

    const stopSyncInterval = (): void => {
        if (syncInterval.value) {
            clearInterval(syncInterval.value);
            syncInterval.value = null;
        }
    };

    // ==========================================
    // SERVICE ACTIONS
    // ==========================================

    const selectRate = (rateType: RateType): void => {
        if (!isTimerRunning.value) {
            selectedRate.value = rateType;
            selectedPricingRange.value = null; // Reset pricing range
            timeAmount.value = 1; // Reset time amount
        }
    };

    const selectPricingRange = (range: PricingRange): void => {
        if (!isTimerRunning.value) {
            selectedPricingRange.value = range;
            remainingSeconds.value = calculateTotalSeconds();
            totalSeconds.value = remainingSeconds.value;
        }
    };

    const updateTimeAmount = (amount: number): void => {
        if (!isTimerRunning.value) {
            timeAmount.value = amount;
            remainingSeconds.value = calculateTotalSeconds();
            totalSeconds.value = remainingSeconds.value;
        }
    };

    const setCustomer = (customer: Customer): void => {
        console.log('✅ Cliente guardado en store:', customer);
        selectedClient.value = customer;
    };

    const updateProducts = (newProducts: Product[]): void => {
        products.value = newProducts;
    };

    const confirmStartService = async (): Promise<void> => {
        if (!selectedClient.value) {
            toast.add({
                severity: 'warn',
                summary: 'Cliente Requerido',
                detail: 'Debe registrar un cliente primero',
                life: 3000
            });
            return;
        }
        
        if (!selectedClient.value.id) {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'El cliente no tiene un ID válido',
                life: 4000
            });
            return;
        }
        
        if (!selectedRate.value) {
            toast.add({
                severity: 'warn',
                summary: 'Tarifa Requerida',
                detail: 'Debe seleccionar una tarifa',
                life: 3000
            });
            return;
        }

        if (!selectedPricingRange.value) {
            toast.add({
                severity: 'warn',
                summary: 'Rango de Precio Requerido',
                detail: 'Debe seleccionar un rango de tiempo',
                life: 3000
            });
            return;
        }
        
        if (!selectedCurrency.value) {
            toast.add({
                severity: 'warn',
                summary: 'Moneda Requerida',
                detail: 'Debe seleccionar una moneda',
                life: 3000
            });
            return;
        }

        try {
            await loadNecessaryData();
            showStartDialog.value = true;
        } catch (error: any) {
            console.error('Error al cargar datos:', error);
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.message || 'No se pudieron cargar los datos necesarios',
                life: 4000
            });
        }
    };

    const startService = async (payload: StartServicePayload): Promise<void> => {
        if (!roomData.value || !selectedClient.value || !selectedCurrency.value || !selectedPricingRange.value) {
            throw new Error('Faltan datos requeridos');
        }

        processingPayment.value = true;

        try {
            const bookingData = {
                room_id: roomData.value.id,
                customers_id: selectedClient.value.id,
                rate_type_id: getRateTypeId(),
                pricing_range_id: selectedPricingRange.value.id,
                currency_id: selectedCurrency.value.id,
                quantity: timeAmount.value,
                rate_per_hour: currentRoomPrice.value,
                voucher_type: voucherType.value,
                
                payments: [
                    {
                        payment_method_id: payload.paymentMethod.id,
                        amount: parseFloat(totalAmount.value),
                        cash_register_id: userCashRegister.value?.id,
                        operation_number: payload.paymentMethod.requires_reference 
                            ? payload.operationNumber 
                            : null
                    }
                ],
                
                consumptions: products.value.length > 0 
                    ? products.value.map(p => ({
                        product_id: p.id,
                        quantity: parseFloat(String(p.quantity || p.cantidad || 0)),
                        unit_price: parseFloat(String(p.precio_venta || p.price || 0))
                    }))
                    : []
            };

            console.log('📤 Enviando booking:', bookingData);

            const response = await axios.post('/bookings', bookingData);

            console.log('✅ Respuesta del servidor:', response.data);

            currentBookingId.value = response.data.data?.booking?.id || null;

            toast.add({
                severity: 'success',
                summary: '✅ Servicio Iniciado',
                detail: response.data.message || 'Habitación ocupada correctamente',
                life: 4000
            });

            showStartDialog.value = false;
            
            totalSeconds.value = calculateTotalSeconds();
            remainingSeconds.value = totalSeconds.value;
            isTimerRunning.value = true;
            
            startLocalTimer();

            if (roomData.value) {
                roomData.value.status = 'occupied';
            }

        } catch (error: any) {
            console.error('❌ Error al crear booking:', error);
            
            if (error.response?.data?.errors) {
                const errors = error.response.data.errors;
                Object.keys(errors).forEach(key => {
                    toast.add({
                        severity: 'error',
                        summary: 'Error de Validación',
                        detail: `${key}: ${Array.isArray(errors[key]) ? errors[key][0] : errors[key]}`,
                        life: 5000
                    });
                });
            } else if (error.response?.data?.message) {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: error.response.data.message,
                    life: 5000
                });
            } else {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: error.message || 'Error al crear el booking',
                    life: 5000
                });
            }
            throw error;
        } finally {
            processingPayment.value = false;
        }
    };

    const confirmFinishService = (): void => {
        if (!currentBookingId.value) {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se encontró el ID del booking activo',
                life: 4000
            });
            return;
        }

        showFinishDialog.value = true;
    };
    const finishService = async (payload: FinishServicePayload): Promise<void> => {
    if (!currentBookingId.value) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se encontró el ID del booking activo',
            life: 4000
        });
        return;
    }

    processingFinish.value = true;

    try {
        // ✅ Recargar caja activa por si se perdió
        if (!userCashRegister.value?.id) {
            const cashRegisterRes = await axios.get('/payments/user-cash-register');
            const cashData = cashRegisterRes.data.data;
            userCashRegister.value = cashData?.cash_register ?? null;
        }

        // ✅ Si aún no hay caja, abortar
        if (!userCashRegister.value?.id) {
            toast.add({
                severity: 'error',
                summary: 'Sin Caja Activa',
                detail: 'No tienes una caja registradora abierta. Por favor abre una caja primero.',
                life: 5000
            });
            return;
        }

        const finishData: any = {
            notes: payload.notes || undefined,
        };

        // ✅ Calcular el monto real a cobrar (consumos pending + penalización)
        const pendingProductsTotal = products.value
            .filter(p => p.status === 'pending')
            .reduce((sum, p) => {
                const qty = parseFloat(String(p.quantity || p.cantidad || 0));
                const price = parseFloat(String(p.precio_venta || p.price || 0));
                return sum + (qty * price);
            }, 0);

        const penaltyTotal = penaltyAmount.value || 0;
        const montoAdicional = parseFloat((pendingProductsTotal + penaltyTotal).toFixed(2));

        if (payload.paymentMethod && montoAdicional > 0) {
            finishData.payments = [
                {
                    payment_method_id: payload.paymentMethod.id,
                    amount: montoAdicional,
                    cash_register_id: userCashRegister.value.id, // ✅ ya validado
                    operation_number: payload.paymentMethod.requires_reference
                        ? payload.operationNumber
                        : null
                }
            ];
        }

        console.log('📤 Finalizando booking:', currentBookingId.value, finishData);

        const response = await axios.post(`/bookings/${currentBookingId.value}/finish`, finishData);

        console.log('✅ Respuesta del servidor:', response.data);

        stopLocalTimer();
        isTimerRunning.value = false;

        toast.add({
            severity: 'success',
            summary: '✅ Servicio Finalizado',
            detail: response.data.message || 'Habitación pasa a limpieza',
            life: 4000
        });

        showFinishDialog.value = false;

        if (penaltyTotal > 0) {
            toast.add({
                severity: 'info',
                summary: '⏱️ Penalización Cobrada',
                detail: `Se cobró S/. ${penaltyTotal.toFixed(2)} por tiempo extra`,
                life: 6000
            });
        }

        if (pendingProductsTotal > 0) {
            toast.add({
                severity: 'info',
                summary: '🛒 Consumos Cobrados',
                detail: `Se cobró S/. ${pendingProductsTotal.toFixed(2)} por consumos adicionales`,
                life: 6000
            });
        }

        setTimeout(() => {
            window.location.reload();
        }, 2000);

    } catch (error: any) {
        console.error('❌ Error al finalizar booking:', error);

        if (error.response?.data?.message) {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response.data.message,
                life: 5000
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.message || 'Error al finalizar el servicio',
                life: 5000
            });
        }
        throw error;
    } finally {
        processingFinish.value = false;
    }
};
    // ==========================================
    // INITIALIZATION & CLEANUP
    // ==========================================

    const initialize = async (room: RoomData): Promise<void> => {
        console.log('🚀 Inicializando habitación:', room.room_number);
        
        roomData.value = room;
        
        try {
            await loadNecessaryData();
            await syncWithBackend();
            startSyncInterval();
            
            if (selectedRate.value && selectedPricingRange.value && !isTimerRunning.value) {
                remainingSeconds.value = calculateTotalSeconds();
            }
            
            console.log('✅ Inicialización completa');
        } catch (error: any) {
            console.error('❌ Error en inicialización:', error);
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.message,
                life: 5000
            });
        }
    };

    const cleanup = (): void => {
        console.log('🧹 Limpiando store...');
        
        stopLocalTimer();
        stopSyncInterval();
        resetState();
        
        console.log('✅ Store completamente limpio');
    };

    const resetState = (): void => {
        roomData.value = null;
        selectedRate.value = null;
        selectedPricingRange.value = null;
        timeAmount.value = 1;
        voucherType.value = 'boleta';
        selectedClient.value = null;
        products.value = [];
        isTimerRunning.value = false;
        remainingSeconds.value = 0;
        totalSeconds.value = 0;
        penaltyAmount.value = 0;
        penaltyMinutes.value = 0;
        roomSubtotal.value = 0;
        currentBookingId.value = null;
        showStartDialog.value = false;
        showFinishDialog.value = false;
        processingPayment.value = false;
        processingFinish.value = false;
        
        console.log('✅ Estado reseteado a valores iniciales');
    };

    // ==========================================
    // RETURN (PUBLIC API)
    // ==========================================

    return {
        // State
        roomData,
        selectedRate,
        selectedPricingRange,
        timeAmount,
        voucherType,
        selectedClient,
        products,
        isTimerRunning,
        remainingSeconds,
        totalSeconds,
        currentBookingId,
        penaltyAmount,
        penaltyMinutes,
        roomSubtotal,
        currencies,
        selectedCurrency,
        rateTypes,
        paymentMethods,
        userCashRegister,
        showStartDialog,
        processingPayment,
        showFinishDialog,
        processingFinish,

        // Computed
        formattedTime,
        progressPercentage,
        currentRoomPrice,
        availablePricingRanges,
        filteredPricingRanges,
        subBranchPolicies,
        hasToleranceEnabled,
        toleranceMinutes,
        calculatePenalty,
        productsTotal,
        roomTotal,
        totalAmount,
        hasExtraTime,
        extraTimeFormatted,
        canStartService,

        // Helpers
        getRateLabel,
        getTimeUnit,
        getStatusLabel,
        getStatusSeverity,

        // Actions
        selectRate,
        selectPricingRange,
        updateTimeAmount,
        setCustomer,
        updateProducts,
        confirmStartService,
        startService,
        confirmFinishService,
        finishService,
        loadNecessaryData,
        syncWithBackend,
        initialize,
        cleanup,
        resetState
    };
});