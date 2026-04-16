<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../publico/socios.html");
    exit;
}

if (!isset($_SESSION['id_perfil']) || (int)$_SESSION['id_perfil'] !== 1) {
    header("Location: ../publico/socios.html");
    exit;
}
