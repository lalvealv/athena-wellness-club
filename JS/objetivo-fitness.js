document.addEventListener("DOMContentLoaded", () => {
    cargarObjetivoFitness();
});

async function cargarObjetivoFitness() {
    try {
        const response = await fetch("../api/objetivo-fitness.php", {
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

        document.getElementById("objetivo-actual").textContent = data.objetivo.nombre;
        document.getElementById("objetivo-descripcion").textContent = data.objetivo.descripcion;
        document.getElementById("objetivo-periodo").textContent = data.objetivo.periodo;
        document.getElementById("objetivo-estado").textContent = data.objetivo.estado;
        document.getElementById("objetivo-progreso-texto").textContent = data.objetivo.progreso + "%";

        const barra = document.getElementById("objetivo-progreso-barra");
        barra.style.width = data.objetivo.progreso + "%";

    } catch (error) {
        console.error(error);
        window.location.href = "../publico/socios.html";
    }
}