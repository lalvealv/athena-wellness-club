// Espera a que el HTML esté completamente cargado
document.addEventListener("DOMContentLoaded", () => {
    // Carga los datos del usuario que se va a editar
    cargarEditarUsuario();

    // Obtiene el formulario de edición
    const form = document.getElementById("form-editar-usuario");

    // Controla el envío del formulario
    form.addEventListener("submit", async (event) => {
        // Evita que la página se recargue
        event.preventDefault();

        // Guarda los cambios del usuario
        await guardarCambiosUsuario();
    });
});

// Obtiene el ID del usuario desde la URL
function obtenerIdUsuarioDesdeURL() {
    const params = new URLSearchParams(window.location.search);
    return params.get("id");
}

// Muestra mensajes de estado en el formulario
function mostrarMensaje(tipo, texto) {
    const mensaje = document.getElementById("mensaje-form-editar-usuario");
    mensaje.className = `form-message ${tipo}`;
    mensaje.textContent = texto;
}

// Carga los datos del usuario seleccionado para editar
async function cargarEditarUsuario() {
    try {
        // Obtiene el ID desde la URL
        const id = obtenerIdUsuarioDesdeURL();

        // Si no hay ID, vuelve al listado de usuarios
        if (!id) {
            window.location.href = "admin-usuarios.html";
            return;
        }

        // Construye la URL del endpoint con el ID del usuario
        const url = new URL("../API/admin-editar-usuario.php", window.location.href);
        url.searchParams.set("id", id);

        // Pide los datos al backend
        const response = await fetch(url, {
            method: "GET",
            credentials: "same-origin"
        });

        // Convierte la respuesta en JSON
        const data = await response.json();

        // Si hay error, vuelve al listado
        if (!response.ok || !data.ok) {
            window.location.href = "admin-usuarios.html";
            return;
        }

        // Carga los datos del administrador en el sidebar
        document.getElementById("admin-foto").src = data.admin.foto_perfil;
        document.getElementById("admin-nombre").textContent = data.admin.nombre_completo;
        document.getElementById("admin-perfil").textContent = data.admin.perfil;

        // Carga el resumen superior del usuario
        document.getElementById("resumen-foto-usuario").src = data.usuario.foto_perfil || "../img/athena_logo.png";
        document.getElementById("resumen-id-usuario").textContent = data.usuario.id_usuario;
        document.getElementById("resumen-alias-usuario").textContent = data.usuario.alias;
        document.getElementById("resumen-perfil-usuario").textContent = data.usuario.perfil;
        document.getElementById("resumen-estado-usuario").textContent = data.usuario.estado;

        // Muestra el plan actual del usuario
        document.getElementById("resumen-plan-actual").textContent =
            data.usuario.suscripcion.membresia || "Sin suscripción activa";

        // Muestra la fecha de renovación actual
        document.getElementById("resumen-renovacion-actual").textContent =
            "Renovación: " + (data.usuario.suscripcion.fecha_renovacion || "No disponible");

        // Guarda IDs y datos ocultos necesarios para el envío
        document.getElementById("idUsuario").value = data.usuario.id_usuario;
        document.getElementById("esAdminOriginal").value = data.usuario.es_admin_original ? "1" : "0";

        // Rellena los campos personales
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

        // Rellena los campos de dirección
        document.getElementById("calle").value = data.usuario.direccion.calle || "";
        document.getElementById("portal").value = data.usuario.direccion.portal || "";
        document.getElementById("piso").value = data.usuario.direccion.piso || "";
        document.getElementById("cp").value = data.usuario.direccion.cp || "";
        document.getElementById("ciudad").value = data.usuario.direccion.ciudad || "";
        document.getElementById("pais").value = data.usuario.direccion.pais || "";

        // Rellena la membresía actual si existe
        if (data.usuario.suscripcion.membresia) {
            document.getElementById("membresia").value = data.usuario.suscripcion.membresia;
        } else {
            document.getElementById("membresia").value = "";
        }

        // Rellena el estado de la suscripción
        document.getElementById("estadoSuscripcion").value =
            data.usuario.suscripcion.estado || "Activa";

        // Rellena la renovación automática
        document.getElementById("renovacionAutomatica").value =
            data.usuario.suscripcion.renovacion_automatica || "Si";

        // Guarda valores originales para detectar cambios importantes
        document.getElementById("perfil").dataset.original = data.usuario.perfil || "";
        document.getElementById("estado").dataset.original = data.usuario.estado || "";

        // Si el usuario es el administrador original, se bloquean perfil y estado
        if (data.usuario.es_admin_original) {
            document.getElementById("perfil").disabled = true;
            document.getElementById("estado").disabled = true;
        }

        // Si el admin logueado no es el original, no puede asignar perfil ADMIN a clientes
        if (!data.admin_logueado_es_original && document.getElementById("perfil").value !== "ADMIN") {
            const opcionAdmin = document.querySelector('#perfil option[value="ADMIN"]');

            if (opcionAdmin) {
                opcionAdmin.disabled = true;
            }
        }

    } catch (error) {
        // Si ocurre un error, lo muestra en consola y vuelve al listado
        console.error(error);
        window.location.href = "admin-usuarios.html";
    }
}

// Guarda los cambios realizados en el formulario
async function guardarCambiosUsuario() {
    // Obtiene los select de perfil y estado
    const perfilSelect = document.getElementById("perfil");
    const estadoSelect = document.getElementById("estado");

    // Recupera valores originales
    const perfilOriginal = perfilSelect.dataset.original || "";
    const estadoOriginal = estadoSelect.dataset.original || "";

    // Obtiene valores actuales
    const perfilActual = perfilSelect.value;
    const estadoActual = estadoSelect.value;

    // Obtiene campos de suscripción
    const estadoSuscripcionSelect = document.getElementById("estadoSuscripcion");
    const renovacionAutomaticaSelect = document.getElementById("renovacionAutomatica");
    const estadoSuscripcionActual = estadoSuscripcionSelect.value;

    // Guarda una lista de cambios importantes para confirmar
    let cambiosImportantes = [];

    // Detecta cambio de perfil
    if (perfilOriginal !== perfilActual) {
        cambiosImportantes.push(`Perfil: ${perfilOriginal} → ${perfilActual}`);
    }

    // Detecta cambio de estado
    if (estadoOriginal !== estadoActual) {
        cambiosImportantes.push(`Estado: ${estadoOriginal} → ${estadoActual}`);
    }

    // Si hay cambios importantes, pide confirmación
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

    // Si se cancela la suscripción, pide confirmación y desactiva renovación
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

    // Muestra mensaje de carga
    mostrarMensaje("loading", "Guardando cambios...");

    try {
        // Obtiene el formulario
        const form = document.getElementById("form-editar-usuario");

        // Reactiva campos deshabilitados para que se incluyan en FormData
        perfilSelect.disabled = false;
        estadoSelect.disabled = false;

        // Convierte el formulario en FormData
        const formData = new FormData(form);

        // Envía los datos al backend
        const response = await fetch("../API/admin-editar-usuario.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        // Convierte la respuesta en JSON
        const data = await response.json();

        // Si hay error, muestra mensaje y recarga datos actuales
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

        // Muestra mensaje de éxito
        mostrarMensaje("success", data.mensaje);

        // Recarga los datos actualizados
        await cargarEditarUsuario();

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola
        console.error(error);
        mostrarMensaje("error", "Ha ocurrido un error al guardar los cambios.");
    }
}