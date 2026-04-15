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

async function cargarEditarUsuario() {
    try {
        const id = obtenerIdUsuarioDesdeURL();

        if (!id) {
            window.location.href = "admin-usuarios.html";
            return;
        }

        const url = new URL("../api/admin-editar-usuario.php", window.location.href);
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

        document.getElementById("resumen-foto-usuario").src = data.usuario.foto_perfil || "../img/perfil.jpg";
        document.getElementById("resumen-id-usuario").textContent = data.usuario.id_usuario;
        document.getElementById("resumen-alias-usuario").textContent = data.usuario.alias;
        document.getElementById("resumen-perfil-usuario").textContent = data.usuario.perfil;
        document.getElementById("resumen-estado-usuario").textContent = data.usuario.estado;

        document.getElementById("resumen-plan-actual").textContent = data.usuario.suscripcion.membresia || "Sin suscripción activa";
        document.getElementById("resumen-renovacion-actual").textContent = "Renovación: " + (data.usuario.suscripcion.fecha_renovacion || "No disponible");

        document.getElementById("idUsuario").value = data.usuario.id_usuario;
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
        }

        document.getElementById("renovacionAutomatica").value = data.usuario.suscripcion.renovacion_automatica || "Si";

    } catch (error) {
        console.error(error);
        window.location.href = "admin-usuarios.html";
    }
}

async function guardarCambiosUsuario() {
    const mensaje = document.getElementById("mensaje-form-editar-usuario");
    mensaje.textContent = "Guardando cambios...";

    try {
        const form = document.getElementById("form-editar-usuario");
        const formData = new FormData(form);

        const response = await fetch("../api/admin-editar-usuario.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            mensaje.textContent = data.mensaje || "No se pudieron guardar los cambios.";
            return;
        }

        mensaje.textContent = data.mensaje;
        await cargarEditarUsuario();

    } catch (error) {
        console.error(error);
        mensaje.textContent = "Ha ocurrido un error al guardar los cambios.";
    }
}