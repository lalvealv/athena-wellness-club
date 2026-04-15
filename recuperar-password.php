<?php
session_start();
require_once 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Acceso no permitido.");
}

$correo = trim($_POST['correo'] ?? '');

if (empty($correo)) {
    die("Debes introducir un correo electrónico.");
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    die("El correo electrónico no es válido.");
}

try {
    $sql = "SELECT id_usuario, nombre, correo
            FROM usuario
            WHERE correo = :correo
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':correo' => $correo
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        die("No existe ninguna cuenta con ese correo.");
    }

    $_SESSION['recuperacion_id_usuario'] = $usuario['id_usuario'];
    $_SESSION['recuperacion_correo'] = $usuario['correo'];

    header("Location: publico/nueva-password.html");
    exit;
} catch (PDOException $e) {
    die("Error al comprobar el correo: " . $e->getMessage());
}
