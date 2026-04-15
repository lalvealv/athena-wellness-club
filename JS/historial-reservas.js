document.addEventListener("DOMContentLoaded", () => {
    cargarHistorialReservas();
});

async function cargarHistorialReservas() {
    try {
        const response = await fetch("../api/historial-reservas.php", {
            method: "GET",
            credentials: "same-origin"
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            window.location.href = "../publico/socios.html";
            return;
        }

        document.getElementById("sidebar-foto").src = data.sidebar.foto_perfil;
        document.getElementById("sidebar-nombre").textContent = data.sidebar.nombre_completo;
        document.getElementById("sidebar-plan").textContent = data.sidebar.membresia;

        const tbody = document.getElementById("tabla-historial-reservas");
        tbody.innerHTML = "";

        if (!data.historial || data.historial.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5">No hay reservas registradas.</td></tr>`;
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

            const fila = document.createElement("tr");
            fila.innerHTML = `
                <td>${item.actividad}</td>
                <td>${item.fecha}</td>
                <td>${item.hora}</td>
                <td>${item.sala}</td>
                <td class="${claseEstado}">${item.estado}</td>
            `;
            tbody.appendChild(fila);
        });

    } catch (error) {
        console.error(error);
        window.location.href = "../publico/socios.html";
    }
}