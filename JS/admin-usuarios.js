// Espera a que el HTML esté completamente cargado
document.addEventListener("DOMContentLoaded", () => {
    // Carga los usuarios al abrir la página
    cargarUsuarios();

    // Obtiene los elementos del formulario de búsqueda
    const formulario = document.getElementById("form-buscar-usuarios");
    const inputBusqueda = document.getElementById("buscarUsuario");
    const botonLimpiar = document.getElementById("btn-limpiar-busqueda");

    // Busca usuarios al enviar el formulario
    formulario.addEventListener("submit", (event) => {
        event.preventDefault();
        cargarUsuarios(inputBusqueda.value.trim());
    });

    // Limpia la búsqueda y recarga todos los usuarios
    botonLimpiar.addEventListener("click", () => {
        setTimeout(() => {
            cargarUsuarios("");
        }, 0);
    });
});

// Carga usuarios desde el backend con búsqueda opcional
async function cargarUsuarios(busqueda = "") {
    const mensaje = document.getElementById("mensaje-admin-usuarios");

    try {
        // Construye la URL del endpoint
        const url = new URL("../API/admin-usuarios.php", window.location.href);

        // Añade el parámetro de búsqueda si existe
        if (busqueda !== "") {
            url.searchParams.set("buscar", busqueda);
        }

        // Solicita los datos al backend
        const response = await fetch(url, {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        // Si hay error, muestra mensaje
        if (!response.ok || !data.ok) {
            mensaje.textContent = data.mensaje || "No se han podido cargar los usuarios.";
            return;
        }

        // Carga datos del administrador
        document.getElementById("admin-foto").src = data.admin.foto_perfil;
        document.getElementById("admin-nombre").textContent = data.admin.nombre_completo;
        document.getElementById("admin-perfil").textContent = data.admin.perfil;

        // Limpia la tabla antes de pintarla
        const tbody = document.getElementById("tabla-usuarios-body");
        tbody.innerHTML = "";

        // Si no hay usuarios, muestra mensaje
        if (!data.usuarios || data.usuarios.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8">No se encontraron usuarios.</td></tr>`;
            return;
        }

        // Recorre los usuarios y crea una fila por cada uno
        data.usuarios.forEach(usuario => {
            let claseEstado = "";

            // Asigna clase visual según el estado
            if (usuario.estado === "Activo") {
                claseEstado = "status-ok";
            } else if (usuario.estado === "Bloqueado" || usuario.estado === "Inactivo") {
                claseEstado = "status-cancel";
            } else {
                claseEstado = "status-wait";
            }

            let selectorPerfil = "";

            // Protege el perfil del administrador original
            if (usuario.es_admin_original) {
                selectorPerfil = `
                    <div class="admin-lock-badge">Admin original</div>
                `;
            } else {
                const puedeAsignarAdmin = data.admin_logueado_es_original;

                // Select para cambiar entre CLIENTE y ADMIN
                selectorPerfil = `
                    <select class="select-perfil" onchange="cambiarPerfilUsuario(${usuario.id_usuario}, this.value)">
                        <option value="CLIENTE" ${usuario.perfil === "CLIENTE" ? "selected" : ""}>CLIENTE</option>
                        <option value="ADMIN" ${usuario.perfil === "ADMIN" ? "selected" : ""} ${!puedeAsignarAdmin ? "disabled" : ""}>ADMIN</option>
                    </select>
                `;
            }

            let selectorEstado = "";

            // Protege el estado del administrador original
            if (usuario.es_admin_original) {
                selectorEstado = `
                    <div class="admin-lock-badge">Protegido</div>
                `;
            } else {
                // Select para cambiar el estado del usuario
                selectorEstado = `
                    <select class="state-select" onchange="cambiarEstadoUsuario(${usuario.id_usuario}, this.value)">
                        <option value="Activo" ${usuario.estado === "Activo" ? "selected" : ""}>Activo</option>
                        <option value="Inactivo" ${usuario.estado === "Inactivo" ? "selected" : ""}>Inactivo</option>
                        <option value="Bloqueado" ${usuario.estado === "Bloqueado" ? "selected" : ""}>Bloqueado</option>
                    </select>
                `;
            }

            // Crea la fila de usuario
            const fila = document.createElement("tr");
            fila.innerHTML = `
                <td>${usuario.id_usuario}</td>
                <td>${usuario.alias}</td>
                <td>${usuario.nombre_completo}</td>
                <td>${usuario.correo}</td>
                <td>${usuario.telefono}</td>
                <td>
                    <div style="margin-bottom: 0.45rem;">${usuario.perfil}</div>
                    ${selectorPerfil}
                </td>
                <td>
                    <span class="${claseEstado}">${usuario.estado}</span>
                    <div style="margin-top: 0.5rem;">${selectorEstado}</div>
                </td>
                <td><a href="admin-editar-usuario.html?id=${usuario.id_usuario}">Editar</a></td>
            `;

            // Añade la fila a la tabla
            tbody.appendChild(fila);
        });

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola
        console.error(error);
        mensaje.textContent = "Ha ocurrido un error al cargar los usuarios.";
    }
}

// Cambia el estado de un usuario
async function cambiarEstadoUsuario(idUsuario, nuevoEstado) {
    const mensaje = document.getElementById("mensaje-admin-usuarios");

    try {
        // Prepara los datos para enviar al backend
        const formData = new FormData();
        formData.append("accion", "cambiar_estado");
        formData.append("id_usuario", idUsuario);
        formData.append("estado", nuevoEstado);

        // Envía la petición POST
        const response = await fetch("../API/admin-usuarios.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        const data = await response.json();

        // Muestra el mensaje recibido del backend
        mensaje.textContent = data.mensaje;

        // Recarga usuarios manteniendo la búsqueda actual
        const inputBusqueda = document.getElementById("buscarUsuario");
        await cargarUsuarios(inputBusqueda.value.trim());

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola
        console.error(error);
        mensaje.textContent = "Ha ocurrido un error al actualizar el estado.";
    }
}

// Cambia el perfil de un usuario
async function cambiarPerfilUsuario(idUsuario, nuevoPerfil) {
    const mensaje = document.getElementById("mensaje-admin-usuarios");

    // Pide confirmación antes de cambiar permisos
    if (!confirm("¿Seguro que quieres cambiar el perfil de este usuario?")) {
        const inputBusqueda = document.getElementById("buscarUsuario");
        await cargarUsuarios(inputBusqueda.value.trim());
        return;
    }

    try {
        // Prepara los datos para enviar al backend
        const formData = new FormData();
        formData.append("accion", "cambiar_perfil");
        formData.append("id_usuario", idUsuario);
        formData.append("perfil", nuevoPerfil);

        // Envía la petición POST
        const response = await fetch("../API/admin-usuarios.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        const data = await response.json();

        // Muestra el mensaje recibido del backend
        mensaje.textContent = data.mensaje;

        // Recarga usuarios manteniendo la búsqueda actual
        const inputBusqueda = document.getElementById("buscarUsuario");
        await cargarUsuarios(inputBusqueda.value.trim());

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola
        console.error(error);
        mensaje.textContent = "Ha ocurrido un error al actualizar el perfil.";
    }
}