// Espera a que el HTML esté completamente cargado
document.addEventListener("DOMContentLoaded", () => {
    // Carga los datos del perfil del usuario
    cargarPerfil();
});

// Función principal que obtiene y pinta el perfil
async function cargarPerfil() {
    try {
        // Petición al backend
        const response = await fetch("../api/perfil.php", {
            method: "GET",
            credentials: "same-origin"
        });

        // Convertir a JSON
        const data = await response.json();

        // Si hay error en la respuesta
        if (!response.ok || !data.ok) {
            mostrarError(data.mensaje || "No se pudo cargar el perfil.");
            return;
        }

        // Imagen de perfil (sidebar y sección principal)
        document.getElementById("sidebar-foto").src = data.foto_perfil;
        document.getElementById("perfil-foto").src = data.foto_perfil;

        // Nombre completo
        const nombreCompleto = `${data.nombre} ${data.apellidos}`.trim();

        // Sidebar
        document.getElementById("sidebar-nombre-completo").textContent = nombreCompleto || "Usuario";
        document.getElementById("sidebar-membresia").textContent = data.membresia || "Sin plan";

        // Datos del perfil
        document.getElementById("perfil-nombre").textContent = data.nombre || "No disponible";
        document.getElementById("perfil-apellidos").textContent = data.apellidos || "No disponible";
        document.getElementById("perfil-correo").textContent = data.correo || "No disponible";
        document.getElementById("perfil-telefono").textContent = data.telefono || "No disponible";
        document.getElementById("perfil-direccion").textContent = data.direccion || "No disponible";
        document.getElementById("perfil-plan").textContent = data.membresia || "Sin suscripción activa";

    } catch (error) {
        // Error de red o inesperado
        mostrarError("Error de conexión al cargar el perfil.");
        console.error(error);
    }
}

// Función para mostrar errores en la interfaz
function mostrarError(mensaje) {
    // Sidebar
    document.getElementById("sidebar-nombre-completo").textContent = "Error";
    document.getElementById("sidebar-membresia").textContent = mensaje;

    // Datos del perfil
    document.getElementById("perfil-nombre").textContent = "Error";
    document.getElementById("perfil-apellidos").textContent = "Error";
    document.getElementById("perfil-correo").textContent = mensaje;
    document.getElementById("perfil-telefono").textContent = mensaje;
    document.getElementById("perfil-direccion").textContent = mensaje;
    document.getElementById("perfil-plan").textContent = mensaje;
}