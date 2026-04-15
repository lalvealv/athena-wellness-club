document.addEventListener("DOMContentLoaded", () => {
    cargarAdminCrearUsuario();

    const form = document.getElementById("form-crear-usuario");
    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        await enviarFormularioCrearUsuario();
    });
});

async function cargarAdminCrearUsuario() {
    try {
        const response = await fetch("../api/admin-crear-usuario.php", {
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

    } catch (error) {
        console.error(error);
        window.location.href = "../publico/socios.html";
    }
}

async function enviarFormularioCrearUsuario() {
    const mensaje = document.getElementById("mensaje-form-crear-usuario");
    mensaje.textContent = "Creando usuario...";

    try {
        const form = document.getElementById("form-crear-usuario");
        const formData = new FormData(form);

        const response = await fetch("../api/admin-crear-usuario.php", {
            method: "POST",
            credentials: "same-origin",
            body: formData
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            mensaje.textContent = data.mensaje || "No se pudo crear el usuario.";
            return;
        }

        mensaje.textContent = data.mensaje;
        form.reset();

    } catch (error) {
        console.error(error);
        mensaje.textContent = "Ha ocurrido un error al crear el usuario.";
    }
}