document.addEventListener("DOMContentLoaded", () => {
    cargarEntrenamientos();

    document.getElementById("btn-guardar-objetivo-semanal").addEventListener("click", async () => {
        await guardarObjetivoSemanal();
    });
});

function mostrarMensajeEntrenamientos(tipo, texto) {
    const mensaje = document.getElementById("mensaje-entrenamientos-socio");
    mensaje.className = `form-message ${tipo}`;
    mensaje.textContent = texto;
}

function aplicarBadgeEstado(estado) {
    const elemento = document.getElementById("detalle-entrenamiento-estado");
    elemento.textContent = estado || "No disponible";
    elemento.className = "status-badge";

    if (estado === "Completado") {
        elemento.classList.add("status-badge--ok");
    } else if (estado === "Programado") {
        elemento.classList.add("status-badge--wait");
    } else {
        elemento.classList.add("status-badge--cancel");
    }
}
/*
function pintarObjetivoSemanal(objetivo) {
    document.getElementById("objetivoSemanalInput").value = objetivo.objetivo_total;
    document.getElementById("objetivo-progreso-texto").textContent = `${objetivo.completados}/${objetivo.objetivo_total}`;
    document.getElementById("objetivo-progreso-subtexto").textContent =
        `Semana ${objetivo.semana} · ${objetivo.completados} entrenamiento(s) completado(s)`;

    const checks = document.getElementById("objetivo-checks");
    checks.innerHTML = "";

    for (let i = 1; i <= objetivo.objetivo_total; i++) {
        const item = document.createElement("span");
        item.className = "weekly-check-item";

        if (i <= objetivo.completados) {
            item.classList.add("weekly-check-item--done");
            item.textContent = "✓";
        } else {
            item.textContent = i;
        }

        checks.appendChild(item);
    }
}
*/


function pintarObjetivoSemanal(objetivo) {
    const input = document.getElementById("objetivoSemanalInput");
    const progresoTexto = document.getElementById("objetivo-progreso-texto");
    const progresoSubtexto = document.getElementById("objetivo-progreso-subtexto");
    const checks = document.getElementById("objetivo-checks");
    const barra = document.getElementById("barra-progreso");

    if (!input || !progresoTexto || !progresoSubtexto || !checks || !barra) {
        return;
    }

    input.value = objetivo.objetivo_total;
    progresoTexto.textContent = `${objetivo.completados}/${objetivo.objetivo_total}`;
    progresoSubtexto.textContent =
        `Semana ${objetivo.semana} · ${objetivo.completados} entrenamiento(s) completado(s)`;

    const porcentaje = objetivo.objetivo_total > 0
        ? Math.min((objetivo.completados / objetivo.objetivo_total) * 100, 100)
        : 0;

    barra.style.width = porcentaje + "%";

    checks.innerHTML = "";

    for (let i = 1; i <= objetivo.objetivo_total; i++) {
        const item = document.createElement("span");
        item.className = "weekly-check-item";

        if (i <= objetivo.completados) {
            item.classList.add("weekly-check-item--done");
            item.textContent = "✓";
        } else {
            item.textContent = i;
        }

        checks.appendChild(item);
    }
}






async function cargarEntrenamientos() {
    try {
        const response = await fetch("../API/entrenamientos.php", {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            mostrarMensajeEntrenamientos("error", data.mensaje || "No se pudieron cargar los entrenamientos.");
            return;
        }

        document.getElementById("sidebar-foto").src = data.sidebar.foto_perfil;
        document.getElementById("sidebar-nombre").textContent = data.sidebar.nombre_completo;
        document.getElementById("sidebar-plan").textContent = data.sidebar.membresia;

        document.getElementById("rutina-actual-nombre").textContent = data.resumen.rutina_nombre;
        document.getElementById("rutina-actual-detalle").textContent = data.resumen.rutina_detalle;
        document.getElementById("ultimo-entrenamiento-fecha").textContent = data.resumen.ultimo_fecha;
        document.getElementById("ultimo-entrenamiento-detalle").textContent = data.resumen.ultimo_detalle;

        pintarObjetivoSemanal(data.objetivo_semanal);

        const tbody = document.getElementById("tabla-entrenamientos-socio");
        tbody.innerHTML = "";

        if (!data.entrenamientos || data.entrenamientos.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5">No tienes entrenamientos registrados.</td></tr>`;
        } else {
            data.entrenamientos.forEach(item => {
                /* let claseEstado = item.estado === "Completado" ? "status-ok" : "status-wait";*/
                let claseEstado = item.estado === "Completado"
                    ? "status-completado"
                    : "status-programado";

                const acciones = `
                    <div class="actions-table">
                        <button class="btn btn--ghost btn--small" type="button"
                            onclick="verDetalleEntrenamiento(${item.id_entrenamiento})">
                            Ver detalle
                        </button>
                        ${item.estado === "Programado" ? `
                            <button class="btn btn--ghost btn--small" type="button"
                                onclick="marcarEntrenamientoCompletado(${item.id_entrenamiento})">
                                Marcar completado
                            </button>
                        ` : ""}
                    </div>
                `;

                const fila = document.createElement("tr");
                fila.innerHTML = `
                    <td>${item.fecha}</td>
                    <td>${item.rutina}</td>
                    <td>${item.duracion}</td>
                    <td class="${claseEstado}">${item.estado}</td>
                    <td>${acciones}</td>
                `;
                tbody.appendChild(fila);
            });
        }

        cargarDetalleEnPantalla(data.detalle_seleccionado, data.detalle_ejercicios || []);
        mostrarMensajeEntrenamientos("success", "Entrenamientos cargados correctamente.");

    } catch (error) {
        console.error(error);
        mostrarMensajeEntrenamientos("error", "Ha ocurrido un error al cargar los entrenamientos.");
    }
}

function cargarDetalleEnPantalla(detalle, ejercicios) {
    document.getElementById("detalle-entrenamiento-fecha").textContent = detalle?.fecha || "Sin datos";
    document.getElementById("detalle-entrenamiento-subtexto").textContent = detalle?.subtexto || "No hay entrenamiento seleccionado";
    document.getElementById("detalle-entrenamiento-observaciones").textContent = detalle?.observaciones || "Sin observaciones";
    aplicarBadgeEstado(detalle?.estado || "Sin datos");

    const tbody = document.getElementById("tabla-detalle-entrenamiento");
    tbody.innerHTML = "";

    if (!ejercicios || ejercicios.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4">No hay ejercicios registrados para mostrar.</td></tr>`;
        return;
    }

    ejercicios.forEach(item => {
        const fila = document.createElement("tr");
        fila.innerHTML = `
            <td>${item.ejercicio}</td>
            <td>${item.series}</td>
            <td>${item.repeticiones}</td>
            <td>${item.peso}</td>
        `;
        tbody.appendChild(fila);
    });
}

async function verDetalleEntrenamiento(idEntrenamiento) {
    try {
        const response = await fetch(`../API/entrenamientos.php?accion=detalle&id_entrenamiento=${idEntrenamiento}`, {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            mostrarMensajeEntrenamientos("error", data.mensaje || "No se pudo cargar el detalle del entrenamiento.");
            return;
        }

        cargarDetalleEnPantalla(data.detalle, data.ejercicios || []);
        document.getElementById("tabla-detalle-entrenamiento").scrollIntoView({ behavior: "smooth" });

    } catch (error) {
        console.error(error);
        mostrarMensajeEntrenamientos("error", "Ha ocurrido un error al cargar el detalle.");
    }
}

async function marcarEntrenamientoCompletado(idEntrenamiento) {
    const confirmar = confirm("¿Quieres marcar este entrenamiento como completado?");

    if (!confirmar) {
        mostrarMensajeEntrenamientos("warning", "Acción cancelada.");
        return;
    }

    try {
        const formData = new FormData();
        formData.append("accion", "marcar_completado");
        formData.append("id_entrenamiento", idEntrenamiento);

        const response = await fetch("../API/entrenamientos.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            mostrarMensajeEntrenamientos("error", data.mensaje || "No se pudo actualizar el entrenamiento.");
            return;
        }

        mostrarMensajeEntrenamientos("success", data.mensaje);
        await cargarEntrenamientos();

    } catch (error) {
        console.error(error);
        mostrarMensajeEntrenamientos("error", "Ha ocurrido un error al actualizar el entrenamiento.");
    }
}

async function guardarObjetivoSemanal() {
    const input = document.getElementById("objetivoSemanalInput");
    const objetivo = parseInt(input.value, 10);

    if (!objetivo || objetivo < 1) {
        mostrarMensajeEntrenamientos("error", "El objetivo semanal debe ser mayor que 0.");
        return;
    }

    try {
        const formData = new FormData();
        formData.append("accion", "guardar_objetivo_semanal");
        formData.append("objetivo_total", objetivo);

        const response = await fetch("../API/entrenamientos.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            mostrarMensajeEntrenamientos("error", data.mensaje || "No se pudo guardar el objetivo semanal.");
            return;
        }

        mostrarMensajeEntrenamientos("success", data.mensaje);
        await cargarEntrenamientos();

    } catch (error) {
        console.error(error);
        mostrarMensajeEntrenamientos("error", "Ha ocurrido un error al guardar el objetivo semanal.");
    }
}