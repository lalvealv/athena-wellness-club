// Espera a que el HTML esté completamente cargado
document.addEventListener("DOMContentLoaded", () => {
    // Carga el resumen general del área de socios
    cargarResumenSocio();
});

// Carga los datos principales del socio desde el backend
async function cargarResumenSocio() {
    try {
        // Solicita los datos al archivo PHP
        const response = await fetch("../api/area-socios.php", {
            method: "GET",
            credentials: "same-origin"
        });

        // Convierte la respuesta en JSON
        const data = await response.json();

        // Si no hay sesión válida, redirige al login
        if (!response.ok || !data.ok) {
            window.location.href = "../publico/socios.html";
            return;
        }

        // Carga los datos del usuario en el sidebar
        document.getElementById("sidebar-foto").src = data.sidebar.foto_perfil;
        document.getElementById("sidebar-nombre").textContent = data.sidebar.nombre_completo;
        document.getElementById("sidebar-plan").textContent = data.sidebar.membresia;

        // Carga información de suscripción
        document.getElementById("resumen-suscripcion").textContent = data.resumen.suscripcion;
        document.getElementById("resumen-renovacion").textContent = data.resumen.renovacion;

        // Carga próxima clase reservada
        document.getElementById("resumen-proxima-clase").textContent = data.resumen.proxima_clase;
        document.getElementById("resumen-proxima-clase-detalle").textContent = data.resumen.proxima_clase_detalle;

        // Carga entrenamientos semanales
        document.getElementById("resumen-entrenamientos-semana").textContent = data.resumen.entrenamientos_semana;
        document.getElementById("resumen-entrenamientos-mensaje").textContent = data.resumen.entrenamientos_mensaje;

        // Carga progreso del objetivo fitness
        document.getElementById("resumen-objetivo-progreso").textContent = data.resumen.objetivo_progreso;
        document.getElementById("resumen-objetivo-texto").textContent = data.resumen.objetivo_texto;

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola y redirige al login
        console.error(error);
        window.location.href = "../publico/socios.html";
    }
}