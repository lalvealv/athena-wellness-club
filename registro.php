<?php
require_once 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Acceso no permitido.");
}

$alias = trim($_POST['alias'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$apellidos = trim($_POST['apellidos'] ?? '');
$fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
$dni = strtoupper(trim($_POST['dni'] ?? ''));
$telefono = trim($_POST['telefono'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';
$password2 = $_POST['password2'] ?? '';
$sexo = trim($_POST['sexo'] ?? '');

$calle = trim($_POST['calle'] ?? '');
$portal = trim($_POST['portal'] ?? '');
$piso = trim($_POST['piso'] ?? '');
$cp = trim($_POST['cp'] ?? '');
$ciudad = trim($_POST['ciudad'] ?? '');
$pais = trim($_POST['pais'] ?? '');

$tarifa = trim($_POST['tarifa'] ?? '');
$terminos = isset($_POST['terminos']) ? 1 : 0;

/* VALIDACIONES BÁSICAS */

if (
    empty($alias) || empty($nombre) || empty($apellidos) || empty($fecha_nacimiento) ||
    empty($dni) || empty($telefono) || empty($correo) || empty($password) ||
    empty($password2) || empty($sexo) || empty($calle) || empty($portal) ||
    empty($piso) || empty($cp) || empty($ciudad) || empty($pais) || empty($tarifa)
) {
    die("Todos los campos obligatorios deben estar completos.");
}

if ($terminos !== 1) {
    die("Debes aceptar los términos y condiciones.");
}

if ($password !== $password2) {
    die("Las contraseñas no coinciden.");
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    die("El correo electrónico no es válido.");
}

if (!preg_match('/^[0-9]{8}[A-Z]$/', $dni)) {
    die("El DNI debe tener 8 números y una letra.");
}

if (!preg_match('/^[6-9][0-9]{8}$/', $telefono)) {
    die("El teléfono debe tener 9 dígitos y empezar por 6, 7, 8 o 9.");
}

if (!preg_match('/^[0-9]{5}$/', $cp)) {
    die("El código postal debe tener 5 dígitos.");
}

/* INICIAR TRANSACCIÓN */

try {
    $conn->beginTransaction();

    /* COMPROBAR DUPLICADOS */

    $sqlComprobar = "SELECT COUNT(*) FROM usuario 
                     WHERE alias = :alias OR correo = :correo OR dni = :dni";
    $stmtComprobar = $conn->prepare($sqlComprobar);
    $stmtComprobar->execute([
        ':alias' => $alias,
        ':correo' => $correo,
        ':dni' => $dni
    ]);

    if ($stmtComprobar->fetchColumn() > 0) {
        $conn->rollBack();
        die("Ya existe un usuario con ese alias, correo o DNI.");
    }

    /* INSERTAR DIRECCIÓN */

    $sqlDireccion = "INSERT INTO direccion (calle, portal, piso, cp, ciudad, pais)
                     VALUES (:calle, :portal, :piso, :cp, :ciudad, :pais)";
    $stmtDireccion = $conn->prepare($sqlDireccion);
    $stmtDireccion->execute([
        ':calle' => $calle,
        ':portal' => $portal,
        ':piso' => $piso,
        ':cp' => $cp,
        ':ciudad' => $ciudad,
        ':pais' => $pais
    ]);

    $idDireccion = $conn->lastInsertId();

    /* OBTENER PERFIL CLIENTE */

    $sqlPerfil = "SELECT id_perfil FROM perfil WHERE nombre_perfil = 'CLIENTE' LIMIT 1";
    $stmtPerfil = $conn->query($sqlPerfil);
    $idPerfil = $stmtPerfil->fetchColumn();

    if (!$idPerfil) {
        $conn->rollBack();
        die("No se encontró el perfil CLIENTE en la base de datos.");
    }

    /* CIFRAR CONTRASEÑA */

    $contrasenaHash = password_hash($password, PASSWORD_DEFAULT);

    /* INSERTAR USUARIO */

    $sqlUsuario = "INSERT INTO usuario
        (alias, nombre, apellidos, fecha_nacimiento, dni, telefono, correo, contrasena, sexo, id_direccion, id_perfil)
        VALUES
        (:alias, :nombre, :apellidos, :fecha_nacimiento, :dni, :telefono, :correo, :contrasena, :sexo, :id_direccion, :id_perfil)";
    $stmtUsuario = $conn->prepare($sqlUsuario);
    $stmtUsuario->execute([
        ':alias' => $alias,
        ':nombre' => $nombre,
        ':apellidos' => $apellidos,
        ':fecha_nacimiento' => $fecha_nacimiento,
        ':dni' => $dni,
        ':telefono' => $telefono,
        ':correo' => $correo,
        ':contrasena' => $contrasenaHash,
        ':sexo' => $sexo,
        ':id_direccion' => $idDireccion,
        ':id_perfil' => $idPerfil
    ]);

    $idUsuario = $conn->lastInsertId();

    /* OBTENER MEMBRESÍA */

    $sqlMembresia = "SELECT id_membresia FROM membresia WHERE nombre = :nombre LIMIT 1";
    $stmtMembresia = $conn->prepare($sqlMembresia);
    $stmtMembresia->execute([
        ':nombre' => $tarifa
    ]);

    $idMembresia = $stmtMembresia->fetchColumn();

    if (!$idMembresia) {
        $conn->rollBack();
        die("No se encontró la membresía seleccionada.");
    }

    /* CREAR SUSCRIPCIÓN*/

    $fechaInicio = date('Y-m-d');
    $fechaRenovacion = date('Y-m-d', strtotime('+1 month'));

    $sqlSuscripcion = "INSERT INTO suscripcion
        (id_usuario, id_membresia, fecha_inicio, fecha_renovacion, fecha_fin, renovacion_automatica, estado)
        VALUES
        (:id_usuario, :id_membresia, :fecha_inicio, :fecha_renovacion, NULL, 1, 'Activa')";
    $stmtSuscripcion = $conn->prepare($sqlSuscripcion);
    $stmtSuscripcion->execute([
        ':id_usuario' => $idUsuario,
        ':id_membresia' => $idMembresia,
        ':fecha_inicio' => $fechaInicio,
        ':fecha_renovacion' => $fechaRenovacion
    ]);

    /*  $conn->commit();

    echo "Registro completado correctamente. Ya puedes iniciar sesión.";*/
    $conn->commit();

    // Redirigir al login con mensaje
    header("Location: publico/socios.html?registro=ok");
    exit;
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    die("Error al registrar el usuario: " . $e->getMessage());
}
