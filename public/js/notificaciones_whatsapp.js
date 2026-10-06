// ============================================
// SISTEMA DE NOTIFICACIONES POR WHATSAPP
// ============================================

/**
 * Envía notificación de reserva confirmada por WhatsApp
 * @param {Object} datosReserva - Datos de la reserva confirmada
 */
function enviarNotificacionReservaWhatsApp(datosReserva) {
    const {
        clienteNombre,
        clienteTelefono,
        numeroMesa,
        fechaReserva,
        horaReserva,
        numeroPersonas,
        numeroNota,
        platosIncluidos = [],
        precioMesa = 0,
        subtotalPlatos = 0,
        impuesto = 0,
        total = 0,
        tienePlatos = false
    } = datosReserva;

    // Limpiar teléfono
    const telefonoLimpio = clienteTelefono.replace(/\D/g, '');

    // Construir mensaje según si tiene platos o no
    let mensaje = `¡Hola ${clienteNombre}! ✨

¡Su reserva ha sido confirmada exitosamente! 🎉

📋 *DETALLES DE SU RESERVA*
━━━━━━━━━━━━━━━━━━━━
🎫 Nota: ${numeroNota}
📅 Fecha: ${fechaReserva}
🕐 Hora: ${horaReserva}
🪑 Mesa: ${numeroMesa}
👥 Personas: ${numeroPersonas}
━━━━━━━━━━━━━━━━━━━━`;

    // Si hay platos incluidos
    if (tienePlatos && platosIncluidos.length > 0) {
        mensaje += `

🍽️ *PLATOS RESERVADOS*
━━━━━━━━━━━━━━━━━━━━`;

        platosIncluidos.forEach(plato => {
            mensaje += `
• ${plato.nombre} x${plato.cantidad}
  $${parseFloat(plato.subtotal).toFixed(2)}`;
        });

        mensaje += `

💰 *RESUMEN DE PAGO*
━━━━━━━━━━━━━━━━━━━━
Reserva de Mesa: $${parseFloat(precioMesa).toFixed(2)}
Platos: $${parseFloat(subtotalPlatos).toFixed(2)}
Subtotal: $${parseFloat(subtotalPlatos + precioMesa).toFixed(2)}
IVA (12%): $${parseFloat(impuesto).toFixed(2)}
━━━━━━━━━━━━━━━━━━━━
✨ *TOTAL: $${parseFloat(total).toFixed(2)}* ✨`;
    } else {
        mensaje += `

💰 *VALOR DE RESERVA*
━━━━━━━━━━━━━━━━━━━━
Reserva de Mesa: $${parseFloat(precioMesa).toFixed(2)}`;
    }

    mensaje += `

📍 *Le Salon de Lumière*
Un placer servirle.

⚠️ *Importante:*
• Llegue 10 minutos antes de su hora
• En caso de cancelación, avise con 24h
• Mantenga esta confirmación

¡Le esperamos! 🌟`;

    // Codificar mensaje para URL
    const mensajeCodificado = encodeURIComponent(mensaje);

    // Construir URL de WhatsApp
    const urlWhatsApp = `https://wa.me/593${telefonoLimpio}?text=${mensajeCodificado}`;

    // Abrir WhatsApp en nueva pestaña
    window.open(urlWhatsApp, '_blank');

    // Mostrar confirmación al admin/usuario
    return true;
}

/**
 * Pregunta si desea enviar notificación por WhatsApp después de crear reserva
 * @param {Object} datosReserva - Datos de la reserva
 */
async function preguntarEnviarWhatsApp(datosReserva) {
    const result = await Swal.fire({
        title: '📱 Notificar al Cliente',
        html: `
            <div style="text-align: left; color: white;">
                <p style="margin-bottom: 15px;">¿Desea enviar confirmación por WhatsApp a:</p>
                <div style="background: rgba(37, 211, 102, 0.2); padding: 15px; border-radius: 10px; margin-bottom: 15px;">
                    <p style="margin: 5px 0;"><strong>Cliente:</strong> ${datosReserva.clienteNombre}</p>
                    <p style="margin: 5px 0;"><strong>Teléfono:</strong> ${datosReserva.clienteTelefono}</p>
                    <p style="margin: 5px 0;"><strong>Mesa:</strong> ${datosReserva.numeroMesa}</p>
                    <p style="margin: 5px 0;"><strong>Fecha:</strong> ${datosReserva.fechaReserva} - ${datosReserva.horaReserva}</p>
                </div>
                <p style="color: #25D366; font-size: 0.9rem;">
                    💡 Se abrirá WhatsApp Web con el mensaje pre-escrito
                </p>
            </div>
        `,
        icon: 'question',
        background: '#1a1a1a',
        color: '#ffffff',
        showCancelButton: true,
        confirmButtonText: '📱 Sí, enviar WhatsApp',
        cancelButtonText: 'No, solo guardar',
        confirmButtonColor: '#25D366',
        cancelButtonColor: '#666',
        width: '600px'
    });

    if (result.isConfirmed) {
        enviarNotificacionReservaWhatsApp(datosReserva);

        // Pequeño delay para que se abra WhatsApp antes de continuar
        await new Promise(resolve => setTimeout(resolve, 500));

        return true;
    }

    return false;
}

/**
 * Enviar notificación automáticamente sin preguntar
 * @param {Object} datosReserva - Datos de la reserva
 */
function enviarWhatsAppAutomatico(datosReserva) {
    // Toast de notificación
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: '📱 Abriendo WhatsApp...',
        text: `Enviando confirmación a ${datosReserva.clienteNombre}`,
        showConfirmButton: false,
        timer: 2000,
        background: '#1a1a1a',
        color: '#ffffff'
    });

    // Enviar después de un pequeño delay
    setTimeout(() => {
        enviarNotificacionReservaWhatsApp(datosReserva);
    }, 300);
}
