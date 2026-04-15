document.addEventListener("DOMContentLoaded", () => {
    cargarEstadisticas();
});

async function cargarEstadisticas() {
    try {
        const response = await fetch("../api/estadisticas.php", {
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

        document.getElementById("estadistica-clases-mes").textContent = data.estadisticas.clases_mes;
        document.getElementById("estadistica-clases-mes-detalle").textContent = data.estadisticas.clases_mes_detalle;

        document.getElementById("estadistica-actividad-favorita").textContent = data.estadisticas.actividad_favorita;
        document.getElementById("estadistica-actividad-favorita-detalle").textContent = data.estadisticas.actividad_favorita_detalle;

        document.getElementById("estadistica-entrenamientos-semanales").textContent = data.estadisticas.entrenamientos_semanales;
        document.getElementById("estadistica-entrenamientos-semanales-detalle").textContent = data.estadisticas.entrenamientos_semanales_detalle;

        document.getElementById("estadistica-asistencia").textContent = data.estadisticas.asistencia;
        document.getElementById("estadistica-asistencia-detalle").textContent = data.estadisticas.asistencia_detalle;

    } catch (error) {
        console.error(error);
        window.location.href = "../publico/socios.html";
    }
}