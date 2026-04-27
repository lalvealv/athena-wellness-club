// Espera a que el HTML esté completamente cargado
document.addEventListener("DOMContentLoaded", () => {
    // Carga el historial de reservas del socio
    cargarHistorialReservas();
});

// Carga el historial de reservas desde el backend
async function cargarHistorialReservas() {
    const mensaje = document.getElementById("mensaje-historial-reservas");

    try {
        // Solicita el historial de reservas al archivo PHP
        const response = await fetch("../api/historial-reservas.php", {
            method: "GET",
            credentials: "same-origin"
        });

        // Convierte la respuesta a JSON
        const data = await response.json();

        // Si hay error de sesión, redirige al login
        if (!data.ok) {
            if (data.mensaje && data.mensaje.toLowerCase().includes("sesión")) {
                window.location.href = "../publico/socios.html";
                return;
            }

            // Si es otro error, muestra el mensaje
            mensaje.textContent = data.mensaje || "No se ha podido cargar el historial.";
            return;
        }

        // Carga datos del usuario en el sidebar
        document.getElementById("sidebar-foto").src = data.sidebar.foto_perfil;
        document.getElementById("sidebar-nombre").textContent = data.sidebar.nombre_completo;
        document.getElementById("sidebar-plan").textContent = data.sidebar.membresia;

        // Prepara la tabla de historial
        const tbody = document.getElementById("tabla-historial-reservas");
        tbody.innerHTML = "";

        // Si no hay historial, muestra mensaje
        if (!data.historial || data.historial.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6">No hay reservas registradas.</td></tr>`;
            return;
        }

        // Recorre las reservas y crea una fila por cada una
        data.historial.forEach(item => {
            let claseEstado = "";

            // Asigna clase visual según el estado de la reserva
            if (item.estado === "Confirmada" || item.estado === "Asistida") {
                claseEstado = "status-ok";
            } else if (item.estado === "Cancelada" || item.estado === "No asistida") {
                claseEstado = "status-cancel";
            } else {
                claseEstado = "status-wait";
            }

            // Por defecto no hay acción disponible
            let accion = "—";

            // Si todavía se puede cancelar, muestra botón
            if (item.puede_cancelar) {
                accion = `<button class="cancel-btn" onclick="cancelarReserva(${item.id_reserva})">Cancelar</button>`;
            }

            // Crea la fila de la tabla
            const fila = document.createElement("tr");
            fila.innerHTML = `
                <td>${item.actividad}</td>
                <td>${item.fecha}</td>
                <td>${item.hora}</td>
                <td>${item.sala}</td>
                <td><span class="${claseEstado}">${item.estado}</span></td>
                <td>${accion}</td>
            `;

            // Añade la fila a la tabla
            tbody.appendChild(fila);
        });

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola
        console.error(error);
        mensaje.textContent = "Ha ocurrido un error al cargar el historial.";
    }
}

// Cancela una reserva desde el historial
async function cancelarReserva(idReserva) {
    const mensaje = document.getElementById("mensaje-historial-reservas");

    // Pide confirmación antes de cancelar
    const confirmar = confirm("¿Quieres cancelar esta reserva?");
    if (!confirmar) {
        return;
    }

    mensaje.textContent = "Cancelando reserva...";

    try {
        // Prepara los datos que se enviarán al backend
        const formData = new FormData();
        formData.append("accion", "cancelar");
        formData.append("id_reserva", idReserva);

        // Envía la petición de cancelación
        const response = await fetch("../api/historial-reservas.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        // Convierte la respuesta en JSON
        const data = await response.json();

        // Muestra el mensaje devuelto por PHP
        mensaje.textContent = data.mensaje;

        // Si se cancela correctamente, recarga el historial
        if (data.ok) {
            await cargarHistorialReservas();

            // Si existe la función de horarios, también actualiza esa pantalla
            if (typeof cargarHorariosReservas === "function") {
                cargarHorariosReservas();
            }
        }

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola
        console.error(error);
        mensaje.textContent = "Ha ocurrido un error al cancelar la reserva.";
    }
}
