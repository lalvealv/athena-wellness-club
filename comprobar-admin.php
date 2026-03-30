<?php
// Iniciar sesión
session_start();

// Si no hay sesión iniciada, enviar al login
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../publico/socios.html");
    exit;
}

// Si el perfil no es ADMIN (id_perfil = 1), bloquear acceso
if ((int)$_SESSION['id_perfil'] !== 1) {
    die("No tienes permisos para acceder a esta página.");
}
