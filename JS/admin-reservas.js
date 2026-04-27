// Espera a que el HTML esté completamente cargado
document.addEventListener("DOMContentLoaded", () => {
    // Carga las reservas al abrir la página
    cargarReservas();

    // Obtiene los elementos del formulario de filtros
    const formulario = document.getElementById("form-filtro-reservas");
    const inputBusqueda = document.getElementById("buscarReserva");
    const selectEstado = document.getElementById("filtrarEstadoReserva");
    const inputFecha = document.getElementById("fechaReserva");
    const botonLimpiar = document.getElementById("btn-limpiar-filtros-reservas");

    // Aplica filtros al enviar el formulario
    formulario.addEventListener("submit", (event) => {
        event.preventDefault();

        cargarReservas(
            inputBusqueda.value.trim(),
            selectEstado.value,
            inputFecha.value
        );
    });

    // Limpia filtros y vuelve a cargar todas las reservas
    botonLimpiar.addEventListener("click", () => {
        setTimeout(() => {
            cargarReservas("", "", "");
        }, 0);
    });
});

// Muestra mensajes de estado en la pantalla de reservas
function mostrarMensajeReservas(tipo, texto) {
    const mensaje = document.getElementById("mensaje-admin-reservas");
    mensaje.className = `form-message ${tipo}`;
    mensaje.textContent = texto;
}

// Carga reservas desde el backend con filtros opcionales
async function cargarReservas(busqueda = "", estado = "", fecha = "") {
    try {
        // Construye la URL del endpoint
        const url = new URL("../API/admin-reservas.php", window.location.href);

        // Añade filtros si tienen valor
        if (busqueda !== "") {
            url.searchParams.set("buscar", busqueda);
        }
        if (estado !== "") {
            url.searchParams.set("estado", estado);
        }
        if (fecha !== "") {
            url.searchParams.set("fecha", fecha);
        }

        // Solicita datos al backend
        const response = await fetch(url, {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        // Si hay error, muestra mensaje
        if (!response.ok || !data.ok) {
            mostrarMensajeReservas("error", data.mensaje || "No se pudieron cargar las reservas.");
            return;
        }

        // Carga los datos del administrador
        document.getElementById("admin-foto").src = data.admin.foto_perfil;
        document.getElementById("admin-nombre").textContent = data.admin.nombre_completo;
        document.getElementById("admin-perfil").textContent = data.admin.perfil;

        // Carga las tarjetas de resumen diario
        document.getElementById("resumen-confirmadas").textContent = data.resumen.confirmadas;
        document.getElementById("resumen-asistidas").textContent = data.resumen.asistidas;
        document.getElementById("resumen-canceladas").textContent = data.resumen.canceladas;
        document.getElementById("resumen-no-asistidas").textContent = data.resumen.no_asistidas;

        // Limpia la tabla antes de pintarla
        const tbody = document.getElementById("tabla-reservas");
        tbody.innerHTML = "";

        // Si no hay reservas, muestra mensaje
        if (!data.reservas || data.reservas.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9">No se encontraron reservas.</td></tr>`;
            mostrarMensajeReservas("warning", "No hay reservas para los filtros aplicados.");
            return;
        }

        // Recorre las reservas y crea una fila por cada una
        data.reservas.forEach(item => {
            let claseEstado = "";

            // Asigna clase visual según el estado de la reserva
            if (item.estado === "Confirmada" || item.estado === "Asistida") {
                claseEstado = "status-ok";
            } else if (item.estado === "Cancelada" || item.estado === "No asistida") {
                claseEstado = "status-cancel";
            } else {
                claseEstado = "status-wait";
            }

            // Crea la fila de la tabla
            const fila = document.createElement("tr");
            fila.innerHTML = `
                <td>${item.id_reserva}</td>
                <td>${item.usuario}</td>
                <td>${item.actividad}</td>
                <td>${item.fecha}</td>
                <td>${item.horario}</td>
                <td>${item.sala}</td>
                <td>${item.instructor}</td>
                <td class="${claseEstado}">${item.estado}</td>
                <td>
                    <div class="actions-table">
                        <a href="admin-editar-usuario.html?id=${item.id_usuario}">Ver usuario</a>

                        <button class="btn btn--ghost btn--small" type="button"
                            onclick="cambiarEstadoReserva(${item.id_reserva}, 'Asistida')">
                            Marcar asistida
                        </button>

                        <button class="btn btn--ghost btn--small" type="button"
                            onclick="cambiarEstadoReserva(${item.id_reserva}, 'No asistida')">
                            Marcar no asistida
                        </button>

                        <button class="btn btn--ghost btn--small" type="button"
                            onclick="eliminarReserva(${item.id_reserva})">
                            Eliminar
                        </button>
                    </div>
                </td>
            `;

            // Añade la fila a la tabla
            tbody.appendChild(fila);
        });

        // Muestra mensaje de carga correcta
        mostrarMensajeReservas("success", "Reservas cargadas correctamente.");

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola
        console.error(error);
        mostrarMensajeReservas("error", "Ha ocurrido un error al cargar las reservas.");
    }
}

// Cambia el estado de una reserva
async function cambiarEstadoReserva(idReserva, nuevoEstado) {
    // Pide confirmación antes de hacer el cambio
    const confirmar = confirm(`¿Seguro que quieres cambiar el estado de esta reserva a "${nuevoEstado}"?`);

    if (!confirmar) {
        mostrarMensajeReservas("warning", "Cambio de estado cancelado.");
        return;
    }

    mostrarMensajeReservas("loading", "Actualizando estado de la reserva...");

    try {
        // Prepara los datos que se enviarán al backend
        const formData = new FormData();
        formData.append("accion", "cambiar_estado");
        formData.append("id_reserva", idReserva);
        formData.append("estado", nuevoEstado);

        // Envía la petición al backend
        const response = await fetch("../API/admin-reservas.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        const data = await response.json();

        // Si hay error, muestra mensaje
        if (!response.ok || !data.ok) {
            mostrarMensajeReservas("error", data.mensaje || "No se pudo actualizar el estado.");
            return;
        }

        // Muestra mensaje de éxito
        mostrarMensajeReservas("success", data.mensaje);

        // Recupera los filtros actuales
        const inputBusqueda = document.getElementById("buscarReserva");
        const selectEstado = document.getElementById("filtrarEstadoReserva");
        const inputFecha = document.getElementById("fechaReserva");

        // Recarga la tabla manteniendo filtros
        await cargarReservas(
            inputBusqueda.value.trim(),
            selectEstado.value,
            inputFecha.value
        );

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola
        console.error(error);
        mostrarMensajeReservas("error", "Ha ocurrido un error al actualizar la reserva.");
    }
}

// Elimina una reserva
async function eliminarReserva(idReserva) {
    // Pide confirmación porque la acción no se puede deshacer
    const confirmar = confirm("¿Seguro que quieres eliminar esta reserva? Esta acción no se puede deshacer.");

    if (!confirmar) {
        mostrarMensajeReservas("warning", "Eliminación cancelada.");
        return;
    }

    mostrarMensajeReservas("loading", "Eliminando reserva...");

    try {
        // Prepara los datos que se enviarán al backend
        const formData = new FormData();
        formData.append("accion", "eliminar");
        formData.append("id_reserva", idReserva);

        // Envía la petición de eliminación
        const response = await fetch("../API/admin-reservas.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        const data = await response.json();

        // Si hay error, muestra mensaje
        if (!response.ok || !data.ok) {
            mostrarMensajeReservas("error", data.mensaje || "No se pudo eliminar la reserva.");
            return;
        }

        // Muestra mensaje de éxito
        mostrarMensajeReservas("success", data.mensaje);

        // Recupera los filtros actuales
        const inputBusqueda = document.getElementById("buscarReserva");
        const selectEstado = document.getElementById("filtrarEstadoReserva");
        const inputFecha = document.getElementById("fechaReserva");

        // Recarga la tabla manteniendo filtros
        await cargarReservas(
            inputBusqueda.value.trim(),
            selectEstado.value,
            inputFecha.value
        );

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola
        console.error(error);
        mostrarMensajeReservas("error", "Ha ocurrido un error al eliminar la reserva.");
    }
}