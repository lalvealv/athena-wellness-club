<?php
session_start();

// 1. Si no hay sesión → fuera
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../publico/socios.html");
    exit;
}

// 2. Si es administrador → fuera del área de socios
if (isset($_SESSION['id_perfil']) && (int)$_SESSION['id_perfil'] === 1) {
    header("Location: ../admin/admin-panel.php");
    exit;
}
