// Espera a que el HTML esté completamente cargado
document.addEventListener("DOMContentLoaded", () => {
    // Carga las suscripciones al abrir la página
    cargarSuscripciones();

    // Obtiene los elementos del formulario de filtros
    const formulario = document.getElementById("form-filtro-suscripciones");
    const inputBusqueda = document.getElementById("buscarSuscripcion");
    const selectPlan = document.getElementById("filtrarPlan");
    const selectEstado = document.getElementById("filtrarEstado");
    const botonLimpiar = document.getElementById("btn-limpiar-filtros-suscripciones");

    // Aplica filtros al enviar el formulario
    formulario.addEventListener("submit", (event) => {
        event.preventDefault();

        cargarSuscripciones(
            inputBusqueda.value.trim(),
            selectPlan.value,
            selectEstado.value
        );
    });

    // Limpia filtros y recarga todas las suscripciones
    botonLimpiar.addEventListener("click", () => {
        setTimeout(() => {
            cargarSuscripciones("", "", "");
        }, 0);
    });
});

// Muestra mensajes de estado en la pantalla de suscripciones
function mostrarMensajeSuscripciones(tipo, texto) {
    const mensaje = document.getElementById("mensaje-admin-suscripciones");
    mensaje.className = `form-message ${tipo}`;
    mensaje.textContent = texto;
}

// Carga suscripciones desde el backend con filtros opcionales
async function cargarSuscripciones(busqueda = "", plan = "", estado = "") {
    try {
        // Construye la URL del endpoint
        const url = new URL("../API/admin-suscripciones.php", window.location.href);

        // Añade filtros solo si tienen valor
        if (busqueda !== "") {
            url.searchParams.set("buscar", busqueda);
        }
        if (plan !== "") {
            url.searchParams.set("plan", plan);
        }
        if (estado !== "") {
            url.searchParams.set("estado", estado);
        }

        // Solicita datos al backend
        const response = await fetch(url, {
            method: "GET",
            credentials: "same-origin"
        });

        // Convierte la respuesta en JSON
        const data = await response.json();

        // Si hay error, muestra mensaje
        if (!response.ok || !data.ok) {
            mostrarMensajeSuscripciones("error", data.mensaje || "No se pudieron cargar las suscripciones.");
            return;
        }

        // Carga los datos del administrador
        document.getElementById("admin-foto").src = data.admin.foto_perfil;
        document.getElementById("admin-nombre").textContent = data.admin.nombre_completo;
        document.getElementById("admin-perfil").textContent = data.admin.perfil;

        // Carga las tarjetas de resumen de planes
        document.getElementById("resumen-essential").textContent = data.resumen.essential;
        document.getElementById("resumen-premium").textContent = data.resumen.premium;
        document.getElementById("resumen-executive").textContent = data.resumen.executive;
        document.getElementById("resumen-canceladas").textContent = data.resumen.canceladas;

        // Limpia la tabla antes de pintarla
        const tbody = document.getElementById("tabla-suscripciones");
        tbody.innerHTML = "";

        // Si no hay resultados, muestra mensaje en la tabla
        if (!data.suscripciones || data.suscripciones.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9">No se encontraron suscripciones.</td></tr>`;
            mostrarMensajeSuscripciones("warning", "No hay resultados para los filtros aplicados.");
            return;
        }

        // Recorre las suscripciones y crea una fila por cada una
        data.suscripciones.forEach(item => {
            let claseEstado = "";

            // Asigna clase visual según el estado de la suscripción
            if (item.estado === "Activa") {
                claseEstado = "status-ok";
            } else if (item.estado === "Cancelada" || item.estado === "Finalizada") {
                claseEstado = "status-cancel";
            } else {
                claseEstado = "status-wait";
            }

            // Crea la fila de la tabla
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

            // Añade la fila a la tabla
            tbody.appendChild(fila);
        });

        // Muestra mensaje de carga correcta
        mostrarMensajeSuscripciones("success", "Suscripciones cargadas correctamente.");

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola
        console.error(error);
        mostrarMensajeSuscripciones("error", "Ha ocurrido un error al cargar las suscripciones.");
    }
}