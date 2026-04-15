document.addEventListener("DOMContentLoaded", () => {
    cargarPanelAdmin();
});

async function cargarPanelAdmin() {
    try {
        const response = await fetch("../api/admin-panel.php", {
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

        document.getElementById("panel-usuarios-activos").textContent = data.resumen.usuarios_activos;
        document.getElementById("panel-usuarios-bloqueados").textContent = data.resumen.usuarios_bloqueados;
        document.getElementById("panel-reservas-hoy").textContent = data.resumen.reservas_hoy;
        document.getElementById("panel-nuevas-altas").textContent = data.resumen.nuevas_altas;

        const tbody = document.getElementById("tabla-ultimos-usuarios");
        tbody.innerHTML = "";

        if (!data.ultimos_usuarios || data.ultimos_usuarios.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6">No hay usuarios registrados.</td></tr>`;
            return;
        }

        data.ultimos_usuarios.forEach(usuario => {
            let claseEstado = "";

            if (usuario.estado === "Activo") {
                claseEstado = "status-ok";
            } else if (usuario.estado === "Bloqueado" || usuario.estado === "Inactivo") {
                claseEstado = "status-cancel";
            }

            const fila = document.createElement("tr");
            fila.innerHTML = `
                <td>${usuario.alias}</td>
                <td>${usuario.nombre_completo}</td>
                <td>${usuario.correo}</td>
                <td>${usuario.fecha_registro}</td>
                <td class="${claseEstado}">${usuario.estado}</td>
                <td><a href="admin-editar-usuario.html?id=${usuario.id_usuario}">Ver ficha</a></td>
            `;
            tbody.appendChild(fila);
        });

    } catch (error) {
        console.error(error);
        window.location.href = "../publico/socios.html";
    }
}