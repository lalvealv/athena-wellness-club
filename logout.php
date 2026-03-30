<?php
// Iniciar la sesión actual
session_start();

// Vaciar todas las variables de sesión
session_unset();

// Destruir la sesión
session_destroy();

// Redirigir al inicio público
header("Location: publico/index.html");
exit;
