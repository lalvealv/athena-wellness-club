// Espera a que el HTML esté completamente cargado
document.addEventListener("DOMContentLoaded", () => {
    // Carga los datos del panel de notificaciones
    cargarNotificacionesAdmin();

    // Obtiene elementos principales del formulario
    const selectDestinatario = document.getElementById("destinatario");
    const filaUsuario = document.getElementById("fila-usuario-especifico");
    const formulario = document.getElementById("form-notificacion");
    const botonLimpiar = document.getElementById("btn-limpiar-notificacion");

    // Muestra el campo de usuario específico solo cuando se selecciona "Usuario"
    selectDestinatario.addEventListener("change", () => {
        if (selectDestinatario.value === "Usuario") {
            filaUsuario.style.display = "block";
        } else {
            filaUsuario.style.display = "none";
        }
    });

    // Controla el envío del formulario
    formulario.addEventListener("submit", async (event) => {
        event.preventDefault();
        await enviarNotificacion();
    });

    // Limpia el formulario y oculta el campo de usuario específico
    botonLimpiar.addEventListener("click", () => {
        setTimeout(() => {
            filaUsuario.style.display = "none";
            document.getElementById("mensaje-form-notificacion").textContent = "";
        }, 0);
    });
});

// Carga administrador, resumen y últimas notificaciones enviadas
async function cargarNotificacionesAdmin() {
    try {
        const response = await fetch("../API/admin-notificaciones.php", {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        // Si no hay permisos, redirige al login
        if (!response.ok || !data.ok) {
            window.location.href = "../publico/socios.html";
            return;
        }

        // Muestra los datos del administrador
        document.getElementById("admin-foto").src = data.admin.foto_perfil;
        document.getElementById("admin-nombre").textContent = data.admin.nombre_completo;
        document.getElementById("admin-perfil").textContent = data.admin.perfil;

        // Muestra el resumen de comunicación
        document.getElementById("resumen-avisos-hoy").textContent = data.resumen.avisos_hoy;
        document.getElementById("resumen-avisos-generales").textContent = data.resumen.avisos_generales;
        document.getElementById("resumen-recordatorios").textContent = data.resumen.recordatorios;
        document.getElementById("resumen-pendientes").textContent = data.resumen.pendientes;

        // Limpia el listado antes de pintarlo
        const lista = document.getElementById("lista-notificaciones-admin");
        lista.innerHTML = "";

        // Si no hay notificaciones, muestra mensaje vacío
        if (!data.notificaciones || data.notificaciones.length === 0) {
            lista.innerHTML = `
                <div class="notification-item">
                    <strong>Sin notificaciones</strong>
                    <p>No hay avisos enviados todavía.</p>
                </div>
            `;
            return;
        }

        // Pinta cada notificación enviada
        data.notificaciones.forEach(item => {
            const bloque = document.createElement("div");
            bloque.className = "notification-item";

            bloque.innerHTML = `
                <strong>${item.titulo}</strong>
                <p>${item.mensaje}</p>
                <p class="muted">
                    Tipo: ${item.tipo} · Destinatario: ${item.destinatario} · 
                    ${item.fecha_envio} · ${item.total_destinatarios} usuario(s)
                </p>
            `;

            lista.appendChild(bloque);
        });

    } catch (error) {
        // Si ocurre un error, lo muestra en consola y redirige al login
        console.error(error);
        window.location.href = "../publico/socios.html";
    }
}

// Envía una nueva notificación desde el formulario
async function enviarNotificacion() {
    const mensajeForm = document.getElementById("mensaje-form-notificacion");
    mensajeForm.textContent = "Enviando notificación...";

    try {
        // Obtiene el formulario y sus datos
        const form = document.getElementById("form-notificacion");
        const formData = new FormData(form);

        // Envía los datos al backend
        const response = await fetch("../API/admin-notificaciones.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        const data = await response.json();

        // Si hay error, muestra el mensaje recibido
        if (!response.ok || !data.ok) {
            mensajeForm.textContent = data.mensaje || "No se pudo enviar la notificación.";
            return;
        }

        // Muestra confirmación, limpia formulario y recarga el listado
        mensajeForm.textContent = data.mensaje;
        form.reset();
        document.getElementById("fila-usuario-especifico").style.display = "none";

        await cargarNotificacionesAdmin();

    } catch (error) {
        // Muestra error en consola y avisa al usuario
        console.error(error);
        mensajeForm.textContent = "Ha ocurrido un error al enviar la notificación.";
    }
}