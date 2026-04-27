<?php
// Verifica que el usuario haya iniciado sesión

// Iniciar sesión para acceder a las variables de sesión
session_start();

// --- Comprobar si el usuario está logueado ---
if (!isset($_SESSION['id_usuario'])) {
    // Si no hay sesión activa, redirigir al login
    header("Location: ../publico/socios.html");
    exit;
}
