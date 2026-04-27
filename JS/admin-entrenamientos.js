// Espera a que el HTML esté completamente cargado
document.addEventListener("DOMContentLoaded", () => {
    // Carga la tabla de entrenamientos del panel admin
    cargarEntrenamientosAdmin();

    // Carga los usuarios disponibles en el select del formulario
    cargarUsuariosEntrenamiento();

    // Obtiene elementos del formulario de filtros
    const formulario = document.getElementById("form-filtro-entrenamientos");
    const inputBusqueda = document.getElementById("buscarEntrenamiento");
    const inputRutina = document.getElementById("filtrarRutina");
    const inputFecha = document.getElementById("fechaEntrenamiento");
    const botonLimpiar = document.getElementById("btn-limpiar-filtros-entrenamientos");

    // Aplica filtros al enviar el formulario
    formulario.addEventListener("submit", (event) => {
        event.preventDefault();

        cargarEntrenamientosAdmin(
            inputBusqueda.value.trim(),
            inputRutina.value.trim(),
            inputFecha.value
        );
    });

    // Limpia filtros y recarga todos los entrenamientos
    botonLimpiar.addEventListener("click", () => {
        setTimeout(() => {
            cargarEntrenamientosAdmin("", "", "");
        }, 0);
    });

    // Añade una nueva fila de ejercicio al formulario
    document.getElementById("btn-anadir-ejercicio").addEventListener("click", anadirFilaEjercicio);

    // Cancela la edición y limpia el formulario
    document.getElementById("btn-cancelar-edicion-entrenamiento").addEventListener("click", limpiarFormularioEntrenamiento);

    // Prepara el formulario para crear un entrenamiento nuevo
    document.getElementById("btn-nuevo-entrenamiento").addEventListener("click", () => {
        limpiarFormularioEntrenamiento();
        document.getElementById("form-entrenamiento-admin").scrollIntoView({ behavior: "smooth" });
    });

    // Guarda el entrenamiento al enviar el formulario
    document.getElementById("form-entrenamiento-admin").addEventListener("submit", async (event) => {
        event.preventDefault();
        await guardarEntrenamiento();
    });
});

// Muestra mensajes en la pantalla de entrenamientos admin
function mostrarMensajeEntrenamientos(tipo, texto) {
    const mensaje = document.getElementById("mensaje-admin-entrenamientos");
    mensaje.className = `form-message ${tipo}`;
    mensaje.textContent = texto;
}

// Añade una nueva fila para introducir un ejercicio
function anadirFilaEjercicio() {
    const tbody = document.getElementById("tabla-ejercicios-form");
    const fila = document.createElement("tr");

    // Crea los inputs de ejercicio, series, repeticiones y peso
    fila.innerHTML = `
        <td><input type="text" class="ejercicio-nombre" placeholder="Ejercicio"></td>
        <td><input type="number" class="ejercicio-series" min="1" placeholder="3"></td>
        <td><input type="number" class="ejercicio-repeticiones" min="1" placeholder="12"></td>
        <td><input type="number" class="ejercicio-peso" min="0" step="0.01" placeholder="20"></td>
        <td><button type="button" class="btn btn--ghost btn--small" onclick="eliminarFilaEjercicio(this)">Quitar</button></td>
    `;

    tbody.appendChild(fila);
}

// Elimina una fila de ejercicio o la limpia si solo queda una
function eliminarFilaEjercicio(boton) {
    const fila = boton.closest("tr");
    const tbody = document.getElementById("tabla-ejercicios-form");

    // Si solo queda una fila, no se elimina; se limpian sus campos
    if (tbody.children.length === 1) {
        fila.querySelector(".ejercicio-nombre").value = "";
        fila.querySelector(".ejercicio-series").value = "";
        fila.querySelector(".ejercicio-repeticiones").value = "";
        fila.querySelector(".ejercicio-peso").value = "";
        return;
    }

    // Si hay más de una fila, se elimina la seleccionada
    fila.remove();
}

// Limpia el formulario de crear/editar entrenamiento
function limpiarFormularioEntrenamiento() {
    document.getElementById("idEntrenamiento").value = "";
    document.getElementById("usuarioEntrenamiento").value = "";
    document.getElementById("fechaFormEntrenamiento").value = "";
    document.getElementById("duracionEntrenamiento").value = "";
    document.getElementById("estadoEntrenamiento").value = "Programado";
    document.getElementById("observacionesEntrenamiento").value = "";

    // Restaura la tabla de ejercicios con una fila vacía
    const tbody = document.getElementById("tabla-ejercicios-form");
    tbody.innerHTML = `
        <tr>
            <td><input type="text" class="ejercicio-nombre" placeholder="Ejercicio"></td>
            <td><input type="number" class="ejercicio-series" min="1" placeholder="3"></td>
            <td><input type="number" class="ejercicio-repeticiones" min="1" placeholder="12"></td>
            <td><input type="number" class="ejercicio-peso" min="0" step="0.01" placeholder="20"></td>
            <td><button type="button" class="btn btn--ghost btn--small" onclick="eliminarFilaEjercicio(this)">Quitar</button></td>
        </tr>
    `;
}

// Carga los usuarios clientes en el select del formulario de entrenamiento
async function cargarUsuariosEntrenamiento() {
    try {
        const response = await fetch("../API/admin-entrenamientos.php?accion=usuarios", {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        // Si hay error, no rellena el select
        if (!response.ok || !data.ok) {
            return;
        }

        const select = document.getElementById("usuarioEntrenamiento");
        select.innerHTML = `<option value="">Selecciona un usuario</option>`;

        // Crea una opción por cada usuario recibido
        data.usuarios.forEach(usuario => {
            const option = document.createElement("option");
            option.value = usuario.id_usuario;
            option.textContent = usuario.nombre_completo;
            select.appendChild(option);
        });

    } catch (error) {
        console.error(error);
    }
}

// Carga entrenamientos en el panel admin, con filtros opcionales
async function cargarEntrenamientosAdmin(busqueda = "", rutina = "", fecha = "") {
    try {
        // Construye la URL del endpoint
        const url = new URL("../API/admin-entrenamientos.php", window.location.href);

        // Añade filtros si tienen valor
        if (busqueda !== "") {
            url.searchParams.set("buscar", busqueda);
        }
        if (rutina !== "") {
            url.searchParams.set("rutina", rutina);
        }
        if (fecha !== "") {
            url.searchParams.set("fecha", fecha);
        }

        // Pide los datos al backend
        const response = await fetch(url, {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        // Si hay error, muestra mensaje
        if (!response.ok || !data.ok) {
            mostrarMensajeEntrenamientos("error", data.mensaje || "No se pudieron cargar los entrenamientos.");
            return;
        }

        // Carga datos del administrador
        document.getElementById("admin-foto").src = data.admin.foto_perfil;
        document.getElementById("admin-nombre").textContent = data.admin.nombre_completo;
        document.getElementById("admin-perfil").textContent = data.admin.perfil;

        // Carga los datos del resumen
        document.getElementById("resumen-entrenamientos-semana").textContent = data.resumen.entrenamientos_semana;
        document.getElementById("resumen-rutina-usada").textContent = data.resumen.rutina_usada;
        document.getElementById("resumen-usuarios-activos").textContent = data.resumen.usuarios_activos;
        document.getElementById("resumen-seguimiento-bajo").textContent = data.resumen.seguimiento_bajo;

        // Limpia la tabla antes de pintarla
        const tbody = document.getElementById("tabla-entrenamientos");
        tbody.innerHTML = "";

        // Si no hay entrenamientos, muestra mensaje en la tabla
        if (!data.entrenamientos || data.entrenamientos.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8">No se encontraron entrenamientos.</td></tr>`;
        } else {
            // Recorre entrenamientos y crea una fila por cada uno
            data.entrenamientos.forEach(item => {
                const estadoClass = item.estado === "Completado" ? "status-ok" : "status-wait";

                const fila = document.createElement("tr");
                fila.innerHTML = `
                    <td>${item.id_entrenamiento}</td>
                    <td>${item.usuario}</td>
                    <td>${item.rutina}</td>
                    <td>${item.fecha}</td>
                    <td>${item.duracion}</td>
                    <td class="${estadoClass}">${item.estado}</td>
                    <td>${item.observaciones}</td>
                    <td>
                        <div class="actions-table">
                            <a href="admin-editar-usuario.html?id=${item.id_usuario}">Ver usuario</a>
                            <button class="btn btn--ghost btn--small" type="button" onclick="editarEntrenamiento(${item.id_entrenamiento})">Editar</button>
                            <button class="btn btn--ghost btn--small" type="button" onclick="eliminarEntrenamiento(${item.id_entrenamiento})">Eliminar</button>
                        </div>
                    </td>
                `;

                tbody.appendChild(fila);
            });
        }

        // Obtiene el detalle del entrenamiento seleccionado por defecto
        const seleccionado = data.detalle?.seleccionado;
        const ejercicios = data.detalle?.ejercicios || [];

        // Muestra información del entrenamiento seleccionado
        document.getElementById("detalle-usuario").textContent = seleccionado?.usuario || "Sin datos";
        document.getElementById("detalle-subtexto-usuario").textContent = seleccionado?.subtexto_usuario || "No hay entrenamiento seleccionado";
        document.getElementById("detalle-rutina").textContent = seleccionado?.rutina || "Sin datos";
        document.getElementById("detalle-subtexto-rutina").textContent = seleccionado?.subtexto_rutina || "Sin detalle";

        // Pinta la tabla de ejercicios del detalle
        const tbodyDetalle = document.getElementById("tabla-detalle-entrenamiento-admin");
        tbodyDetalle.innerHTML = "";

        if (ejercicios.length === 0) {
            tbodyDetalle.innerHTML = `<tr><td colspan="4">No hay ejercicios registrados para este entrenamiento.</td></tr>`;
        } else {
            ejercicios.forEach(item => {
                const fila = document.createElement("tr");
                fila.innerHTML = `
                    <td>${item.ejercicio}</td>
                    <td>${item.series}</td>
                    <td>${item.repeticiones}</td>
                    <td>${item.peso}</td>
                `;

                tbodyDetalle.appendChild(fila);
            });
        }

        mostrarMensajeEntrenamientos("success", "Entrenamientos cargados correctamente.");

    } catch (error) {
        console.error(error);
        mostrarMensajeEntrenamientos("error", "Ha ocurrido un error al cargar los entrenamientos.");
    }
}

// Guarda un entrenamiento nuevo o editado
async function guardarEntrenamiento() {
    mostrarMensajeEntrenamientos("loading", "Guardando entrenamiento...");

    try {
        // Obtiene los valores principales del formulario
        const idEntrenamiento = document.getElementById("idEntrenamiento").value;
        const idUsuario = document.getElementById("usuarioEntrenamiento").value;
        const fecha = document.getElementById("fechaFormEntrenamiento").value;
        const duracion = document.getElementById("duracionEntrenamiento").value;
        const estado = document.getElementById("estadoEntrenamiento").value;
        const observaciones = document.getElementById("observacionesEntrenamiento").value.trim();

        // Valida campos obligatorios
        if (!idUsuario || !fecha || !duracion) {
            mostrarMensajeEntrenamientos("error", "Debes completar usuario, fecha y duración.");
            return;
        }

        // Recoge los ejercicios del formulario
        const ejercicios = [];
        const filas = document.querySelectorAll("#tabla-ejercicios-form tr");

        filas.forEach(fila => {
            const ejercicio = fila.querySelector(".ejercicio-nombre")?.value.trim() || "";
            const series = fila.querySelector(".ejercicio-series")?.value || "";
            const repeticiones = fila.querySelector(".ejercicio-repeticiones")?.value || "";
            const peso = fila.querySelector(".ejercicio-peso")?.value || "";

            // Solo añade ejercicios que tengan nombre
            if (ejercicio !== "") {
                ejercicios.push({
                    ejercicio,
                    series,
                    repeticiones,
                    peso
                });
            }
        });

        // Valida que haya al menos un ejercicio
        if (ejercicios.length === 0) {
            mostrarMensajeEntrenamientos("error", "Debes añadir al menos un ejercicio.");
            return;
        }

        // Prepara los datos que se enviarán al PHP
        const payload = {
            accion: idEntrenamiento ? "editar" : "crear",
            id_entrenamiento: idEntrenamiento,
            id_usuario: idUsuario,
            fecha,
            duracion_minutos: duracion,
            estado,
            observaciones,
            ejercicios
        };

        // Envía el entrenamiento al backend
        const response = await fetch("../API/admin-entrenamientos.php", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        // Si hay error, muestra mensaje
        if (!response.ok || !data.ok) {
            mostrarMensajeEntrenamientos("error", data.mensaje || "No se pudo guardar el entrenamiento.");
            return;
        }

        // Si se guarda bien, limpia y recarga la tabla
        mostrarMensajeEntrenamientos("success", data.mensaje);
        limpiarFormularioEntrenamiento();
        await cargarEntrenamientosAdmin();

    } catch (error) {
        console.error(error);
        mostrarMensajeEntrenamientos("error", "Ha ocurrido un error al guardar el entrenamiento.");
    }
}

// Carga un entrenamiento concreto para editarlo
async function editarEntrenamiento(idEntrenamiento) {
    try {
        // Solicita al backend el detalle del entrenamiento
        const response = await fetch(`../API/admin-entrenamientos.php?accion=detalle&id_entrenamiento=${idEntrenamiento}`, {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        // Si hay error, muestra mensaje
        if (!response.ok || !data.ok) {
            mostrarMensajeEntrenamientos("error", data.mensaje || "No se pudo cargar el entrenamiento.");
            return;
        }

        const entrenamiento = data.entrenamiento;
        const ejercicios = data.ejercicios || [];

        // Rellena los campos principales del formulario
        document.getElementById("idEntrenamiento").value = entrenamiento.id_entrenamiento;
        document.getElementById("usuarioEntrenamiento").value = entrenamiento.id_usuario;
        document.getElementById("fechaFormEntrenamiento").value = entrenamiento.fecha;
        document.getElementById("duracionEntrenamiento").value = entrenamiento.duracion_minutos;
        document.getElementById("estadoEntrenamiento").value = entrenamiento.estado;
        document.getElementById("observacionesEntrenamiento").value = entrenamiento.observaciones || "";

        // Limpia la tabla de ejercicios antes de rellenarla
        const tbody = document.getElementById("tabla-ejercicios-form");
        tbody.innerHTML = "";

        // Si no hay ejercicios, añade una fila vacía
        if (ejercicios.length === 0) {
            anadirFilaEjercicio();
        } else {
            // Crea una fila editable por cada ejercicio
            ejercicios.forEach(item => {
                const fila = document.createElement("tr");
                fila.innerHTML = `
                    <td><input type="text" class="ejercicio-nombre" value="${item.ejercicio || ""}" placeholder="Ejercicio"></td>
                    <td><input type="number" class="ejercicio-series" min="1" value="${item.series || ""}" placeholder="3"></td>
                    <td><input type="number" class="ejercicio-repeticiones" min="1" value="${item.repeticiones || ""}" placeholder="12"></td>
                    <td><input type="number" class="ejercicio-peso" min="0" step="0.01" value="${item.peso_num || ""}" placeholder="20"></td>
                    <td><button type="button" class="btn btn--ghost btn--small" onclick="eliminarFilaEjercicio(this)">Quitar</button></td>
                `;

                tbody.appendChild(fila);
            });
        }

        // Baja visualmente hasta el formulario
        document.getElementById("form-entrenamiento-admin").scrollIntoView({ behavior: "smooth" });
        mostrarMensajeEntrenamientos("warning", "Editando entrenamiento seleccionado.");

    } catch (error) {
        console.error(error);
        mostrarMensajeEntrenamientos("error", "Ha ocurrido un error al cargar el entrenamiento.");
    }
}

// Elimina un entrenamiento desde el panel admin
async function eliminarEntrenamiento(idEntrenamiento) {
    // Pide confirmación antes de eliminar
    const confirmar = confirm("¿Seguro que quieres eliminar este entrenamiento?");

    if (!confirmar) {
        mostrarMensajeEntrenamientos("warning", "Eliminación cancelada.");
        return;
    }

    try {
        // Envía la petición de eliminación al backend
        const response = await fetch("../API/admin-entrenamientos.php", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                accion: "eliminar",
                id_entrenamiento: idEntrenamiento
            })
        });

        const data = await response.json();

        // Si hay error, muestra mensaje
        if (!response.ok || !data.ok) {
            mostrarMensajeEntrenamientos("error", data.mensaje || "No se pudo eliminar el entrenamiento.");
            return;
        }

        // Si se elimina bien, recarga la tabla
        mostrarMensajeEntrenamientos("success", data.mensaje);
        await cargarEntrenamientosAdmin();

    } catch (error) {
        console.error(error);
        mostrarMensajeEntrenamientos("error", "Ha ocurrido un error al eliminar el entrenamiento.");
    }
}