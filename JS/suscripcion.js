document.addEventListener("DOMContentLoaded", () => {
    cargarSuscripcion();
});

async function cargarSuscripcion() {
    try {
        const response = await fetch("../api/suscripcion.php", {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            mostrarError(data.mensaje || "No se pudo cargar la suscripción.");
            return;
        }

        const nombreCompleto = `${data.nombre} ${data.apellidos}`.trim();

        document.getElementById("sidebar-foto").src = data.foto_perfil;
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

    } catch (error) {
        mostrarError("Error de conexión al cargar la suscripción.");
        console.error(error);
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
}