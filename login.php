<?php
// Iniciar sesión para poder guardar datos del usuario logueado
session_start();

// Incluir archivo de conexión a la base de datos
require_once 'conexion.php';

// Comprobar que el formulario se envía por POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Acceso no permitido.");
}

// Recoger datos del formulario
$identificador = trim($_POST['identificador'] ?? '');
$password = $_POST['password'] ?? '';

// Comprobar que los campos no estén vacíos
if (empty($identificador) || empty($password)) {
    die("Debes completar todos los campos.");
}

try {
    // Buscar usuario por alias o por correo
    $sql = "SELECT * FROM usuario 
            WHERE alias = :identificador OR correo = :identificador
            LIMIT 1";

    // Preparar consulta
    $stmt = $conn->prepare($sql);

    // Ejecutar consulta
    $stmt->execute([
        ':identificador' => $identificador
    ]);

    // Obtener datos del usuario
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si no existe el usuario
    if (!$usuario) {
        die("Usuario o contraseña incorrectos.");
    }

    // Comprobar que el usuario esté activo
    if ($usuario['estado'] !== 'Activo') {
        die("Tu cuenta no está activa. Contacta con el club.");
    }

    // Verificar contraseña cifrada
    if (!password_verify($password, $usuario['contrasena'])) {
        die("Usuario o contraseña incorrectos.");
    }

    // Guardar datos en la sesión
    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['alias'] = $usuario['alias'];
    $_SESSION['nombre'] = $usuario['nombre'];
    $_SESSION['apellidos'] = $usuario['apellidos'];
    $_SESSION['correo'] = $usuario['correo'];
    $_SESSION['id_perfil'] = $usuario['id_perfil'];

    // Redirigir según el tipo de perfil
    // Perfil 1 = ADMIN
    if ((int)$usuario['id_perfil'] === 1) {
        header("Location: admin/admin-panel.php");
        exit;
    }
    // Perfil 2 = CLIENTE
    else {
        header("Location: socios/area-socios.php");
        exit;
    }
} catch (PDOException $e) {
    // Mostrar error si falla la consulta
    die("Error en el inicio de sesión: " . $e->getMessage());
}
