<template>
    <Dialog 
        v-model:visible="dialogVisible" 
        modal 
        header="Vista previa del comprobante" 
        :style="{ width: '50vw' }"
        @hide="handleClose"
    >
        <div v-if="loading" class="flex justify-center p-4">
            <i class="pi pi-spin pi-spinner" style="font-size: 2rem"></i>
        </div>
        <iframe v-else :src="localPdfUrl" width="100%" height="600px"></iframe>
    </Dialog>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import Dialog from 'primevue/dialog';
import axios from 'axios';
import jsPDF from 'jspdf';

const props = defineProps({
    bookingId: {
        type: [String, Number],
        required: true
    },
    visible: {
        type: Boolean,
        required: true
    }
});

const emit = defineEmits(['update:visible', 'close']);

const dialogVisible = ref(props.visible);
const loading = ref(true);
const localPdfUrl = ref('');

watch(() => props.visible, (newValue) => {
    dialogVisible.value = newValue;
    if (newValue) {
        generatePDF();
    }
});

watch(() => dialogVisible.value, (newValue) => {
    emit('update:visible', newValue);
    if (!newValue) {
        emit('close');
        limpiarPDF();
    }
});

const handleClose = () => {
    emit('close');
    limpiarPDF();
};

const limpiarPDF = () => {
    if (localPdfUrl.value) {
        URL.revokeObjectURL(localPdfUrl.value);
        localPdfUrl.value = '';
    }
};

const generatePDF = async () => {
    loading.value = true;
    try {
        // Obtener datos del ticket
        const response = await axios.get(`/bookings/${props.bookingId}/ticket`);
        const data = response.data.data;

        // Crear PDF tipo ticket (80mm de ancho)
        const pdf = new jsPDF({
            orientation: 'portrait',
            unit: 'mm',
            format: [80, 200] // Ancho fijo 80mm, alto variable
        });

        let y = 10;
        const lineHeight = 5;
        const margin = 5;
        const maxWidth = 70;

        // Encabezado
        pdf.setFontSize(14);
        pdf.setFont('helvetica', 'bold');
        pdf.text(data.empresa.nombre, 40, y, { align: 'center' });
        y += lineHeight;

        pdf.setFontSize(8);
        pdf.setFont('helvetica', 'normal');
        pdf.text(`RUC: ${data.empresa.ruc}`, 40, y, { align: 'center' });
        y += lineHeight;
        pdf.text(data.empresa.direccion, 40, y, { align: 'center' });
        y += lineHeight;
        pdf.text(`Tel: ${data.empresa.telefono}`, 40, y, { align: 'center' });
        y += lineHeight + 3;

        // Línea divisoria
        pdf.setLineDash([2, 2]);
        pdf.line(margin, y, 75, y);
        y += 5;

        // Tipo de comprobante
        pdf.setFontSize(12);
        pdf.setFont('helvetica', 'bold');
        pdf.text(data.comprobante.tipo, 40, y, { align: 'center' });
        y += lineHeight;
        pdf.setFontSize(9);
        pdf.text(data.comprobante.numero, 40, y, { align: 'center' });
        y += lineHeight + 2;

        // Fecha y hora
        pdf.setFontSize(8);
        pdf.setFont('helvetica', 'normal');
        pdf.text(`Fecha: ${data.comprobante.fecha}`, margin, y);
        pdf.text(`Hora: ${data.comprobante.hora}`, 50, y);
        y += lineHeight + 3;

        // Línea divisoria
        pdf.line(margin, y, 75, y);
        y += 5;

        // Cliente
        pdf.setFont('helvetica', 'bold');
        pdf.text('CLIENTE:', margin, y);
        y += lineHeight;
        pdf.setFont('helvetica', 'normal');
        pdf.text(data.cliente.nombre, margin, y);
        y += lineHeight;
        pdf.text(data.cliente.documento, margin, y);
        y += lineHeight + 3;

        // Línea divisoria
        pdf.line(margin, y, 75, y);
        y += 5;

        // Habitación
        pdf.setFont('helvetica', 'bold');
        pdf.text('HABITACIÓN:', margin, y);
        y += lineHeight;
        pdf.setFont('helvetica', 'normal');
        pdf.text(`Nro: ${data.habitacion.numero}`, margin, y);
        y += lineHeight;
        pdf.text(`Tipo: ${data.habitacion.tipo}`, margin, y);
        y += lineHeight;
        pdf.text(`Tarifa: ${data.habitacion.tarifa}`, margin, y);
        y += lineHeight;
        pdf.setFont('helvetica', 'bold');
        pdf.text(`${data.habitacion.cantidad} x S/ ${data.habitacion.precioUnitario.toFixed(2)}`, margin, y);
        pdf.text(`S/ ${data.habitacion.total.toFixed(2)}`, 75, y, { align: 'right' });
        y += lineHeight + 3;

        // Productos (si hay)
        if (data.productos && data.productos.length > 0) {
            pdf.line(margin, y, 75, y);
            y += 5;
            
            pdf.setFont('helvetica', 'bold');
            pdf.text('PRODUCTOS:', margin, y);
            y += lineHeight;
            
            pdf.setFont('helvetica', 'normal');
            data.productos.forEach(producto => {
                pdf.text(producto.nombre, margin, y);
                y += lineHeight;
                pdf.text(`${producto.cantidad} x S/ ${producto.precio.toFixed(2)}`, margin + 2, y);
                pdf.text(`S/ ${producto.total.toFixed(2)}`, 75, y, { align: 'right' });
                y += lineHeight;
            });
            y += 2;
        }

        // Línea divisoria
        pdf.line(margin, y, 75, y);
        y += 5;

        // Totales
        pdf.setFont('helvetica', 'normal');
        pdf.text('Subtotal:', margin, y);
        pdf.text(`S/ ${data.totales.subtotal.toFixed(2)}`, 75, y, { align: 'right' });
        y += lineHeight;

        if (data.totales.descuento > 0) {
            pdf.text('Descuento:', margin, y);
            pdf.text(`-S/ ${data.totales.descuento.toFixed(2)}`, 75, y, { align: 'right' });
            y += lineHeight;
        }

        if (data.totales.igv > 0) {
            pdf.text('IGV (18%):', margin, y);
            pdf.text(`S/ ${data.totales.igv.toFixed(2)}`, 75, y, { align: 'right' });
            y += lineHeight;
        }

        // Total
        pdf.setLineDash([]);
        pdf.line(margin, y, 75, y);
        y += 5;
        
        pdf.setFontSize(10);
        pdf.setFont('helvetica', 'bold');
        pdf.text('TOTAL:', margin, y);
        pdf.text(`S/ ${data.totales.total.toFixed(2)}`, 75, y, { align: 'right' });
        y += lineHeight + 3;

        // Línea divisoria
        pdf.setLineDash([2, 2]);
        pdf.line(margin, y, 75, y);
        y += 5;

        // Método de pago
        pdf.setFontSize(8);
        pdf.setFont('helvetica', 'normal');
        pdf.text(`Método de Pago: ${data.pago.metodo}`, margin, y);
        y += lineHeight;
        
        if (data.pago.operacion) {
            pdf.text(`Nro. Operación: ${data.pago.operacion}`, margin, y);
            y += lineHeight + 3;
        } else {
            y += 3;
        }

        // Línea divisoria
        pdf.line(margin, y, 75, y);
        y += 5;

        // Footer
        pdf.setFont('helvetica', 'bold');
        pdf.text('¡GRACIAS POR SU PREFERENCIA!', 40, y, { align: 'center' });
        y += lineHeight;
        pdf.setFont('helvetica', 'normal');
        pdf.text(data.footer.mensaje, 40, y, { align: 'center' });
        y += lineHeight + 2;
        pdf.setFontSize(6);
        pdf.text(data.footer.sistema, 40, y, { align: 'center' });

        // Convertir a Blob URL
        const pdfBlob = pdf.output('blob');
        localPdfUrl.value = URL.createObjectURL(pdfBlob);

    } catch (error) {
        console.error('Error al generar PDF:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    if (props.bookingId && props.visible) {
        generatePDF();
    }
});

watch(() => props.bookingId, (newId) => {
    if (newId && dialogVisible.value) {
        generatePDF();
    }
});
</script>