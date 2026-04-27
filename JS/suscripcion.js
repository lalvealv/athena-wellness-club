// Espera a que el HTML esté completamente cargado
document.addEventListener("DOMContentLoaded", () => {
    // Carga la información de la suscripción del socio
    cargarSuscripcion();

    // Obtiene el botón de cancelar suscripción
    const botonCancelar = document.getElementById("btn-cancelar-suscripcion");

    // Asocia el botón con la función de cancelación
    botonCancelar.addEventListener("click", async () => {
        await cancelarSuscripcion();
    });
});

// Muestra mensajes de estado en la pantalla de suscripción
function mostrarMensaje(tipo, texto) {
    const mensaje = document.getElementById("mensaje-suscripcion");
    mensaje.className = `form-message ${tipo}`;
    mensaje.textContent = texto;
}

// Aplica una clase visual al estado de la suscripción
function aplicarBadgeEstado(estado) {
    const elementoEstado = document.getElementById("estado");
    elementoEstado.textContent = estado || "No disponible";
    elementoEstado.className = "status-badge";

    if (estado === "Activa") {
        elementoEstado.classList.add("status-badge--ok");
    } else if (estado === "Pausada") {
        elementoEstado.classList.add("status-badge--wait");
    } else if (estado === "Cancelada" || estado === "Finalizada") {
        elementoEstado.classList.add("status-badge--cancel");
    }
}

// Muestra avisos especiales según el estado o fecha de renovación
function mostrarAvisoSuscripcion(data) {
    const aviso = document.getElementById("aviso-suscripcion");
    aviso.style.display = "none";
    aviso.className = "subscription-alert";

    // Si no hay datos válidos, no muestra aviso
    if (!data || !data.estado) {
        return;
    }

    // Aviso si la suscripción activa se renueva pronto
    if (data.estado === "Activa" && typeof data.dias_para_renovacion === "number") {
        if (data.dias_para_renovacion === 0) {
            aviso.textContent = "Tu suscripción se renueva hoy.";
            aviso.classList.add("subscription-alert--info");
            aviso.style.display = "block";
            return;
        }

        if (data.dias_para_renovacion > 0 && data.dias_para_renovacion <= 7) {
            aviso.textContent = `Tu suscripción se renueva en ${data.dias_para_renovacion} día${data.dias_para_renovacion === 1 ? "" : "s"}.`;
            aviso.classList.add("subscription-alert--info");
            aviso.style.display = "block";
            return;
        }
    }

    // Aviso si la suscripción está cancelada
    if (data.estado === "Cancelada") {
        aviso.textContent = `Tu suscripción está cancelada y seguirá activa hasta el ${data.fecha_renovacion}. Después pasará a finalizada.`;
        aviso.classList.add("subscription-alert--warning");
        aviso.style.display = "block";
        return;
    }

    // Aviso si la suscripción está finalizada
    if (data.estado === "Finalizada") {
        aviso.textContent = "Tu suscripción está finalizada. Si lo deseas, puedes volver a contratar un plan.";
        aviso.classList.add("subscription-alert--error");
        aviso.style.display = "block";
    }
}

// Carga la información de suscripción desde el backend
async function cargarSuscripcion() {
    try {
        // Solicita los datos al archivo PHP
        const response = await fetch("../API/suscripcion.php", {
            method: "GET",
            credentials: "same-origin"
        });

        // Convierte la respuesta a JSON
        const data = await response.json();

        // Si hay error, muestra mensaje
        if (!response.ok || !data.ok) {
            mostrarError(data.mensaje || "No se pudo cargar la suscripción.");
            return;
        }

        // Construye el nombre completo del usuario
        const nombreCompleto = `${data.nombre} ${data.apellidos}`.trim();

        // Carga los datos del usuario en el sidebar
        document.getElementById("sidebar-foto").src = data.foto_perfil || "../img-socios/socio1.png";
        document.getElementById("sidebar-nombre").textContent = nombreCompleto || "Usuario";
        document.getElementById("sidebar-plan").textContent = data.membresia || "Sin plan";

        // Carga los datos principales de la suscripción
        document.getElementById("plan").textContent = data.membresia || "Sin suscripción activa";
        document.getElementById("precio").textContent = data.cuota || "No disponible";
        document.getElementById("periodo").textContent = data.periodo || "No disponible";
        document.getElementById("horario").textContent = data.horario || "No disponible";
        document.getElementById("fecha-inicio").textContent = data.fecha_inicio || "No disponible";
        document.getElementById("fecha-renovacion").textContent = data.fecha_renovacion || "No disponible";
        document.getElementById("renovacion-automatica").textContent = data.renovacion_automatica || "No disponible";
        document.getElementById("descripcion").textContent = data.descripcion || "Sin descripción disponible.";

        // Aplica estado visual y aviso informativo
        aplicarBadgeEstado(data.estado);
        mostrarAvisoSuscripcion(data);

        // Controla si el botón de cancelar debe estar activo o desactivado
        const botonCancelar = document.getElementById("btn-cancelar-suscripcion");

        if (data.estado === "Cancelada" || data.estado === "Finalizada" || !data.id_suscripcion) {
            botonCancelar.disabled = true;
            botonCancelar.textContent = "Suscripción no cancelable";
        } else {
            botonCancelar.disabled = false;
            botonCancelar.textContent = "Cancelar suscripción";
        }

        // Muestra mensaje según el estado actual de la suscripción
        if (data.estado === "Cancelada") {
            mostrarMensaje("warning", "Tu suscripción está cancelada. No se realizará el siguiente cobro automático.");
        } else if (data.estado === "Finalizada") {
            mostrarMensaje("error", "Tu suscripción está finalizada.");
        } else {
            mostrarMensaje("success", "Suscripción cargada correctamente.");
        }

    } catch (error) {
        // Si ocurre un error inesperado, muestra error
        mostrarError("Error de conexión al cargar la suscripción.");
        console.error(error);
    }
}

// Cancela la suscripción del usuario
async function cancelarSuscripcion() {
    // Pide confirmación antes de cancelar
    const confirmar = confirm(
        "Si cancelas tu suscripción, no se realizará el siguiente cobro automático.\n\n¿Deseas continuar?"
    );

    if (!confirmar) {
        mostrarMensaje("warning", "Cancelación anulada.");
        return;
    }

    // Muestra mensaje de carga
    mostrarMensaje("loading", "Cancelando suscripción...");

    try {
        // Prepara los datos para enviar al backend
        const formData = new FormData();
        formData.append("accion", "cancelar");

        // Envía la petición de cancelación
        const response = await fetch("../API/suscripcion.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        // Convierte la respuesta a JSON
        const data = await response.json();

        // Si hay error, muestra mensaje
        if (!response.ok || !data.ok) {
            mostrarMensaje("error", data.mensaje || "No se pudo cancelar la suscripción.");
            return;
        }

        // Muestra confirmación y recarga datos actualizados
        mostrarMensaje("success", data.mensaje);
        await cargarSuscripcion();

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola
        console.error(error);
        mostrarMensaje("error", "Ha ocurrido un error al cancelar la suscripción.");
    }
}

// Muestra errores generales en la interfaz
function mostrarError(mensaje) {
    // Sidebar
    document.getElementById("sidebar-nombre").textContent = "Error";
    document.getElementById("sidebar-plan").textContent = mensaje;

    // Datos de la suscripción
    document.getElementById("plan").textContent = mensaje;
    document.getElementById("precio").textContent = mensaje;
    document.getElementById("periodo").textContent = mensaje;
    document.getElementById("horario").textContent = mensaje;
    document.getElementById("fecha-inicio").textContent = mensaje;
    document.getElementById("fecha-renovacion").textContent = mensaje;
    document.getElementById("renovacion-automatica").textContent = mensaje;
    document.getElementById("descripcion").textContent = mensaje;

    // Estado visual de error
    aplicarBadgeEstado("Error");

    // Mensaje final
    mostrarMensaje("error", mensaje);
}