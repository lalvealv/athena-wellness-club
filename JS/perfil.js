document.addEventListener("DOMContentLoaded", () => {
    cargarPerfil();
});

async function cargarPerfil() {
    try {
        const response = await fetch("../api/perfil.php", {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            mostrarError(data.mensaje || "No se pudo cargar el perfil.");
            return;
        }

        document.getElementById("sidebar-foto").src = data.foto_perfil;
        document.getElementById("perfil-foto").src = data.foto_perfil;

        const nombreCompleto = `${data.nombre} ${data.apellidos}`.trim();

        document.getElementById("sidebar-nombre-completo").textContent = nombreCompleto || "Usuario";
        document.getElementById("sidebar-membresia").textContent = data.membresia || "Sin plan";

        document.getElementById("perfil-nombre").textContent = data.nombre || "No disponible";
        document.getElementById("perfil-apellidos").textContent = data.apellidos || "No disponible";
        document.getElementById("perfil-correo").textContent = data.correo || "No disponible";
        document.getElementById("perfil-telefono").textContent = data.telefono || "No disponible";
        document.getElementById("perfil-direccion").textContent = data.direccion || "No disponible";
        document.getElementById("perfil-plan").textContent = data.membresia || "Sin suscripción activa";

    } catch (error) {
        mostrarError("Error de conexión al cargar el perfil.");
        console.error(error);
    }
}

function mostrarError(mensaje) {
    document.getElementById("sidebar-nombre-completo").textContent = "Error";
    document.getElementById("sidebar-membresia").textContent = mensaje;

    document.getElementById("perfil-nombre").textContent = "Error";
    document.getElementById("perfil-apellidos").textContent = "Error";
    document.getElementById("perfil-correo").textContent = mensaje;
    document.getElementById("perfil-telefono").textContent = mensaje;
    document.getElementById("perfil-direccion").textContent = mensaje;
    document.getElementById("perfil-plan").textContent = mensaje;
}