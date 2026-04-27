<?php
// Actualiza la contraseña del usuario tras iniciar recuperación

// Iniciar sesión para acceder al usuario que está recuperando la contraseña
session_start();

// Incluir conexión a la base de datos
require_once 'conexion.php';

// Comprobar que el formulario se envía por POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Acceso no permitido.");
}

// Comprobar que existe un proceso de recuperación activo
if (!isset($_SESSION['recuperacion_id_usuario'])) {
    die("No se ha iniciado el proceso de recuperación de contraseña.");
}

// Recoger datos necesarios
$idUsuario = (int) $_SESSION['recuperacion_id_usuario'];
$password = $_POST['password'] ?? '';
$password2 = $_POST['password2'] ?? '';

// Validar campos obligatorios
if (empty($password) || empty($password2)) {
    die("Debes completar ambos campos.");
}

// Validar que ambas contraseñas coincidan
if ($password !== $password2) {
    die("Las contraseñas no coinciden.");
}

// Validar seguridad de la contraseña
if (!preg_match('/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
    die("La contraseña debe tener al menos 8 caracteres, una mayúscula y un número.");
}

try {
    // Cifrar la nueva contraseña antes de guardarla
    $contrasenaHash = password_hash($password, PASSWORD_DEFAULT);

    // Actualizar la contraseña del usuario
    $sql = "UPDATE usuario
            SET contrasena = :contrasena
            WHERE id_usuario = :id_usuario";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':contrasena' => $contrasenaHash,
        ':id_usuario' => $idUsuario
    ]);

    // Eliminar datos temporales de recuperación
    unset($_SESSION['recuperacion_id_usuario']);
    unset($_SESSION['recuperacion_correo']);

    // Redirigir al login con mensaje de confirmación
    header("Location: publico/socios.html?password=ok");
    exit;
} catch (PDOException $e) {
    // Mostrar error si falla la actualización
    die("Error al actualizar la contraseña: " . $e->getMessage());
}
