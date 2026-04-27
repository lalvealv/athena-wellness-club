// Espera a que todo el HTML esté cargado antes de ejecutar JavaScript
document.addEventListener("DOMContentLoaded", () => {
    // Carga los datos del administrador en la página
    cargarAdminCrearUsuario();

    // Obtiene el formulario de creación de usuario
    const form = document.getElementById("form-crear-usuario");

    // Escucha el envío del formulario
    form.addEventListener("submit", async (event) => {
        // Evita que el formulario recargue la página
        event.preventDefault();

        // Envía los datos del formulario al backend
        await enviarFormularioCrearUsuario();
    });
});

// Carga los datos del administrador logueado
async function cargarAdminCrearUsuario() {
    try {
        // Hace una petición GET al archivo PHP
        const response = await fetch("../api/admin-crear-usuario.php", {
            method: "GET",
            credentials: "same-origin"
        });

        // Convierte la respuesta en JSON
        const data = await response.json();

        // Si hay error o no está autorizado, redirige al login
        if (!response.ok || !data.ok) {
            window.location.href = "../publico/socios.html";
            return;
        }

        // Muestra los datos del administrador en el sidebar
        document.getElementById("admin-foto").src = data.admin.foto_perfil;
        document.getElementById("admin-nombre").textContent = data.admin.nombre_completo;
        document.getElementById("admin-perfil").textContent = data.admin.perfil;

    } catch (error) {
        // Muestra el error en consola y redirige al login
        console.error(error);
        window.location.href = "../publico/socios.html";
    }
}

// Envía el formulario para crear un nuevo usuario
async function enviarFormularioCrearUsuario() {
    // Elemento donde se mostrará el mensaje del resultado
    const mensaje = document.getElementById("mensaje-form-crear-usuario");
    mensaje.textContent = "Creando usuario...";

    try {
        // Obtiene el formulario
        const form = document.getElementById("form-crear-usuario");

        // Convierte los campos del formulario en FormData
        const formData = new FormData(form);

        // Envía los datos al backend mediante POST
        const response = await fetch("../api/admin-crear-usuario.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        // Convierte la respuesta en JSON
        const data = await response.json();

        // Si hay error, muestra el mensaje recibido
        if (!response.ok || !data.ok) {
            mensaje.textContent = data.mensaje || "No se pudo crear el usuario.";
            return;
        }

        // Muestra mensaje de éxito
        mensaje.textContent = data.mensaje;

        // Limpia el formulario después de crear el usuario
        form.reset();

    } catch (error) {
        // Si hay fallo inesperado, lo muestra en consola y avisa al usuario
        console.error(error);
        mensaje.textContent = "Ha ocurrido un error al crear el usuario.";
    }
}