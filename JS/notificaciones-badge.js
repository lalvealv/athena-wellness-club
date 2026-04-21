document.addEventListener("DOMContentLoaded", () => {
    cargarBadgeNotificaciones();
});

async function cargarBadgeNotificaciones() {
    try {
        const response = await fetch("../API/notificaciones.php?accion=conteo", {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            return;
        }

        actualizarBadge(data.no_leidas || 0);

    } catch (error) {
        console.error("Error cargando badge de notificaciones:", error);
    }
}

function actualizarBadge(total) {
    const navBadge = document.getElementById("nav-avisos-badge");
    const menuBadge = document.getElementById("menu-avisos-badge");

    [navBadge, menuBadge].forEach(badge => {
        if (!badge) return;

        if (total > 0) {
            badge.hidden = false;
            badge.textContent = total;
        } else {
            badge.hidden = true;
            badge.textContent = "0";
        }
    });
}