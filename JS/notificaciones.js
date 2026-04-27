// Espera a que el HTML esté completamente cargado
document.addEventListener("DOMContentLoaded", () => {
    // Carga las notificaciones del usuario
    cargarNotificaciones();
});

// Muestra mensajes de estado en la pantalla de notificaciones
function mostrarMensajeNotificaciones(tipo, texto) {
    const mensaje = document.getElementById("mensaje-notificaciones");
    mensaje.className = `form-message ${tipo}`;
    mensaje.textContent = texto;
}

// Actualiza el contador visual de avisos no leídos
function actualizarBadgeAvisos(totalNoLeidas) {
    const navBadge = document.getElementById("nav-avisos-badge");
    const menuBadge = document.getElementById("menu-avisos-badge");

    // Recorre el badge superior y el badge del menú lateral
    [navBadge, menuBadge].forEach(badge => {
        if (!badge) {
            return;
        }

        // Si hay notificaciones no leídas, muestra el número
        if (totalNoLeidas > 0) {
            badge.hidden = false;
            badge.textContent = totalNoLeidas;
        } else {
            // Si no hay pendientes, oculta el badge
            badge.hidden = true;
            badge.textContent = "0";
        }
    });
}

// Pinta el detalle de una notificación seleccionada
function pintarDetalleNotificacion(item) {
    document.getElementById("detalle-notificacion-tipo").textContent = item.tipo || "General";
    document.getElementById("detalle-notificacion-titulo").textContent = item.titulo || "Sin título";
    document.getElementById("detalle-notificacion-fecha").textContent = item.fecha_envio || "No disponible";
    document.getElementById("detalle-notificacion-mensaje").textContent = item.mensaje || "Sin contenido";
}

// Carga todas las notificaciones del usuario desde el backend
async function cargarNotificaciones() {
    try {
        // Solicita las notificaciones al archivo PHP
        const response = await fetch("../API/notificaciones.php", {
            method: "GET",
            credentials: "same-origin"
        });

        // Convierte la respuesta a JSON
        const data = await response.json();

        // Si no hay sesión válida, redirige al login
        if (!response.ok || !data.ok) {
            window.location.href = "../publico/socios.html";
            return;
        }

        // Carga datos del usuario en el sidebar
        document.getElementById("sidebar-foto").src = data.sidebar.foto_perfil;
        document.getElementById("sidebar-nombre").textContent = data.sidebar.nombre_completo;
        document.getElementById("sidebar-plan").textContent = data.sidebar.membresia;

        // Actualiza el badge de avisos pendientes
        actualizarBadgeAvisos(Number(data.no_leidas || 0));

        // Prepara el contenedor de notificaciones
        const contenedor = document.getElementById("lista-notificaciones");
        contenedor.innerHTML = "";

        // Si no hay notificaciones, muestra mensaje vacío
        if (!data.notificaciones || data.notificaciones.length === 0) {
            contenedor.innerHTML = `
                <div class="notification-item">
                    <strong>Sin notificaciones</strong>
                    <p>No tienes avisos registrados en este momento.</p>
                </div>
            `;

            // También limpia el panel de detalle
            document.getElementById("detalle-notificacion-tipo").textContent = "Sin avisos";
            document.getElementById("detalle-notificacion-titulo").textContent = "No hay notificaciones";
            document.getElementById("detalle-notificacion-fecha").textContent = "—";
            document.getElementById("detalle-notificacion-mensaje").textContent = "Cuando recibas avisos aparecerán aquí.";
            return;
        }

        // Recorre todas las notificaciones y crea un botón por cada una
        data.notificaciones.forEach((item, index) => {
            const bloque = document.createElement("button");
            bloque.type = "button";

            // Aplica clase diferente si está leída o no leída
            bloque.className = `notification-item notification-item--clickable ${item.leida === 1 ? "notification-read" : "notification-unread"}`;

            // Texto visual del estado
            const textoEstado = item.leida === 1 ? "Leída" : "No leída";

            // Contenido del bloque de notificación
            bloque.innerHTML = `
                <strong>${item.titulo}</strong>
                <p>${item.resumen}</p>
                <p class="muted">${item.tipo} · ${item.fecha_envio} · ${textoEstado}</p>
            `;

            // Al hacer clic, abre el detalle de la notificación
            bloque.addEventListener("click", async () => {
                await abrirNotificacion(item.id_notificacion);
            });

            // Añade la notificación al contenedor
            contenedor.appendChild(bloque);

            // Muestra automáticamente el detalle de la primera notificación
            if (index === 0) {
                pintarDetalleNotificacion(item);
            }
        });

        // Muestra mensaje según haya avisos pendientes o no
        if (data.no_leidas > 0) {
            mostrarMensajeNotificaciones("warning", `Tienes ${data.no_leidas} notificación(es) sin leer.`);
        } else {
            mostrarMensajeNotificaciones("success", "No tienes notificaciones pendientes de lectura.");
        }

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola y redirige al login
        console.error(error);
        window.location.href = "../publico/socios.html";
    }
}

// Abre una notificación concreta y la marca como leída
async function abrirNotificacion(idNotificacion) {
    try {
        // Solicita el detalle de la notificación seleccionada
        const response = await fetch(`../API/notificaciones.php?accion=detalle&id_notificacion=${idNotificacion}`, {
            method: "GET",
            credentials: "same-origin"
        });

        // Convierte la respuesta a JSON
        const data = await response.json();

        // Si hay error, muestra mensaje
        if (!response.ok || !data.ok) {
            mostrarMensajeNotificaciones("error", data.mensaje || "No se pudo abrir la notificación.");
            return;
        }

        // Pinta el detalle de la notificación seleccionada
        pintarDetalleNotificacion(data.notificacion);

        // Recarga la lista para actualizar el estado de leído/no leído
        await cargarNotificaciones();

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola
        console.error(error);
        mostrarMensajeNotificaciones("error", "Ha ocurrido un error al abrir la notificación.");
    }
}