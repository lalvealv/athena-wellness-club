// Espera a que el HTML esté completamente cargado
document.addEventListener("DOMContentLoaded", () => {
    // Carga horarios y reservas al abrir la página
    cargarHorariosReservas();
});

// Carga horarios, resumen y datos del usuario desde el backend
async function cargarHorariosReservas() {
    const mensaje = document.getElementById("mensaje-reserva");

    try {
        // Solicita los datos al archivo PHP
        const response = await fetch("../api/horarios-reservas.php", {
            method: "GET",
            credentials: "same-origin"
        });

        // Convierte la respuesta a JSON
        const data = await response.json();

        // Muestra los datos en consola para depuración
        console.log("DATOS API RESERVAS:", data);

        // Si hay error de sesión, redirige al login
        if (!data.ok) {
            if (data.mensaje && data.mensaje.toLowerCase().includes("sesión")) {
                window.location.href = "../publico/socios.html";
                return;
            }

            // Si es otro error, muestra el mensaje
            mensaje.textContent = data.mensaje || "No se han podido cargar los horarios.";
            return;
        }

        // Carga los datos del usuario en el sidebar
        document.getElementById("foto-usuario-sidebar").src = data.usuario.foto_perfil;
        document.getElementById("nombre-usuario-sidebar").textContent = data.usuario.nombre_completo;
        document.getElementById("membresia-usuario-sidebar").textContent = data.usuario.membresia;

        // Carga las tarjetas de resumen
        document.getElementById("resumen-proxima-clase").textContent = data.resumen.proxima_clase;
        document.getElementById("resumen-proxima-fecha").textContent = data.resumen.proxima_fecha;
        document.getElementById("resumen-reservas-activas").textContent = data.resumen.reservas_activas;
        document.getElementById("resumen-ultima-reserva").textContent = data.resumen.ultima_reserva;
        document.getElementById("resumen-ultima-fecha").textContent = data.resumen.ultima_fecha;
        document.getElementById("resumen-sesiones-disponibles").textContent = data.resumen.sesiones_disponibles;

        // Pinta la cabecera y el cuerpo de la tabla
        renderCabecera(data.columnas || []);
        renderTabla(data.filas || []);

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola
        console.error(error);
        mensaje.textContent = "Ha ocurrido un error al cargar los horarios.";
    }
}

// Pinta la cabecera de la tabla con los días y fechas
function renderCabecera(columnas) {
    const thead = document.getElementById("cabecera-horario-semanal");

    // Si no hay columnas, muestra cabecera vacía
    if (!columnas || columnas.length === 0) {
        thead.innerHTML = `
            <tr>
                <th>Horario</th>
                <th>Sin fechas disponibles</th>
            </tr>
        `;
        return;
    }

    // Primera columna fija: horario
    let html = `<tr><th>Horario</th>`;

    // Añade una columna por cada fecha recibida
    columnas.forEach(col => {
        html += `
            <th>
                <div class="table-day">${col.dia}</div>
                <div class="table-date">${col.fecha}</div>
            </th>
        `;
    });

    html += `</tr>`;

    // Inserta la cabecera en la tabla
    thead.innerHTML = html;
}

// Pinta el cuerpo de la tabla de horarios
function renderTabla(filas) {
    const tbody = document.getElementById("tabla-horario-semanal-socios");

    // Si no hay filas, muestra mensaje
    if (!filas || filas.length === 0) {
        tbody.innerHTML = `<tr><td colspan="30">No hay horarios disponibles.</td></tr>`;
        return;
    }

    // Limpia la tabla antes de pintarla
    tbody.innerHTML = "";

    // Recorre cada franja horaria
    filas.forEach(fila => {
        const tr = document.createElement("tr");

        // Primera celda: franja horaria
        let html = `<td class="time-col">${fila.franja}</td>`;

        // Recorre cada celda de la franja
        fila.celdas.forEach(celda => {
            // Si no hay clase en esa celda, pinta vacío
            if (celda.vacia) {
                html += `<td class="empty-slot">—</td>`;
                return;
            }

            // Define la clase visual según el estado de la sesión
            let claseEstado = "";
            if (celda.estado === "Confirmada") claseEstado = "status-ok";
            if (celda.estado === "Completa" || celda.estado === "Cancelada" || celda.estado === "Completada") claseEstado = "status-cancel";
            if (celda.estado === "Disponible" || celda.estado === "Pendiente") claseEstado = "status-wait";

            // Si se puede reservar, muestra botón; si no, muestra estado
            let accion = "";
            if (celda.puede_reservar) {
                accion = `<button class="reserve-btn" onclick="reservarSesion(${celda.id_horario}, '${celda.fecha_iso}')">Reservar</button>`;
            } else {
                accion = `<span class="${claseEstado}">${celda.estado}</span>`;
            }

            // Crea la celda con los datos de la clase
            html += `
                <td>
                    <div class="class-slot">
                        <div class="class-name">${celda.actividad}</div>
                        <span><strong>Sala:</strong> ${celda.sala}</span>
                        <span><strong>Monitor:</strong> ${celda.monitor}</span>
                        <span><strong>Fecha:</strong> ${celda.fecha}</span>
                        <span><strong>${celda.plazas}</strong></span>
                        ${accion}
                    </div>
                </td>
            `;
        });

        // Inserta las celdas en la fila
        tr.innerHTML = html;

        // Añade la fila a la tabla
        tbody.appendChild(tr);
    });
}

// Realiza la reserva de una sesión
async function reservarSesion(idHorario, fechaIso) {
    const mensaje = document.getElementById("mensaje-reserva");

    // Informa al usuario de que se está procesando
    mensaje.textContent = "Realizando reserva...";

    try {
        // Prepara los datos para enviar al backend
        const formData = new FormData();
        formData.append("id_horario", idHorario);
        formData.append("fecha", fechaIso);

        // Envía la petición de reserva
        const response = await fetch("../api/horarios-reservas.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        // Convierte la respuesta a JSON
        const data = await response.json();

        // Muestra el mensaje devuelto por PHP
        mensaje.textContent = data.mensaje;

        // Si la reserva se realiza correctamente, recarga horarios
        if (data.ok) {
            await cargarHorariosReservas();
        }

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola
        console.error(error);
        mensaje.textContent = "Ha ocurrido un error al realizar la reserva.";
    }
}