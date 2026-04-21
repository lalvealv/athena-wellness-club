document.addEventListener("DOMContentLoaded", () => {
    cargarNotificaciones();
});

function mostrarMensajeNotificaciones(tipo, texto) {
    const mensaje = document.getElementById("mensaje-notificaciones");
    mensaje.className = `form-message ${tipo}`;
    mensaje.textContent = texto;
}

function actualizarBadgeAvisos(totalNoLeidas) {
    const navBadge = document.getElementById("nav-avisos-badge");
    const menuBadge = document.getElementById("menu-avisos-badge");

    [navBadge, menuBadge].forEach(badge => {
        if (!badge) {
            return;
        }

        if (totalNoLeidas > 0) {
            badge.hidden = false;
            badge.textContent = totalNoLeidas;
        } else {
            badge.hidden = true;
            badge.textContent = "0";
        }
    });
}

function pintarDetalleNotificacion(item) {
    document.getElementById("detalle-notificacion-tipo").textContent = item.tipo || "General";
    document.getElementById("detalle-notificacion-titulo").textContent = item.titulo || "Sin título";
    document.getElementById("detalle-notificacion-fecha").textContent = item.fecha_envio || "No disponible";
    document.getElementById("detalle-notificacion-mensaje").textContent = item.mensaje || "Sin contenido";
}

async function cargarNotificaciones() {
    try {
        const response = await fetch("../API/notificaciones.php", {
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

        actualizarBadgeAvisos(Number(data.no_leidas || 0));

        const contenedor = document.getElementById("lista-notificaciones");
        contenedor.innerHTML = "";

        if (!data.notificaciones || data.notificaciones.length === 0) {
            contenedor.innerHTML = `
                <div class="notification-item">
                    <strong>Sin notificaciones</strong>
                    <p>No tienes avisos registrados en este momento.</p>
                </div>
            `;
            document.getElementById("detalle-notificacion-tipo").textContent = "Sin avisos";
            document.getElementById("detalle-notificacion-titulo").textContent = "No hay notificaciones";
            document.getElementById("detalle-notificacion-fecha").textContent = "—";
            document.getElementById("detalle-notificacion-mensaje").textContent = "Cuando recibas avisos aparecerán aquí.";
            return;
        }

        data.notificaciones.forEach((item, index) => {
            const bloque = document.createElement("button");
            bloque.type = "button";
            bloque.className = `notification-item notification-item--clickable ${item.leida === 1 ? "notification-read" : "notification-unread"}`;

            const textoEstado = item.leida === 1 ? "Leída" : "No leída";

            bloque.innerHTML = `
                <strong>${item.titulo}</strong>
                <p>${item.resumen}</p>
                <p class="muted">${item.tipo} · ${item.fecha_envio} · ${textoEstado}</p>
            `;

            bloque.addEventListener("click", async () => {
                await abrirNotificacion(item.id_notificacion);
            });

            contenedor.appendChild(bloque);

            if (index === 0) {
                pintarDetalleNotificacion(item);
            }
        });

        if (data.no_leidas > 0) {
            mostrarMensajeNotificaciones("warning", `Tienes ${data.no_leidas} notificación(es) sin leer.`);
        } else {
            mostrarMensajeNotificaciones("success", "No tienes notificaciones pendientes de lectura.");
        }

    } catch (error) {
        console.error(error);
        window.location.href = "../publico/socios.html";
    }
}

async function abrirNotificacion(idNotificacion) {
    try {
        const response = await fetch(`../API/notificaciones.php?accion=detalle&id_notificacion=${idNotificacion}`, {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            mostrarMensajeNotificaciones("error", data.mensaje || "No se pudo abrir la notificación.");
            return;
        }

        pintarDetalleNotificacion(data.notificacion);
        await cargarNotificaciones();

    } catch (error) {
        console.error(error);
        mostrarMensajeNotificaciones("error", "Ha ocurrido un error al abrir la notificación.");
    }
}