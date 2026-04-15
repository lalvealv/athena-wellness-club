document.addEventListener("DOMContentLoaded", () => {
    cargarNotificaciones();
});

async function cargarNotificaciones() {
    try {
        const response = await fetch("../api/notificaciones.php", {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            window.location.href = "../publico/socios.html";
            return;
        }

        document.getElementById("sidebar-foto").src = data.sidebar.foto_perfil;
        document.getElementById("sidebar-nombre").textContent = data.sidebar.nombre_completo;
        document.getElementById("sidebar-plan").textContent = data.sidebar.membresia;

        const contenedor = document.getElementById("lista-notificaciones");
        contenedor.innerHTML = "";

        if (!data.notificaciones || data.notificaciones.length === 0) {
            contenedor.innerHTML = `
                <div class="notification-item">
                    <strong>Sin notificaciones</strong>
                    <p>No tienes avisos registrados en este momento.</p>
                </div>
            `;
            return;
        }

        data.notificaciones.forEach(item => {
            const bloque = document.createElement("div");
            bloque.className = "notification-item";

            const textoEstado = item.leida === 1 ? "Leída" : "No leída";

            bloque.innerHTML = `
                <strong>${item.titulo}</strong>
                <p>${item.mensaje}</p>
                <p class="muted">${item.tipo} · ${item.fecha_envio} · ${textoEstado}</p>
            `;

            contenedor.appendChild(bloque);
        });

    } catch (error) {
        console.error(error);
        window.location.href = "../publico/socios.html";
    }
}