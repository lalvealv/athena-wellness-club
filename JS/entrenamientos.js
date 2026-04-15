document.addEventListener("DOMContentLoaded", () => {
    cargarEntrenamientos();
});

async function cargarEntrenamientos() {
    try {
        const response = await fetch("../api/entrenamientos.php", {
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

        document.getElementById("rutina-actual-nombre").textContent = data.resumen.rutina_nombre;
        document.getElementById("rutina-actual-detalle").textContent = data.resumen.rutina_detalle;
        document.getElementById("ultimo-entrenamiento-fecha").textContent = data.resumen.ultimo_fecha;
        document.getElementById("ultimo-entrenamiento-detalle").textContent = data.resumen.ultimo_detalle;

        const tbody = document.getElementById("tabla-detalle-entrenamiento");
        tbody.innerHTML = "";

        if (!data.detalle || data.detalle.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4">No hay ejercicios registrados para mostrar.</td></tr>`;
            return;
        }

        data.detalle.forEach(item => {
            const fila = document.createElement("tr");
            fila.innerHTML = `
                <td>${item.ejercicio}</td>
                <td>${item.series}</td>
                <td>${item.repeticiones}</td>
                <td>${item.peso}</td>
            `;
            tbody.appendChild(fila);
        });

    } catch (error) {
        console.error(error);
        window.location.href = "../publico/socios.html";
    }
}