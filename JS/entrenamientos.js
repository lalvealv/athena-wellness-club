// Espera a que el HTML esté completamente cargado
document.addEventListener("DOMContentLoaded", () => {
    // Carga los entrenamientos del socio al abrir la página
    cargarEntrenamientos();

    // Asocia el botón de guardar objetivo semanal con su función
    document.getElementById("btn-guardar-objetivo-semanal").addEventListener("click", async () => {
        await guardarObjetivoSemanal();
    });
});

// Muestra mensajes de estado en la pantalla de entrenamientos del socio
function mostrarMensajeEntrenamientos(tipo, texto) {
    const mensaje = document.getElementById("mensaje-entrenamientos-socio");
    mensaje.className = `form-message ${tipo}`;
    mensaje.textContent = texto;
}

// Aplica una clase visual al estado del entrenamiento seleccionado
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

// Pinta el objetivo semanal, la barra de progreso y los checks visuales
function pintarObjetivoSemanal(objetivo) {
    const input = document.getElementById("objetivoSemanalInput");
    const progresoTexto = document.getElementById("objetivo-progreso-texto");
    const progresoSubtexto = document.getElementById("objetivo-progreso-subtexto");
    const checks = document.getElementById("objetivo-checks");
    const barra = document.getElementById("barra-progreso");

    // Evita errores si algún elemento no existe en el HTML
    if (!input || !progresoTexto || !progresoSubtexto || !checks || !barra) {
        return;
    }

    // Rellena el objetivo actual y el progreso numérico
    input.value = objetivo.objetivo_total;
    progresoTexto.textContent = `${objetivo.completados}/${objetivo.objetivo_total}`;
    progresoSubtexto.textContent =
        `Semana ${objetivo.semana} · ${objetivo.completados} entrenamiento(s) completado(s)`;

    // Calcula el porcentaje de progreso sin superar el 100%
    const porcentaje = objetivo.objetivo_total > 0
        ? Math.min((objetivo.completados / objetivo.objetivo_total) * 100, 100)
        : 0;

    // Actualiza el ancho de la barra de progreso
    barra.style.width = porcentaje + "%";

    // Limpia los checks antes de volver a pintarlos
    checks.innerHTML = "";

    // Crea un check por cada entrenamiento objetivo
    for (let i = 1; i <= objetivo.objetivo_total; i++) {
        const item = document.createElement("span");
        item.className = "weekly-check-item";

        // Marca como completado si corresponde
        if (i <= objetivo.completados) {
            item.classList.add("weekly-check-item--done");
            item.textContent = "✓";
        } else {
            item.textContent = i;
        }

        checks.appendChild(item);
    }
}

// Carga entrenamientos, resumen y objetivo semanal del socio
async function cargarEntrenamientos() {
    try {
        const response = await fetch("../API/entrenamientos.php", {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        // Si hay error, muestra mensaje
        if (!response.ok || !data.ok) {
            mostrarMensajeEntrenamientos("error", data.mensaje || "No se pudieron cargar los entrenamientos.");
            return;
        }

        // Carga los datos del usuario en el sidebar
        document.getElementById("sidebar-foto").src = data.sidebar.foto_perfil;
        document.getElementById("sidebar-nombre").textContent = data.sidebar.nombre_completo;
        document.getElementById("sidebar-plan").textContent = data.sidebar.membresia;

        // Carga tarjetas de rutina y último entrenamiento
        document.getElementById("rutina-actual-nombre").textContent = data.resumen.rutina_nombre;
        document.getElementById("rutina-actual-detalle").textContent = data.resumen.rutina_detalle;
        document.getElementById("ultimo-entrenamiento-fecha").textContent = data.resumen.ultimo_fecha;
        document.getElementById("ultimo-entrenamiento-detalle").textContent = data.resumen.ultimo_detalle;

        // Pinta el objetivo semanal
        pintarObjetivoSemanal(data.objetivo_semanal);

        // Prepara la tabla de entrenamientos
        const tbody = document.getElementById("tabla-entrenamientos-socio");
        tbody.innerHTML = "";

        // Si no hay entrenamientos, muestra mensaje
        if (!data.entrenamientos || data.entrenamientos.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5">No tienes entrenamientos registrados.</td></tr>`;
        } else {
            // Pinta cada entrenamiento en la tabla
            data.entrenamientos.forEach(item => {
                let claseEstado = item.estado === "Completado"
                    ? "status-completado"
                    : "status-programado";

                // Botones disponibles para cada entrenamiento
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

                // Crea la fila del entrenamiento
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

        // Carga el detalle inicial en pantalla
        cargarDetalleEnPantalla(data.detalle_seleccionado, data.detalle_ejercicios || []);

        // Muestra mensaje de éxito
        mostrarMensajeEntrenamientos("success", "Entrenamientos cargados correctamente.");

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola
        console.error(error);
        mostrarMensajeEntrenamientos("error", "Ha ocurrido un error al cargar los entrenamientos.");
    }
}

// Muestra el detalle de un entrenamiento en la parte inferior
function cargarDetalleEnPantalla(detalle, ejercicios) {
    // Rellena los datos generales del detalle
    document.getElementById("detalle-entrenamiento-fecha").textContent = detalle?.fecha || "Sin datos";
    document.getElementById("detalle-entrenamiento-subtexto").textContent = detalle?.subtexto || "No hay entrenamiento seleccionado";
    document.getElementById("detalle-entrenamiento-observaciones").textContent = detalle?.observaciones || "Sin observaciones";

    // Aplica badge visual al estado
    aplicarBadgeEstado(detalle?.estado || "Sin datos");

    // Prepara la tabla de ejercicios
    const tbody = document.getElementById("tabla-detalle-entrenamiento");
    tbody.innerHTML = "";

    // Si no hay ejercicios, muestra mensaje
    if (!ejercicios || ejercicios.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4">No hay ejercicios registrados para mostrar.</td></tr>`;
        return;
    }

    // Pinta cada ejercicio
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

// Carga el detalle de un entrenamiento concreto
async function verDetalleEntrenamiento(idEntrenamiento) {
    try {
        const response = await fetch(`../API/entrenamientos.php?accion=detalle&id_entrenamiento=${idEntrenamiento}`, {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        // Si hay error, muestra mensaje
        if (!response.ok || !data.ok) {
            mostrarMensajeEntrenamientos("error", data.mensaje || "No se pudo cargar el detalle del entrenamiento.");
            return;
        }

        // Carga el detalle recibido
        cargarDetalleEnPantalla(data.detalle, data.ejercicios || []);

        // Baja hasta la tabla de detalle
        document.getElementById("tabla-detalle-entrenamiento").scrollIntoView({ behavior: "smooth" });

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola
        console.error(error);
        mostrarMensajeEntrenamientos("error", "Ha ocurrido un error al cargar el detalle.");
    }
}

// Marca un entrenamiento programado como completado
async function marcarEntrenamientoCompletado(idEntrenamiento) {
    // Pide confirmación antes de modificar el entrenamiento
    const confirmar = confirm("¿Quieres marcar este entrenamiento como completado?");

    if (!confirmar) {
        mostrarMensajeEntrenamientos("warning", "Acción cancelada.");
        return;
    }

    try {
        // Prepara los datos para enviar al backend
        const formData = new FormData();
        formData.append("accion", "marcar_completado");
        formData.append("id_entrenamiento", idEntrenamiento);

        // Envía la petición al backend
        const response = await fetch("../API/entrenamientos.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        const data = await response.json();

        // Si hay error, muestra mensaje
        if (!response.ok || !data.ok) {
            mostrarMensajeEntrenamientos("error", data.mensaje || "No se pudo actualizar el entrenamiento.");
            return;
        }

        // Si todo va bien, recarga la pantalla
        mostrarMensajeEntrenamientos("success", data.mensaje);
        await cargarEntrenamientos();

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola
        console.error(error);
        mostrarMensajeEntrenamientos("error", "Ha ocurrido un error al actualizar el entrenamiento.");
    }
}

// Guarda o actualiza el objetivo semanal del socio
async function guardarObjetivoSemanal() {
    const input = document.getElementById("objetivoSemanalInput");
    const objetivo = parseInt(input.value, 10);

    // Valida que el objetivo sea mayor que 0
    if (!objetivo || objetivo < 1) {
        mostrarMensajeEntrenamientos("error", "El objetivo semanal debe ser mayor que 0.");
        return;
    }

    try {
        // Prepara los datos para enviar al backend
        const formData = new FormData();
        formData.append("accion", "guardar_objetivo_semanal");
        formData.append("objetivo_total", objetivo);

        // Envía el nuevo objetivo semanal
        const response = await fetch("../API/entrenamientos.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        const data = await response.json();

        // Si hay error, muestra mensaje
        if (!response.ok || !data.ok) {
            mostrarMensajeEntrenamientos("error", data.mensaje || "No se pudo guardar el objetivo semanal.");
            return;
        }

        // Si se guarda bien, recarga los datos actualizados
        mostrarMensajeEntrenamientos("success", data.mensaje);
        await cargarEntrenamientos();

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola
        console.error(error);
        mostrarMensajeEntrenamientos("error", "Ha ocurrido un error al guardar el objetivo semanal.");
    }
}