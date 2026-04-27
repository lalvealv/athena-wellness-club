// Espera a que el HTML esté completamente cargado
document.addEventListener("DOMContentLoaded", () => {
    // Carga el número de notificaciones no leídas
    cargarBadgeNotificaciones();
});

// Consulta al backend cuántas notificaciones tiene sin leer el usuario
async function cargarBadgeNotificaciones() {
    try {
        // Solicita el conteo de notificaciones no leídas
        const response = await fetch("../API/notificaciones.php?accion=conteo", {
            method: "GET",
            credentials: "same-origin"
        });

        // Convierte la respuesta en JSON
        const data = await response.json();

        // Si hay error, no muestra nada
        if (!response.ok || !data.ok) {
            return;
        }

        // Actualiza los badges con el total recibido
        actualizarBadge(data.no_leidas || 0);

    } catch (error) {
        // Muestra el error en consola sin romper la página
        console.error("Error cargando badge de notificaciones:", error);
    }
}

// Actualiza visualmente los badges de notificaciones
function actualizarBadge(total) {
    const navBadge = document.getElementById("nav-avisos-badge");
    const menuBadge = document.getElementById("menu-avisos-badge");

    // Recorre los dos posibles badges: navegación superior y menú lateral
    [navBadge, menuBadge].forEach(badge => {
        // Si el badge no existe en esa página, no hace nada
        if (!badge) return;

        // Si hay notificaciones pendientes, muestra el número
        if (total > 0) {
            badge.hidden = false;
            badge.textContent = total;
        } else {
            // Si no hay notificaciones, oculta el badge
            badge.hidden = true;
            badge.textContent = "0";
        }
    });
}