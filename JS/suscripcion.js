document.addEventListener("DOMContentLoaded", () => {
    cargarSuscripcion();

    const botonCancelar = document.getElementById("btn-cancelar-suscripcion");
    botonCancelar.addEventListener("click", async () => {
        await cancelarSuscripcion();
    });
});

function mostrarMensaje(tipo, texto) {
    const mensaje = document.getElementById("mensaje-suscripcion");
    mensaje.className = `form-message ${tipo}`;
    mensaje.textContent = texto;
}

async function cargarSuscripcion() {
    try {
        const response = await fetch("../API/suscripcion.php", {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            mostrarError(data.mensaje || "No se pudo cargar la suscripción.");
            return;
        }

        const nombreCompleto = `${data.nombre} ${data.apellidos}`.trim();

        document.getElementById("sidebar-foto").src = data.foto_perfil || "../img-socios/socio1.png";
        document.getElementById("sidebar-nombre").textContent = nombreCompleto || "Usuario";
        document.getElementById("sidebar-plan").textContent = data.membresia || "Sin plan";

        document.getElementById("plan").textContent = data.membresia || "Sin suscripción activa";
        document.getElementById("precio").textContent = data.cuota || "No disponible";
        document.getElementById("periodo").textContent = data.periodo || "No disponible";
        document.getElementById("horario").textContent = data.horario || "No disponible";
        document.getElementById("fecha-inicio").textContent = data.fecha_inicio || "No disponible";
        document.getElementById("fecha-renovacion").textContent = data.fecha_renovacion || "No disponible";
        document.getElementById("estado").textContent = data.estado || "No disponible";
        document.getElementById("renovacion-automatica").textContent = data.renovacion_automatica || "No disponible";
        document.getElementById("descripcion").textContent = data.descripcion || "Sin descripción disponible.";

        const botonCancelar = document.getElementById("btn-cancelar-suscripcion");

        if (data.estado === "Cancelada" || data.estado === "Finalizada" || !data.id_suscripcion) {
            botonCancelar.disabled = true;
            botonCancelar.textContent = "Suscripción no cancelable";
        } else {
            botonCancelar.disabled = false;
            botonCancelar.textContent = "Cancelar suscripción";
        }

        mostrarMensaje("success", "Suscripción cargada correctamente.");

    } catch (error) {
        mostrarError("Error de conexión al cargar la suscripción.");
        console.error(error);
    }
}

async function cancelarSuscripcion() {
    const confirmar = confirm(
        "Si cancelas tu suscripción, no se realizará el siguiente cobro automático.\n\n¿Deseas continuar?"
    );

    if (!confirmar) {
        mostrarMensaje("warning", "Cancelación anulada.");
        return;
    }

    mostrarMensaje("loading", "Cancelando suscripción...");

    try {
        const formData = new FormData();
        formData.append("accion", "cancelar");

        const response = await fetch("../API/suscripcion.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            mostrarMensaje("error", data.mensaje || "No se pudo cancelar la suscripción.");
            return;
        }

        mostrarMensaje("success", data.mensaje);
        await cargarSuscripcion();

    } catch (error) {
        console.error(error);
        mostrarMensaje("error", "Ha ocurrido un error al cancelar la suscripción.");
    }
}

function mostrarError(mensaje) {
    document.getElementById("sidebar-nombre").textContent = "Error";
    document.getElementById("sidebar-plan").textContent = mensaje;

    document.getElementById("plan").textContent = mensaje;
    document.getElementById("precio").textContent = mensaje;
    document.getElementById("periodo").textContent = mensaje;
    document.getElementById("horario").textContent = mensaje;
    document.getElementById("fecha-inicio").textContent = mensaje;
    document.getElementById("fecha-renovacion").textContent = mensaje;
    document.getElementById("estado").textContent = mensaje;
    document.getElementById("renovacion-automatica").textContent = mensaje;
    document.getElementById("descripcion").textContent = mensaje;

    mostrarMensaje("error", mensaje);
}