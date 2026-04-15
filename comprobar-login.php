<?php
// Iniciar sesión para poder comprobar si el usuario ha iniciado sesión
session_start();

// Si no existe un usuario logueado, redirigir al login
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../publico/socios.html");
    exit;
}

/*
<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../publico/socios.html");
    exit;
}
?>*/