document.addEventListener("DOMContentLoaded", () => {
    cargarNotificacionesAdmin();

    const selectDestinatario = document.getElementById("destinatario");
    const filaUsuario = document.getElementById("fila-usuario-especifico");
    const formulario = document.getElementById("form-notificacion");
    const botonLimpiar = document.getElementById("btn-limpiar-notificacion");

    selectDestinatario.addEventListener("change", () => {
        if (selectDestinatario.value === "Usuario") {
            filaUsuario.style.display = "block";
        } else {
            filaUsuario.style.display = "none";
        }
    });

    formulario.addEventListener("submit", async (event) => {
        event.preventDefault();
        await enviarNotificacion();
    });

    botonLimpiar.addEventListener("click", () => {
        setTimeout(() => {
            filaUsuario.style.display = "none";
            document.getElementById("mensaje-form-notificacion").textContent = "";
        }, 0);
    });
});

async function cargarNotificacionesAdmin() {
    try {
        const response = await fetch("../API/admin-notificaciones.php", {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            window.location.href = "../publico/socios.html";
            return;
        }

        document.getElementById("admin-foto").src = data.admin.foto_perfil;
        document.getElementById("admin-nombre").textContent = data.admin.nombre_completo;
        document.getElementById("admin-perfil").textContent = data.admin.perfil;

        document.getElementById("resumen-avisos-hoy").textContent = data.resumen.avisos_hoy;
        document.getElementById("resumen-avisos-generales").textContent = data.resumen.avisos_generales;
        document.getElementById("resumen-recordatorios").textContent = data.resumen.recordatorios;
        document.getElementById("resumen-pendientes").textContent = data.resumen.pendientes;

        const lista = document.getElementById("lista-notificaciones-admin");
        lista.innerHTML = "";

        if (!data.notificaciones || data.notificaciones.length === 0) {
            lista.innerHTML = `
                <div class="notification-item">
                    <strong>Sin notificaciones</strong>
                    <p>No hay avisos enviados todavía.</p>
                </div>
            `;
            return;
        }

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
        console.error(error);
        window.location.href = "../publico/socios.html";
    }
}

async function enviarNotificacion() {
    const mensajeForm = document.getElementById("mensaje-form-notificacion");
    mensajeForm.textContent = "Enviando notificación...";

    try {
        const form = document.getElementById("form-notificacion");
        const formData = new FormData(form);

        const response = await fetch("../API/admin-notificaciones.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            mensajeForm.textContent = data.mensaje || "No se pudo enviar la notificación.";
            return;
        }

        mensajeForm.textContent = data.mensaje;
        form.reset();
        document.getElementById("fila-usuario-especifico").style.display = "none";
        await cargarNotificacionesAdmin();

    } catch (error) {
        console.error(error);
        mensajeForm.textContent = "Ha ocurrido un error al enviar la notificación.";
    }
}