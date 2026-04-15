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
            window.location.href = "../publico/socios.html";
            return;
        }

        document.getElementById("admin-foto").src = data.admin.foto_perfil;
        document.getElementById("admin-nombre").textContent = data.admin.nombre_completo;
        document.getElementById("admin-perfil").textContent = data.admin.perfil;

        const tbody = document.getElementById("tabla-usuarios");
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
            }

            const fila = document.createElement("tr");
            fila.innerHTML = `
                <td>${usuario.id_usuario}</td>
                <td>${usuario.alias}</td>
                <td>${usuario.nombre_completo}</td>
                <td>${usuario.correo}</td>
                <td>${usuario.telefono}</td>
                <td>${usuario.perfil}</td>
                <td class="${claseEstado}">${usuario.estado}</td>
                <td><a href="admin-editar-usuario.html?id=${usuario.id_usuario}">Editar</a></td>
            `;
            tbody.appendChild(fila);
        });

    } catch (error) {
        console.error(error);
        window.location.href = "../publico/socios.html";
    }
}