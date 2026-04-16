document.addEventListener("DOMContentLoaded", () => {
    cargarUsuarios();

    const formulario = document.getElementById("form-buscar-usuarios");
    const inputBusqueda = document.getElementById("buscarUsuario");
    const botonLimpiar = document.getElementById("btn-limpiar-busqueda");

    formulario.addEventListener("submit", (event) => {
        event.preventDefault();
        cargarUsuarios(inputBusqueda.value.trim());
    });

    botonLimpiar.addEventListener("click", () => {
        setTimeout(() => {
            cargarUsuarios("");
        }, 0);
    });
});

async function cargarUsuarios(busqueda = "") {
    const mensaje = document.getElementById("mensaje-admin-usuarios");

    try {
        const url = new URL("../api/admin-usuarios.php", window.location.href);

        if (busqueda !== "") {
            url.searchParams.set("buscar", busqueda);
        }

        const response = await fetch(url, {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            mensaje.textContent = data.mensaje || "No se han podido cargar los usuarios.";
            return;
        }

        document.getElementById("admin-foto").src = data.admin.foto_perfil;
        document.getElementById("admin-nombre").textContent = data.admin.nombre_completo;
        document.getElementById("admin-perfil").textContent = data.admin.perfil;

        const tbody = document.getElementById("tabla-usuarios-body");
        tbody.innerHTML = "";

        if (!data.usuarios || data.usuarios.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8">No se encontraron usuarios.</td></tr>`;
            return;
        }

        data.usuarios.forEach(usuario => {
            let claseEstado = "";

            if (usuario.estado === "Activo") {
                claseEstado = "status-ok";
            } else if (usuario.estado === "Bloqueado" || usuario.estado === "Inactivo") {
                claseEstado = "status-cancel";
            } else {
                claseEstado = "status-wait";
            }

            const selectorEstado = `
                <select class="state-select" onchange="cambiarEstadoUsuario(${usuario.id_usuario}, this.value)">
                    <option value="Activo" ${usuario.estado === "Activo" ? "selected" : ""}>Activo</option>
                    <option value="Inactivo" ${usuario.estado === "Inactivo" ? "selected" : ""}>Inactivo</option>
                    <option value="Bloqueado" ${usuario.estado === "Bloqueado" ? "selected" : ""}>Bloqueado</option>
                </select>
            `;

            const fila = document.createElement("tr");
            fila.innerHTML = `
                <td>${usuario.id_usuario}</td>
                <td>${usuario.alias}</td>
                <td>${usuario.nombre_completo}</td>
                <td>${usuario.correo}</td>
                <td>${usuario.telefono}</td>
                <td>${usuario.perfil}</td>
                <td>
                    <span class="${claseEstado}">${usuario.estado}</span>
                    <div style="margin-top: 0.5rem;">${selectorEstado}</div>
                </td>
                <td><a href="admin-editar-usuario.html?id=${usuario.id_usuario}">Editar</a></td>
            `;
            tbody.appendChild(fila);
        });

    } catch (error) {
        console.error(error);
        mensaje.textContent = "Ha ocurrido un error al cargar los usuarios.";
    }
}

async function cambiarEstadoUsuario(idUsuario, nuevoEstado) {
    const mensaje = document.getElementById("mensaje-admin-usuarios");

    try {
        const formData = new FormData();
        formData.append("accion", "cambiar_estado");
        formData.append("id_usuario", idUsuario);
        formData.append("estado", nuevoEstado);

        const response = await fetch("../api/admin-usuarios.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        const data = await response.json();
        mensaje.textContent = data.mensaje;

        if (data.ok) {
            const inputBusqueda = document.getElementById("buscarUsuario");
            await cargarUsuarios(inputBusqueda.value.trim());
        }
    } catch (error) {
        console.error(error);
        mensaje.textContent = "Ha ocurrido un error al actualizar el estado.";
    }
}