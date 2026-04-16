<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion.php';

function responderJSON(bool $ok, string $mensaje, int $codigo = 200, array $extra = []): void
{
    http_response_code($codigo);
    echo json_encode(array_merge([
        'ok' => $ok,
        'mensaje' => $mensaje
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['id_usuario'])) {
    responderJSON(false, 'Sesión no válida.', 401);
}

if (!isset($_SESSION['id_perfil']) || (int)$_SESSION['id_perfil'] !== 1) {
    responderJSON(false, 'Acceso no autorizado.', 403);
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $idAdmin = (int)$_SESSION['id_usuario'];

        $sqlAdmin = "SELECT nombre, apellidos, foto_perfil
                     FROM usuario
                     WHERE id_usuario = :id_usuario
                     LIMIT 1";

        $stmtAdmin = $conn->prepare($sqlAdmin);
        $stmtAdmin->execute([
            ':id_usuario' => $idAdmin
        ]);
        $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            responderJSON(false, 'No se encontró el administrador logueado.', 404);
        }

        $fotoAdmin = !empty($admin['foto_perfil']) ? $admin['foto_perfil'] : '../img/athena_logo.png';
        $nombreAdmin = trim(($admin['nombre'] ?? '') . ' ' . ($admin['apellidos'] ?? ''));

        responderJSON(true, 'Datos cargados correctamente.', 200, [
            'admin' => [
                'foto_perfil' => $fotoAdmin,
                'nombre_completo' => $nombreAdmin !== '' ? $nombreAdmin : 'Administrador ATHENA',
                'perfil' => 'Perfil ADMIN'
            ]
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderJSON(false, 'Método no permitido.', 405);
    }

    $alias = trim($_POST['alias'] ?? '');
    $dni = strtoupper(trim($_POST['dni'] ?? ''));
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $fechaNacimiento = trim($_POST['fechaNacimiento'] ?? '');
    $sexo = trim($_POST['sexo'] ?? '');

    $calle = trim($_POST['calle'] ?? '');
    $portal = trim($_POST['portal'] ?? '');
    $piso = trim($_POST['piso'] ?? '');
    $cp = trim($_POST['cp'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $pais = trim($_POST['pais'] ?? '');

    $perfil = trim($_POST['perfil'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $confirmarContrasena = $_POST['confirmarContrasena'] ?? '';
    $fotoPerfil = trim($_POST['fotoPerfil'] ?? '');

    $membresia = trim($_POST['membresia'] ?? '');
    $renovacionAutomatica = trim($_POST['renovacionAutomatica'] ?? 'Si');

    if (
        $alias === '' || $dni === '' || $nombre === '' || $apellidos === '' || $correo === '' ||
        $telefono === '' || $fechaNacimiento === '' || $sexo === '' || $perfil === '' ||
        $estado === '' || $contrasena === '' || $confirmarContrasena === '' ||
        $calle === '' || $cp === '' || $ciudad === '' || $pais === ''
    ) {
        responderJSON(false, 'Debes completar todos los campos obligatorios.');
    }

    if ($contrasena !== $confirmarContrasena) {
        responderJSON(false, 'Las contraseñas no coinciden.');
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        responderJSON(false, 'El correo electrónico no es válido.');
    }

    if (!preg_match('/^[0-9]{8}[A-Z]$/', $dni)) {
        responderJSON(false, 'El DNI debe tener 8 números y una letra.');
    }

    if (!preg_match('/^[6-9][0-9]{8}$/', $telefono)) {
        responderJSON(false, 'El teléfono debe tener 9 dígitos y empezar por 6, 7, 8 o 9.');
    }

    if (!preg_match('/^[0-9]{5}$/', $cp)) {
        responderJSON(false, 'El código postal debe tener 5 dígitos.');
    }

    $conn->beginTransaction();

    $sqlDuplicados = "SELECT COUNT(*)
                      FROM usuario
                      WHERE alias = :alias OR correo = :correo OR dni = :dni";

    $stmtDuplicados = $conn->prepare($sqlDuplicados);
    $stmtDuplicados->execute([
        ':alias' => $alias,
        ':correo' => $correo,
        ':dni' => $dni
    ]);

    if ((int)$stmtDuplicados->fetchColumn() > 0) {
        $conn->rollBack();
        responderJSON(false, 'Ya existe un usuario con ese alias, correo o DNI.');
    }

    $sqlDireccion = "INSERT INTO direccion (calle, portal, piso, cp, ciudad, pais)
                     VALUES (:calle, :portal, :piso, :cp, :ciudad, :pais)";

    $stmtDireccion = $conn->prepare($sqlDireccion);
    $stmtDireccion->execute([
        ':calle' => $calle,
        ':portal' => $portal !== '' ? $portal : null,
        ':piso' => $piso !== '' ? $piso : null,
        ':cp' => $cp,
        ':ciudad' => $ciudad,
        ':pais' => $pais
    ]);

    $idDireccion = (int)$conn->lastInsertId();

    $nombrePerfilBD = strtoupper($perfil);

    $sqlPerfil = "SELECT id_perfil
                  FROM perfil
                  WHERE nombre_perfil = :perfil
                  LIMIT 1";

    $stmtPerfil = $conn->prepare($sqlPerfil);
    $stmtPerfil->execute([
        ':perfil' => $nombrePerfilBD
    ]);
    $idPerfil = $stmtPerfil->fetchColumn();

    if (!$idPerfil) {
        $conn->rollBack();
        responderJSON(false, 'No se encontró el perfil indicado.');
    }

    $hash = password_hash($contrasena, PASSWORD_DEFAULT);

    $sqlUsuario = "INSERT INTO usuario
        (alias, nombre, apellidos, fecha_nacimiento, dni, telefono, correo, contrasena, sexo, estado, foto_perfil, id_direccion, id_perfil)
        VALUES
        (:alias, :nombre, :apellidos, :fecha_nacimiento, :dni, :telefono, :correo, :contrasena, :sexo, :estado, :foto_perfil, :id_direccion, :id_perfil)";

    $stmtUsuario = $conn->prepare($sqlUsuario);
    $stmtUsuario->execute([
        ':alias' => $alias,
        ':nombre' => $nombre,
        ':apellidos' => $apellidos,
        ':fecha_nacimiento' => $fechaNacimiento,
        ':dni' => $dni,
        ':telefono' => $telefono,
        ':correo' => $correo,
        ':contrasena' => $hash,
        ':sexo' => $sexo,
        ':estado' => $estado,
        ':foto_perfil' => $fotoPerfil !== '' ? $fotoPerfil : null,
        ':id_direccion' => $idDireccion,
        ':id_perfil' => $idPerfil
    ]);

    $idUsuario = (int)$conn->lastInsertId();

    if ($membresia !== '') {
        $sqlMembresia = "SELECT id_membresia
                         FROM membresia
                         WHERE nombre = :nombre
                         LIMIT 1";

        $stmtMembresia = $conn->prepare($sqlMembresia);
        $stmtMembresia->execute([
            ':nombre' => $membresia
        ]);
        $idMembresia = $stmtMembresia->fetchColumn();

        if (!$idMembresia) {
            $conn->rollBack();
            responderJSON(false, 'No se encontró la membresía seleccionada.');
        }

        $fechaInicio = date('Y-m-d');
        $fechaRenovacion = date('Y-m-d', strtotime('+1 month'));
        $renovacion = $renovacionAutomatica === 'Si' ? 1 : 0;

        $sqlSuscripcion = "INSERT INTO suscripcion
            (id_usuario, id_membresia, fecha_inicio, fecha_renovacion, fecha_fin, renovacion_automatica, estado)
            VALUES
            (:id_usuario, :id_membresia, :fecha_inicio, :fecha_renovacion, NULL, :renovacion_automatica, 'Activa')";

        $stmtSuscripcion = $conn->prepare($sqlSuscripcion);
        $stmtSuscripcion->execute([
            ':id_usuario' => $idUsuario,
            ':id_membresia' => $idMembresia,
            ':fecha_inicio' => $fechaInicio,
            ':fecha_renovacion' => $fechaRenovacion,
            ':renovacion_automatica' => $renovacion
        ]);
    }

    $conn->commit();

    responderJSON(true, 'Usuario creado correctamente.');
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    responderJSON(false, 'Error al crear el usuario.', 500, [
        'error' => $e->getMessage()
    ]);
}
