document.addEventListener("DOMContentLoaded", () => {
    cargarEntrenamientosAdmin();

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
});

async function cargarEntrenamientosAdmin(busqueda = "", rutina = "", fecha = "") {
    try {
        const url = new URL("../api/admin-entrenamientos.php", window.location.href);

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
            window.location.href = "../publico/socios.html";
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
            tbody.innerHTML = `<tr><td colspan="7">No se encontraron entrenamientos.</td></tr>`;
        } else {
            data.entrenamientos.forEach(item => {
                const fila = document.createElement("tr");
                fila.innerHTML = `
                    <td>${item.id_entrenamiento}</td>
                    <td>${item.usuario}</td>
                    <td>${item.rutina}</td>
                    <td>${item.fecha}</td>
                    <td>${item.duracion}</td>
                    <td>${item.observaciones}</td>
                    <td><a href="admin-editar-usuario.html?id=${item.id_usuario}">Ver usuario</a></td>
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

    } catch (error) {
        console.error(error);
        window.location.href = "../publico/socios.html";
    }
}