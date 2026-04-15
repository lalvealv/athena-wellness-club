document.addEventListener("DOMContentLoaded", () => {
    cargarResumenSocio();
});

async function cargarResumenSocio() {
    try {
        const response = await fetch("../api/area-socios.php", {
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

        document.getElementById("resumen-suscripcion").textContent = data.resumen.suscripcion;
        document.getElementById("resumen-renovacion").textContent = data.resumen.renovacion;

        document.getElementById("resumen-proxima-clase").textContent = data.resumen.proxima_clase;
        document.getElementById("resumen-proxima-clase-detalle").textContent = data.resumen.proxima_clase_detalle;

        document.getElementById("resumen-entrenamientos-semana").textContent = data.resumen.entrenamientos_semana;
        document.getElementById("resumen-entrenamientos-mensaje").textContent = data.resumen.entrenamientos_mensaje;

        document.getElementById("resumen-objetivo-progreso").textContent = data.resumen.objetivo_progreso;
        document.getElementById("resumen-objetivo-texto").textContent = data.resumen.objetivo_texto;

    } catch (error) {
        console.error(error);
        window.location.href = "../publico/socios.html";
    }
}