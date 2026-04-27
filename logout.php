<?php

// Cierra la sesión del usuario y redirige al login

// Iniciar la sesión actual
session_start();

// --- Eliminar todas las variables de sesión ---
session_unset();

// --- Destruir completamente la sesión ---
session_destroy();

// --- Redirigir al usuario a la página de acceso ---
header("Location: publico/socios.html");
exit;
