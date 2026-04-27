// Espera a que el HTML esté completamente cargado
document.addEventListener("DOMContentLoaded", () => {
    // Carga los datos del panel de administración
    cargarPanelAdmin();
});

// Carga el resumen general del panel de administración
async function cargarPanelAdmin() {
    try {
        // Solicita los datos al backend
        const response = await fetch("../api/admin-panel.php", {
            method: "GET",
            credentials: "same-origin"
        });

        // Convierte la respuesta en JSON
        const data = await response.json();

        // Si no hay permisos o falla la petición, redirige al login
        if (!response.ok || !data.ok) {
            window.location.href = "../publico/socios.html";
            return;
        }

        // Carga los datos del administrador
        document.getElementById("admin-foto").src = data.admin.foto_perfil;
        document.getElementById("admin-nombre").textContent = data.admin.nombre_completo;
        document.getElementById("admin-perfil").textContent = data.admin.perfil;

        // Carga las tarjetas de resumen
        document.getElementById("panel-usuarios-activos").textContent = data.resumen.usuarios_activos;
        document.getElementById("panel-usuarios-bloqueados").textContent = data.resumen.usuarios_bloqueados;
        document.getElementById("panel-reservas-hoy").textContent = data.resumen.reservas_hoy;
        document.getElementById("panel-nuevas-altas").textContent = data.resumen.nuevas_altas;

        // Prepara la tabla de últimos usuarios registrados
        const tbody = document.getElementById("tabla-ultimos-usuarios");
        tbody.innerHTML = "";

        // Si no hay usuarios, muestra un mensaje en la tabla
        if (!data.ultimos_usuarios || data.ultimos_usuarios.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6">No hay usuarios registrados.</td></tr>`;
            return;
        }

        // Recorre los últimos usuarios y crea una fila para cada uno
        data.ultimos_usuarios.forEach(usuario => {
            let claseEstado = "";

            // Asigna clase visual según el estado del usuario
            if (usuario.estado === "Activo") {
                claseEstado = "status-ok";
            } else if (usuario.estado === "Bloqueado" || usuario.estado === "Inactivo") {
                claseEstado = "status-cancel";
            }

            // Crea la fila de la tabla
            const fila = document.createElement("tr");
            fila.innerHTML = `
                <td>${usuario.alias}</td>
                <td>${usuario.nombre_completo}</td>
                <td>${usuario.correo}</td>
                <td>${usuario.fecha_registro}</td>
                <td class="${claseEstado}">${usuario.estado}</td>
                <td><a href="admin-editar-usuario.html?id=${usuario.id_usuario}">Ver ficha</a></td>
            `;

            // Añade la fila a la tabla
            tbody.appendChild(fila);
        });

    } catch (error) {
        // Si hay un error inesperado, lo muestra en consola y redirige al login
        console.error(error);
        window.location.href = "../publico/socios.html";
    }
}