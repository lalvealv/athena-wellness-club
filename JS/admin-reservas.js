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

async function cargarReservas(busqueda = "", estado = "", fecha = "") {
    try {
        const url = new URL("../api/admin-reservas.php", window.location.href);

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
            window.location.href = "../publico/socios.html";
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
            return;
        }

        data.reservas.forEach(item => {
            let claseEstado = "";

            if (item.estado === "Confirmada" || item.estado === "Asistida") {
                claseEstado = "status-ok";
            } else if (item.estado === "Cancelada" || item.estado === "No asistida") {
                claseEstado = "status-cancel";
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
                <td><a href="admin-editar-usuario.html?id=${item.id_usuario}">Ver usuario</a></td>
            `;
            tbody.appendChild(fila);
        });

    } catch (error) {
        console.error(error);
        window.location.href = "../publico/socios.html";
    }
}