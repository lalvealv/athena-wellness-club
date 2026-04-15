document.addEventListener("DOMContentLoaded", () => {
    cargarHorariosReservas();
});

async function cargarHorariosReservas() {
    const mensaje = document.getElementById("mensaje-reserva");

    try {
        const response = await fetch("../api/horarios-reservas.php", {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();
        console.log("DATOS API RESERVAS:", data);

        if (!data.ok) {
            if (data.mensaje && data.mensaje.toLowerCase().includes("sesión")) {
                window.location.href = "../publico/socios.html";
                return;
            }

            mensaje.textContent = data.mensaje || "No se han podido cargar los horarios.";
            return;
        }

        document.getElementById("foto-usuario-sidebar").src = data.usuario.foto_perfil;
        document.getElementById("nombre-usuario-sidebar").textContent = data.usuario.nombre_completo;
        document.getElementById("membresia-usuario-sidebar").textContent = data.usuario.membresia;

        document.getElementById("resumen-proxima-clase").textContent = data.resumen.proxima_clase;
        document.getElementById("resumen-proxima-fecha").textContent = data.resumen.proxima_fecha;
        document.getElementById("resumen-reservas-activas").textContent = data.resumen.reservas_activas;
        document.getElementById("resumen-ultima-reserva").textContent = data.resumen.ultima_reserva;
        document.getElementById("resumen-ultima-fecha").textContent = data.resumen.ultima_fecha;
        document.getElementById("resumen-sesiones-disponibles").textContent = data.resumen.sesiones_disponibles;

        renderCabecera(data.columnas || []);
        renderTabla(data.filas || []);

    } catch (error) {
        console.error(error);
        mensaje.textContent = "Ha ocurrido un error al cargar los horarios.";
    }
}

function renderCabecera(columnas) {
    const thead = document.getElementById("cabecera-horario-semanal");

    if (!columnas || columnas.length === 0) {
        thead.innerHTML = `
            <tr>
                <th>Horario</th>
                <th>Sin fechas disponibles</th>
            </tr>
        `;
        return;
    }

    let html = `<tr><th>Horario</th>`;

    columnas.forEach(col => {
        html += `
            <th>
                <div class="table-day">${col.dia}</div>
                <div class="table-date">${col.fecha}</div>
            </th>
        `;
    });

    html += `</tr>`;
    thead.innerHTML = html;
}

function renderTabla(filas) {
    const tbody = document.getElementById("tabla-horario-semanal-socios");

    if (!filas || filas.length === 0) {
        tbody.innerHTML = `<tr><td colspan="30">No hay horarios disponibles.</td></tr>`;
        return;
    }

    tbody.innerHTML = "";

    filas.forEach(fila => {
        const tr = document.createElement("tr");
        let html = `<td class="time-col">${fila.franja}</td>`;

        fila.celdas.forEach(celda => {
            if (celda.vacia) {
                html += `<td class="empty-slot">—</td>`;
                return;
            }

            let claseEstado = "";
            if (celda.estado === "Confirmada") claseEstado = "status-ok";
            if (celda.estado === "Completa" || celda.estado === "Cancelada" || celda.estado === "Completada") claseEstado = "status-cancel";
            if (celda.estado === "Disponible" || celda.estado === "Pendiente") claseEstado = "status-wait";

            let accion = "";
            if (celda.puede_reservar) {
                accion = `<button class="reserve-btn" onclick="reservarSesion(${celda.id_horario}, '${celda.fecha_iso}')">Reservar</button>`;
            } else {
                accion = `<span class="${claseEstado}">${celda.estado}</span>`;
            }

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

        tr.innerHTML = html;
        tbody.appendChild(tr);
    });
}

async function reservarSesion(idHorario, fechaIso) {
    const mensaje = document.getElementById("mensaje-reserva");
    mensaje.textContent = "Realizando reserva...";

    try {
        const formData = new FormData();
        formData.append("id_horario", idHorario);
        formData.append("fecha", fechaIso);

        const response = await fetch("../api/horarios-reservas.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        const data = await response.json();
        mensaje.textContent = data.mensaje;

        if (data.ok) {
            await cargarHorariosReservas();
        }

    } catch (error) {
        console.error(error);
        mensaje.textContent = "Ha ocurrido un error al realizar la reserva.";
    }
}