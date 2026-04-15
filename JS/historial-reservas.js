document.addEventListener("DOMContentLoaded", () => {
    cargarHistorialReservas();
});

async function cargarHistorialReservas() {
    const mensaje = document.getElementById("mensaje-historial-reservas");

    try {
        const response = await fetch("../api/historial-reservas.php", {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        if (!data.ok) {
            if (data.mensaje && data.mensaje.toLowerCase().includes("sesión")) {
                window.location.href = "../publico/socios.html";
                return;
            }

            mensaje.textContent = data.mensaje || "No se ha podido cargar el historial.";
            return;
        }

        document.getElementById("sidebar-foto").src = data.sidebar.foto_perfil;
        document.getElementById("sidebar-nombre").textContent = data.sidebar.nombre_completo;
        document.getElementById("sidebar-plan").textContent = data.sidebar.membresia;

        const tbody = document.getElementById("tabla-historial-reservas");
        tbody.innerHTML = "";

        if (!data.historial || data.historial.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6">No hay reservas registradas.</td></tr>`;
            return;
        }

        data.historial.forEach(item => {
            let claseEstado = "";

            if (item.estado === "Confirmada" || item.estado === "Asistida") {
                claseEstado = "status-ok";
            } else if (item.estado === "Cancelada" || item.estado === "No asistida") {
                claseEstado = "status-cancel";
            } else {
                claseEstado = "status-wait";
            }

            let accion = "—";

            if (item.puede_cancelar) {
                accion = `<button class="cancel-btn" onclick="cancelarReserva(${item.id_reserva})">Cancelar</button>`;
            }

            const fila = document.createElement("tr");
            fila.innerHTML = `
                <td>${item.actividad}</td>
                <td>${item.fecha}</td>
                <td>${item.hora}</td>
                <td>${item.sala}</td>
                <td><span class="${claseEstado}">${item.estado}</span></td>
                <td>${accion}</td>
            `;
            tbody.appendChild(fila);
        });

    } catch (error) {
        console.error(error);
        mensaje.textContent = "Ha ocurrido un error al cargar el historial.";
    }
}

async function cancelarReserva(idReserva) {
    const mensaje = document.getElementById("mensaje-historial-reservas");

    const confirmar = confirm("¿Quieres cancelar esta reserva?");
    if (!confirmar) {
        return;
    }

    mensaje.textContent = "Cancelando reserva...";

    try {
        const formData = new FormData();
        formData.append("accion", "cancelar");
        formData.append("id_reserva", idReserva);

        const response = await fetch("../api/historial-reservas.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        const data = await response.json();
        mensaje.textContent = data.mensaje;

        if (data.ok) {
            await cargarHistorialReservas();

            //  recargar también horarios automáticamente
            if (typeof cargarHorariosReservas === "function") {
                cargarHorariosReservas();
            }
        }

    } catch (error) {
        console.error(error);
        mensaje.textContent = "Ha ocurrido un error al cancelar la reserva.";
    }
}