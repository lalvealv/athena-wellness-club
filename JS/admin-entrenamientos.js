document.addEventListener("DOMContentLoaded", () => {
    cargarEntrenamientosAdmin();
    cargarUsuariosEntrenamiento();

    const formulario = document.getElementById("form-filtro-entrenamientos");
    const inputBusqueda = document.getElementById("buscarEntrenamiento");
    const inputRutina = document.getElementById("filtrarRutina");
    const inputFecha = document.getElementById("fechaEntrenamiento");
    const botonLimpiar = document.getElementById("btn-limpiar-filtros-entrenamientos");

    formulario.addEventListener("submit", (event) => {
        event.preventDefault();
        cargarEntrenamientosAdmin(
            inputBusqueda.value.trim(),
            inputRutina.value.trim(),
            inputFecha.value
        );
    });

    botonLimpiar.addEventListener("click", () => {
        setTimeout(() => {
            cargarEntrenamientosAdmin("", "", "");
        }, 0);
    });

    document.getElementById("btn-anadir-ejercicio").addEventListener("click", anadirFilaEjercicio);
    document.getElementById("btn-cancelar-edicion-entrenamiento").addEventListener("click", limpiarFormularioEntrenamiento);

    document.getElementById("btn-nuevo-entrenamiento").addEventListener("click", () => {
        limpiarFormularioEntrenamiento();
        document.getElementById("form-entrenamiento-admin").scrollIntoView({ behavior: "smooth" });
    });

    document.getElementById("form-entrenamiento-admin").addEventListener("submit", async (event) => {
        event.preventDefault();
        await guardarEntrenamiento();
    });
});

function mostrarMensajeEntrenamientos(tipo, texto) {
    const mensaje = document.getElementById("mensaje-admin-entrenamientos");
    mensaje.className = `form-message ${tipo}`;
    mensaje.textContent = texto;
}

function anadirFilaEjercicio() {
    const tbody = document.getElementById("tabla-ejercicios-form");
    const fila = document.createElement("tr");
    fila.innerHTML = `
        <td><input type="text" class="ejercicio-nombre" placeholder="Ejercicio"></td>
        <td><input type="number" class="ejercicio-series" min="1" placeholder="3"></td>
        <td><input type="number" class="ejercicio-repeticiones" min="1" placeholder="12"></td>
        <td><input type="number" class="ejercicio-peso" min="0" step="0.01" placeholder="20"></td>
        <td><button type="button" class="btn btn--ghost btn--small" onclick="eliminarFilaEjercicio(this)">Quitar</button></td>
    `;
    tbody.appendChild(fila);
}

function eliminarFilaEjercicio(boton) {
    const fila = boton.closest("tr");
    const tbody = document.getElementById("tabla-ejercicios-form");

    if (tbody.children.length === 1) {
        fila.querySelector(".ejercicio-nombre").value = "";
        fila.querySelector(".ejercicio-series").value = "";
        fila.querySelector(".ejercicio-repeticiones").value = "";
        fila.querySelector(".ejercicio-peso").value = "";
        return;
    }

    fila.remove();
}

function limpiarFormularioEntrenamiento() {
    document.getElementById("idEntrenamiento").value = "";
    document.getElementById("usuarioEntrenamiento").value = "";
    document.getElementById("fechaFormEntrenamiento").value = "";
    document.getElementById("duracionEntrenamiento").value = "";
    document.getElementById("estadoEntrenamiento").value = "Programado";
    document.getElementById("observacionesEntrenamiento").value = "";

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

async function cargarUsuariosEntrenamiento() {
    try {
        const response = await fetch("../API/admin-entrenamientos.php?accion=usuarios", {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            return;
        }

        const select = document.getElementById("usuarioEntrenamiento");
        select.innerHTML = `<option value="">Selecciona un usuario</option>`;

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

async function cargarEntrenamientosAdmin(busqueda = "", rutina = "", fecha = "") {
    try {
        const url = new URL("../API/admin-entrenamientos.php", window.location.href);

        if (busqueda !== "") {
            url.searchParams.set("buscar", busqueda);
        }
        if (rutina !== "") {
            url.searchParams.set("rutina", rutina);
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
            mostrarMensajeEntrenamientos("error", data.mensaje || "No se pudieron cargar los entrenamientos.");
            return;
        }

        document.getElementById("admin-foto").src = data.admin.foto_perfil;
        document.getElementById("admin-nombre").textContent = data.admin.nombre_completo;
        document.getElementById("admin-perfil").textContent = data.admin.perfil;

        document.getElementById("resumen-entrenamientos-semana").textContent = data.resumen.entrenamientos_semana;
        document.getElementById("resumen-rutina-usada").textContent = data.resumen.rutina_usada;
        document.getElementById("resumen-usuarios-activos").textContent = data.resumen.usuarios_activos;
        document.getElementById("resumen-seguimiento-bajo").textContent = data.resumen.seguimiento_bajo;

        const tbody = document.getElementById("tabla-entrenamientos");
        tbody.innerHTML = "";

        if (!data.entrenamientos || data.entrenamientos.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8">No se encontraron entrenamientos.</td></tr>`;
        } else {
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

        const seleccionado = data.detalle?.seleccionado;
        const ejercicios = data.detalle?.ejercicios || [];

        document.getElementById("detalle-usuario").textContent = seleccionado?.usuario || "Sin datos";
        document.getElementById("detalle-subtexto-usuario").textContent = seleccionado?.subtexto_usuario || "No hay entrenamiento seleccionado";
        document.getElementById("detalle-rutina").textContent = seleccionado?.rutina || "Sin datos";
        document.getElementById("detalle-subtexto-rutina").textContent = seleccionado?.subtexto_rutina || "Sin detalle";

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

async function guardarEntrenamiento() {
    mostrarMensajeEntrenamientos("loading", "Guardando entrenamiento...");

    try {
        const idEntrenamiento = document.getElementById("idEntrenamiento").value;
        const idUsuario = document.getElementById("usuarioEntrenamiento").value;
        const fecha = document.getElementById("fechaFormEntrenamiento").value;
        const duracion = document.getElementById("duracionEntrenamiento").value;
        const estado = document.getElementById("estadoEntrenamiento").value;
        const observaciones = document.getElementById("observacionesEntrenamiento").value.trim();

        if (!idUsuario || !fecha || !duracion) {
            mostrarMensajeEntrenamientos("error", "Debes completar usuario, fecha y duración.");
            return;
        }

        const ejercicios = [];
        const filas = document.querySelectorAll("#tabla-ejercicios-form tr");

        filas.forEach(fila => {
            const ejercicio = fila.querySelector(".ejercicio-nombre")?.value.trim() || "";
            const series = fila.querySelector(".ejercicio-series")?.value || "";
            const repeticiones = fila.querySelector(".ejercicio-repeticiones")?.value || "";
            const peso = fila.querySelector(".ejercicio-peso")?.value || "";

            if (ejercicio !== "") {
                ejercicios.push({
                    ejercicio,
                    series,
                    repeticiones,
                    peso
                });
            }
        });

        if (ejercicios.length === 0) {
            mostrarMensajeEntrenamientos("error", "Debes añadir al menos un ejercicio.");
            return;
        }

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

        const response = await fetch("../API/admin-entrenamientos.php", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            mostrarMensajeEntrenamientos("error", data.mensaje || "No se pudo guardar el entrenamiento.");
            return;
        }

        mostrarMensajeEntrenamientos("success", data.mensaje);
        limpiarFormularioEntrenamiento();
        await cargarEntrenamientosAdmin();

    } catch (error) {
        console.error(error);
        mostrarMensajeEntrenamientos("error", "Ha ocurrido un error al guardar el entrenamiento.");
    }
}

async function editarEntrenamiento(idEntrenamiento) {
    try {
        const response = await fetch(`../API/admin-entrenamientos.php?accion=detalle&id_entrenamiento=${idEntrenamiento}`, {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            mostrarMensajeEntrenamientos("error", data.mensaje || "No se pudo cargar el entrenamiento.");
            return;
        }

        const entrenamiento = data.entrenamiento;
        const ejercicios = data.ejercicios || [];

        document.getElementById("idEntrenamiento").value = entrenamiento.id_entrenamiento;
        document.getElementById("usuarioEntrenamiento").value = entrenamiento.id_usuario;
        document.getElementById("fechaFormEntrenamiento").value = entrenamiento.fecha;
        document.getElementById("duracionEntrenamiento").value = entrenamiento.duracion_minutos;
        document.getElementById("estadoEntrenamiento").value = entrenamiento.estado;
        document.getElementById("observacionesEntrenamiento").value = entrenamiento.observaciones || "";

        const tbody = document.getElementById("tabla-ejercicios-form");
        tbody.innerHTML = "";

        if (ejercicios.length === 0) {
            anadirFilaEjercicio();
        } else {
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

        document.getElementById("form-entrenamiento-admin").scrollIntoView({ behavior: "smooth" });
        mostrarMensajeEntrenamientos("warning", "Editando entrenamiento seleccionado.");

    } catch (error) {
        console.error(error);
        mostrarMensajeEntrenamientos("error", "Ha ocurrido un error al cargar el entrenamiento.");
    }
}

async function eliminarEntrenamiento(idEntrenamiento) {
    const confirmar = confirm("¿Seguro que quieres eliminar este entrenamiento?");

    if (!confirmar) {
        mostrarMensajeEntrenamientos("warning", "Eliminación cancelada.");
        return;
    }

    try {
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

        if (!response.ok || !data.ok) {
            mostrarMensajeEntrenamientos("error", data.mensaje || "No se pudo eliminar el entrenamiento.");
            return;
        }

        mostrarMensajeEntrenamientos("success", data.mensaje);
        await cargarEntrenamientosAdmin();

    } catch (error) {
        console.error(error);
        mostrarMensajeEntrenamientos("error", "Ha ocurrido un error al eliminar el entrenamiento.");
    }
}