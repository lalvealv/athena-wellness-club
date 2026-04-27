<?php
// Comprueba si el correo existe y permite iniciar recuperación de contraseña

// Iniciar sesión para guardar datos temporales del proceso
session_start();

// Incluir conexión a la base de datos
require_once 'conexion.php';

// --- Comprobar que la petición sea POST ---
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Acceso no permitido.");
}

// --- Recoger y limpiar el correo ---
$correo = trim($_POST['correo'] ?? '');

// --- Validar que no esté vacío ---
if (empty($correo)) {
    die("Debes introducir un correo electrónico.");
}

// --- Validar formato de email ---
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    die("El correo electrónico no es válido.");
}

try {
    // --- Buscar usuario por correo ---
    $sql = "SELECT id_usuario, nombre, correo
            FROM usuario
            WHERE correo = :correo
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':correo' => $correo
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- Si no existe el usuario ---
    if (!$usuario) {
        die("No existe ninguna cuenta con ese correo.");
    }

    // --- Guardar datos en sesión para el siguiente paso ---
    $_SESSION['recuperacion_id_usuario'] = $usuario['id_usuario'];
    $_SESSION['recuperacion_correo'] = $usuario['correo'];

    // --- Redirigir a la página para establecer nueva contraseña ---
    header("Location: publico/nueva-password.html");
    exit;
} catch (PDOException $e) {
    // --- Manejo de errores ---
    die("Error al comprobar el correo: " . $e->getMessage());
}
