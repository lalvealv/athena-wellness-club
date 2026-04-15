<?php
session_start();
require_once 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Acceso no permitido.");
}

if (!isset($_SESSION['recuperacion_id_usuario'])) {
    die("No se ha iniciado el proceso de recuperación de contraseña.");
}

$idUsuario = (int) $_SESSION['recuperacion_id_usuario'];
$password = $_POST['password'] ?? '';
$password2 = $_POST['password2'] ?? '';

if (empty($password) || empty($password2)) {
    die("Debes completar ambos campos.");
}

if ($password !== $password2) {
    die("Las contraseñas no coinciden.");
}

if (!preg_match('/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
    die("La contraseña debe tener al menos 8 caracteres, una mayúscula y un número.");
}

try {
    $contrasenaHash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "UPDATE usuario
            SET contrasena = :contrasena
            WHERE id_usuario = :id_usuario";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':contrasena' => $contrasenaHash,
        ':id_usuario' => $idUsuario
    ]);

    unset($_SESSION['recuperacion_id_usuario']);
    unset($_SESSION['recuperacion_correo']);

    header("Location: publico/socios.html?password=ok");
    exit;
} catch (PDOException $e) {
    die("Error al actualizar la contraseña: " . $e->getMessage());
}
