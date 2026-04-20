document.addEventListener("DOMContentLoaded", () => {
    cargarSuscripciones();

    const formulario = document.getElementById("form-filtro-suscripciones");
    const inputBusqueda = document.getElementById("buscarSuscripcion");
    const selectPlan = document.getElementById("filtrarPlan");
    const selectEstado = document.getElementById("filtrarEstado");
    const botonLimpiar = document.getElementById("btn-limpiar-filtros-suscripciones");

    formulario.addEventListener("submit", (event) => {
        event.preventDefault();
        cargarSuscripciones(
            inputBusqueda.value.trim(),
            selectPlan.value,
            selectEstado.value
        );
    });

    botonLimpiar.addEventListener("click", () => {
        setTimeout(() => {
            cargarSuscripciones("", "", "");
        }, 0);
    });
});

function mostrarMensajeSuscripciones(tipo, texto) {
    const mensaje = document.getElementById("mensaje-admin-suscripciones");
    mensaje.className = `form-message ${tipo}`;
    mensaje.textContent = texto;
}

async function cargarSuscripciones(busqueda = "", plan = "", estado = "") {
    try {
        const url = new URL("../API/admin-suscripciones.php", window.location.href);

        if (busqueda !== "") {
            url.searchParams.set("buscar", busqueda);
        }
        if (plan !== "") {
            url.searchParams.set("plan", plan);
        }
        if (estado !== "") {
            url.searchParams.set("estado", estado);
        }

        const response = await fetch(url, {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            mostrarMensajeSuscripciones("error", data.mensaje || "No se pudieron cargar las suscripciones.");
            return;
        }

        document.getElementById("admin-foto").src = data.admin.foto_perfil;
        document.getElementById("admin-nombre").textContent = data.admin.nombre_completo;
        document.getElementById("admin-perfil").textContent = data.admin.perfil;

        document.getElementById("resumen-essential").textContent = data.resumen.essential;
        document.getElementById("resumen-premium").textContent = data.resumen.premium;
        document.getElementById("resumen-executive").textContent = data.resumen.executive;
        document.getElementById("resumen-canceladas").textContent = data.resumen.canceladas;

        const tbody = document.getElementById("tabla-suscripciones");
        tbody.innerHTML = "";

        if (!data.suscripciones || data.suscripciones.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9">No se encontraron suscripciones.</td></tr>`;
            mostrarMensajeSuscripciones("warning", "No hay resultados para los filtros aplicados.");
            return;
        }

        data.suscripciones.forEach(item => {
            let claseEstado = "";

            if (item.estado === "Activa") {
                claseEstado = "status-ok";
            } else if (item.estado === "Cancelada" || item.estado === "Finalizada") {
                claseEstado = "status-cancel";
            } else {
                claseEstado = "status-wait";
            }

            const fila = document.createElement("tr");
            fila.innerHTML = `
                <td>${item.id_suscripcion}</td>
                <td>${item.usuario}</td>
                <td>${item.plan}</td>
                <td>${item.precio}</td>
                <td>${item.fecha_inicio}</td>
                <td>${item.fecha_renovacion}</td>
                <td class="${claseEstado}">${item.estado}</td>
                <td>${item.renovacion_automatica}</td>
                <td><a href="admin-editar-usuario.html?id=${item.id_usuario}">Ver ficha</a></td>
            `;
            tbody.appendChild(fila);
        });

        mostrarMensajeSuscripciones("success", "Suscripciones cargadas correctamente.");

    } catch (error) {
        console.error(error);
        mostrarMensajeSuscripciones("error", "Ha ocurrido un error al cargar las suscripciones.");
    }
}