document.addEventListener("DOMContentLoaded", () => {
    cargarReservas();

    const formulario = document.getElementById("form-filtro-reservas");
    const inputBusqueda = document.getElementById("buscarReserva");
    const selectEstado = document.getElementById("filtrarEstadoReserva");
    const inputFecha = document.getElementById("fechaReserva");
    const botonLimpiar = document.getElementById("btn-limpiar-filtros-reservas");

    formulario.addEventListener("submit", (event) => {
        event.preventDefault();
        cargarReservas(
            inputBusqueda.value.trim(),
            selectEstado.value,
            inputFecha.value
        );
    });

    botonLimpiar.addEventListener("click", () => {
        setTimeout(() => {
            cargarReservas("", "", "");
        }, 0);
    });
});

function mostrarMensajeReservas(tipo, texto) {
    const mensaje = document.getElementById("mensaje-admin-reservas");
    mensaje.className = `form-message ${tipo}`;
    mensaje.textContent = texto;
}

async function cargarReservas(busqueda = "", estado = "", fecha = "") {
    try {
        const url = new URL("../API/admin-reservas.php", window.location.href);

        if (busqueda !== "") {
            url.searchParams.set("buscar", busqueda);
        }
        if (estado !== "") {
            url.searchParams.set("estado", estado);
        }
        if (fecha !== "") {
            url.searchParams.set("fecha", fecha);
        }

        const response = await fetch(url, {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            mostrarMensajeReservas("error", data.mensaje || "No se pudieron cargar las reservas.");
            return;
        }

        document.getElementById("admin-foto").src = data.admin.foto_perfil;
        document.getElementById("admin-nombre").textContent = data.admin.nombre_completo;
        document.getElementById("admin-perfil").textContent = data.admin.perfil;

        document.getElementById("resumen-confirmadas").textContent = data.resumen.confirmadas;
        document.getElementById("resumen-asistidas").textContent = data.resumen.asistidas;
        document.getElementById("resumen-canceladas").textContent = data.resumen.canceladas;
        document.getElementById("resumen-no-asistidas").textContent = data.resumen.no_asistidas;

        const tbody = document.getElementById("tabla-reservas");
        tbody.innerHTML = "";

        if (!data.reservas || data.reservas.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9">No se encontraron reservas.</td></tr>`;
            mostrarMensajeReservas("warning", "No hay reservas para los filtros aplicados.");
            return;
        }

        data.reservas.forEach(item => {
            let claseEstado = "";

            if (item.estado === "Confirmada" || item.estado === "Asistida") {
                claseEstado = "status-ok";
            } else if (item.estado === "Cancelada" || item.estado === "No asistida") {
                claseEstado = "status-cancel";
            } else {
                claseEstado = "status-wait";
            }

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
            tbody.appendChild(fila);
        });

        mostrarMensajeReservas("success", "Reservas cargadas correctamente.");

    } catch (error) {
        console.error(error);
        mostrarMensajeReservas("error", "Ha ocurrido un error al cargar las reservas.");
    }
}

async function cambiarEstadoReserva(idReserva, nuevoEstado) {
    const confirmar = confirm(`¿Seguro que quieres cambiar el estado de esta reserva a "${nuevoEstado}"?`);

    if (!confirmar) {
        mostrarMensajeReservas("warning", "Cambio de estado cancelado.");
        return;
    }

    mostrarMensajeReservas("loading", "Actualizando estado de la reserva...");

    try {
        const formData = new FormData();
        formData.append("accion", "cambiar_estado");
        formData.append("id_reserva", idReserva);
        formData.append("estado", nuevoEstado);

        const response = await fetch("../API/admin-reservas.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            mostrarMensajeReservas("error", data.mensaje || "No se pudo actualizar el estado.");
            return;
        }

        mostrarMensajeReservas("success", data.mensaje);

        const inputBusqueda = document.getElementById("buscarReserva");
        const selectEstado = document.getElementById("filtrarEstadoReserva");
        const inputFecha = document.getElementById("fechaReserva");

        await cargarReservas(
            inputBusqueda.value.trim(),
            selectEstado.value,
            inputFecha.value
        );

    } catch (error) {
        console.error(error);
        mostrarMensajeReservas("error", "Ha ocurrido un error al actualizar la reserva.");
    }
}

async function eliminarReserva(idReserva) {
    const confirmar = confirm("¿Seguro que quieres eliminar esta reserva? Esta acción no se puede deshacer.");

    if (!confirmar) {
        mostrarMensajeReservas("warning", "Eliminación cancelada.");
        return;
    }

    mostrarMensajeReservas("loading", "Eliminando reserva...");

    try {
        const formData = new FormData();
        formData.append("accion", "eliminar");
        formData.append("id_reserva", idReserva);

        const response = await fetch("../API/admin-reservas.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            mostrarMensajeReservas("error", data.mensaje || "No se pudo eliminar la reserva.");
            return;
        }

        mostrarMensajeReservas("success", data.mensaje);

        const inputBusqueda = document.getElementById("buscarReserva");
        const selectEstado = document.getElementById("filtrarEstadoReserva");
        const inputFecha = document.getElementById("fechaReserva");

        await cargarReservas(
            inputBusqueda.value.trim(),
            selectEstado.value,
            inputFecha.value
        );

    } catch (error) {
        console.error(error);
        mostrarMensajeReservas("error", "Ha ocurrido un error al eliminar la reserva.");
    }
}