document.addEventListener("DOMContentLoaded", () => {
    cargarEditarUsuario();

    const form = document.getElementById("form-editar-usuario");
    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        await guardarCambiosUsuario();
    });
});

function obtenerIdUsuarioDesdeURL() {
    const params = new URLSearchParams(window.location.search);
    return params.get("id");
}

function mostrarMensaje(tipo, texto) {
    const mensaje = document.getElementById("mensaje-form-editar-usuario");
    mensaje.className = `form-message ${tipo}`;
    mensaje.textContent = texto;
}

async function cargarEditarUsuario() {
    try {
        const id = obtenerIdUsuarioDesdeURL();

        if (!id) {
            window.location.href = "admin-usuarios.html";
            return;
        }

        const url = new URL("../API/admin-editar-usuario.php", window.location.href);
        url.searchParams.set("id", id);

        const response = await fetch(url, {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            window.location.href = "admin-usuarios.html";
            return;
        }

        document.getElementById("admin-foto").src = data.admin.foto_perfil;
        document.getElementById("admin-nombre").textContent = data.admin.nombre_completo;
        document.getElementById("admin-perfil").textContent = data.admin.perfil;

        document.getElementById("resumen-foto-usuario").src = data.usuario.foto_perfil || "../img/athena_logo.png";
        document.getElementById("resumen-id-usuario").textContent = data.usuario.id_usuario;
        document.getElementById("resumen-alias-usuario").textContent = data.usuario.alias;
        document.getElementById("resumen-perfil-usuario").textContent = data.usuario.perfil;
        document.getElementById("resumen-estado-usuario").textContent = data.usuario.estado;

        document.getElementById("resumen-plan-actual").textContent =
            data.usuario.suscripcion.membresia || "Sin suscripción activa";

        document.getElementById("resumen-renovacion-actual").textContent =
            "Renovación: " + (data.usuario.suscripcion.fecha_renovacion || "No disponible");

        document.getElementById("idUsuario").value = data.usuario.id_usuario;
        document.getElementById("esAdminOriginal").value = data.usuario.es_admin_original ? "1" : "0";

        document.getElementById("alias").value = data.usuario.alias || "";
        document.getElementById("dni").value = data.usuario.dni || "";
        document.getElementById("nombre").value = data.usuario.nombre || "";
        document.getElementById("apellidos").value = data.usuario.apellidos || "";
        document.getElementById("correo").value = data.usuario.correo || "";
        document.getElementById("telefono").value = data.usuario.telefono || "";
        document.getElementById("fechaNacimiento").value = data.usuario.fecha_nacimiento || "";
        document.getElementById("sexo").value = data.usuario.sexo || "";
        document.getElementById("estado").value = data.usuario.estado || "";
        document.getElementById("perfil").value = data.usuario.perfil || "";
        document.getElementById("fotoPerfil").value = data.usuario.foto_perfil || "";

        document.getElementById("calle").value = data.usuario.direccion.calle || "";
        document.getElementById("portal").value = data.usuario.direccion.portal || "";
        document.getElementById("piso").value = data.usuario.direccion.piso || "";
        document.getElementById("cp").value = data.usuario.direccion.cp || "";
        document.getElementById("ciudad").value = data.usuario.direccion.ciudad || "";
        document.getElementById("pais").value = data.usuario.direccion.pais || "";

        if (data.usuario.suscripcion.membresia) {
            document.getElementById("membresia").value = data.usuario.suscripcion.membresia;
        } else {
            document.getElementById("membresia").value = "";
        }

        document.getElementById("estadoSuscripcion").value =
            data.usuario.suscripcion.estado || "Activa";

        document.getElementById("renovacionAutomatica").value =
            data.usuario.suscripcion.renovacion_automatica || "Si";

        // Guardamos valores originales para comparar al guardar
        document.getElementById("perfil").dataset.original = data.usuario.perfil || "";
        document.getElementById("estado").dataset.original = data.usuario.estado || "";

        // PROTEGER CAMPOS SI ES EL ADMIN ORIGINAL
        if (data.usuario.es_admin_original) {
            document.getElementById("perfil").disabled = true;
            document.getElementById("estado").disabled = true;
        }

        // SOLO EL ADMIN ORIGINAL PUEDE ASIGNAR PERFIL ADMIN
        if (!data.admin_logueado_es_original && document.getElementById("perfil").value !== "ADMIN") {
            const opcionAdmin = document.querySelector('#perfil option[value="ADMIN"]');
            if (opcionAdmin) {
                opcionAdmin.disabled = true;
            }
        }

    } catch (error) {
        console.error(error);
        window.location.href = "admin-usuarios.html";
    }
}

async function guardarCambiosUsuario() {
    const perfilSelect = document.getElementById("perfil");
    const estadoSelect = document.getElementById("estado");

    const perfilOriginal = perfilSelect.dataset.original || "";
    const estadoOriginal = estadoSelect.dataset.original || "";

    const perfilActual = perfilSelect.value;
    const estadoActual = estadoSelect.value;

    const estadoSuscripcionSelect = document.getElementById("estadoSuscripcion");
    const renovacionAutomaticaSelect = document.getElementById("renovacionAutomatica");
    const estadoSuscripcionActual = estadoSuscripcionSelect.value;

    let cambiosImportantes = [];

    if (perfilOriginal !== perfilActual) {
        cambiosImportantes.push(`Perfil: ${perfilOriginal} → ${perfilActual}`);
    }

    if (estadoOriginal !== estadoActual) {
        cambiosImportantes.push(`Estado: ${estadoOriginal} → ${estadoActual}`);
    }

    if (cambiosImportantes.length > 0) {
        const confirmar = confirm(
            "Vas a guardar cambios importantes:\n\n" +
            cambiosImportantes.join("\n") +
            "\n\n¿Deseas continuar?"
        );

        if (!confirmar) {
            mostrarMensaje("warning", "Guardado cancelado.");
            return;
        }
    }

    if (estadoSuscripcionActual === "Cancelada") {
        const confirmarCancelacion = confirm(
            "Esta suscripción quedará cancelada y no se renovará automáticamente.\n\n¿Deseas continuar?"
        );

        if (!confirmarCancelacion) {
            mostrarMensaje("warning", "Cancelación de suscripción anulada.");
            return;
        }

        renovacionAutomaticaSelect.value = "No";
    }

    mostrarMensaje("loading", "Guardando cambios...");

    try {
        const form = document.getElementById("form-editar-usuario");

        // Si estaban deshabilitados, los reactivamos justo antes de enviar
        perfilSelect.disabled = false;
        estadoSelect.disabled = false;

        const formData = new FormData(form);

        const response = await fetch("../API/admin-editar-usuario.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            mostrarMensaje("error", data.mensaje || "No se pudieron guardar los cambios.");
            await cargarEditarUsuario();
            return;
            const estadoSuscripcion = document.getElementById("estadoSuscripcion");
            const renovacionAutomatica = document.getElementById("renovacionAutomatica");

            estadoSuscripcion.addEventListener("change", () => {
                if (estadoSuscripcion.value === "Cancelada") {
                    renovacionAutomatica.value = "No";
                }
            });
        }

        mostrarMensaje("success", data.mensaje);
        await cargarEditarUsuario();

    } catch (error) {
        console.error(error);
        mostrarMensaje("error", "Ha ocurrido un error al guardar los cambios.");
    }
}