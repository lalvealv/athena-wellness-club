// Espera a que el HTML esté completamente cargado
document.addEventListener("DOMContentLoaded", () => {
    // Carga el objetivo fitness del socio
    cargarObjetivoFitness();
});

// Carga los datos del objetivo fitness desde el backend
async function cargarObjetivoFitness() {
    try {
        // Solicita los datos al archivo PHP
        const response = await fetch("../api/objetivo-fitness.php", {
            method: "GET",
            credentials: "same-origin"
        });

        // Convierte la respuesta a JSON
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

        // Carga los datos del objetivo fitness
        document.getElementById("objetivo-actual").textContent = data.objetivo.nombre;
        document.getElementById("objetivo-descripcion").textContent = data.objetivo.descripcion;
        document.getElementById("objetivo-periodo").textContent = data.objetivo.periodo;
        document.getElementById("objetivo-estado").textContent = data.objetivo.estado;
        document.getElementById("objetivo-progreso-texto").textContent = data.objetivo.progreso + "%";

        // Actualiza visualmente la barra de progreso
        const barra = document.getElementById("objetivo-progreso-barra");
        barra.style.width = data.objetivo.progreso + "%";

    } catch (error) {
        // Si ocurre un error inesperado, lo muestra en consola y redirige al login
        console.error(error);
        window.location.href = "../publico/socios.html";
    }
}