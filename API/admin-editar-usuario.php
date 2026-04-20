<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion.php';

define('ADMIN_ORIGINAL_ID', 1);

function responderJSON(bool $ok, string $mensaje, int $codigo = 200, array $extra = []): void
{
    http_response_code($codigo);
    echo json_encode(array_merge([
        'ok' => $ok,
        'mensaje' => $mensaje
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function esAdminOriginal(int $idUsuario): bool
{
    return $idUsuario === ADMIN_ORIGINAL_ID;
}

function adminLogueadoEsOriginal(int $idAdmin): bool
{
    return $idAdmin === ADMIN_ORIGINAL_ID;
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
        $idUsuario = (int)($_GET['id'] ?? 0);

        if ($idUsuario <= 0) {
            responderJSON(false, 'ID de usuario no válido.', 400);
        }

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

        $sqlUsuario = "SELECT
                            u.id_usuario,
                            u.alias,
                            u.nombre,
                            u.apellidos,
                            u.fecha_nacimiento,
                            u.dni,
                            u.telefono,
                            u.correo,
                            u.sexo,
                            u.estado,
                            u.foto_perfil,
                            u.id_perfil,
                            p.nombre_perfil,
                            d.calle,
                            d.portal,
                            d.piso,
                            d.cp,
                            d.ciudad,
                            d.pais,
                            s.id_suscripcion,
                            s.renovacion_automatica,
                            s.fecha_renovacion,
                            s.estado AS estado_suscripcion,
                            m.nombre AS membresia
                        FROM usuario u
                        INNER JOIN perfil p
                            ON u.id_perfil = p.id_perfil
                        LEFT JOIN direccion d
                            ON u.id_direccion = d.id_direccion
                        LEFT JOIN suscripcion s
                            ON u.id_usuario = s.id_usuario AND s.estado IN ('Activa', 'Pausada')
                        LEFT JOIN membresia m
                            ON s.id_membresia = m.id_membresia
                        WHERE u.id_usuario = :id_usuario
                        LIMIT 1";

        $stmtUsuario = $conn->prepare($sqlUsuario);
        $stmtUsuario->execute([
            ':id_usuario' => $idUsuario
        ]);
        $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            responderJSON(false, 'No se encontró el usuario indicado.', 404);
        }

        $fotoAdmin = !empty($admin['foto_perfil']) ? $admin['foto_perfil'] : '../img/athena_logo.png';
        $nombreAdmin = trim(($admin['nombre'] ?? '') . ' ' . ($admin['apellidos'] ?? ''));

        responderJSON(true, 'Datos cargados correctamente.', 200, [
            'admin' => [
                'foto_perfil' => $fotoAdmin,
                'nombre_completo' => $nombreAdmin !== '' ? $nombreAdmin : 'Administrador ATHENA',
                'perfil' => 'Perfil ADMIN'
            ],
            'admin_logueado_es_original' => adminLogueadoEsOriginal($idAdmin),
            'usuario' => [
                'id_usuario' => (int)$usuario['id_usuario'],
                'alias' => $usuario['alias'] ?? '',
                'nombre' => $usuario['nombre'] ?? '',
                'apellidos' => $usuario['apellidos'] ?? '',
                'fecha_nacimiento' => $usuario['fecha_nacimiento'] ?? '',
                'dni' => $usuario['dni'] ?? '',
                'telefono' => $usuario['telefono'] ?? '',
                'correo' => $usuario['correo'] ?? '',
                'sexo' => $usuario['sexo'] ?? '',
                'estado' => $usuario['estado'] ?? '',
                'foto_perfil' => $usuario['foto_perfil'] ?? '../img/athena_logo.png',
                'perfil' => strtoupper($usuario['nombre_perfil'] ?? ''),
                'es_admin_original' => esAdminOriginal((int)$usuario['id_usuario']),
                'direccion' => [
                    'calle' => $usuario['calle'] ?? '',
                    'portal' => $usuario['portal'] ?? '',
                    'piso' => $usuario['piso'] ?? '',
                    'cp' => $usuario['cp'] ?? '',
                    'ciudad' => $usuario['ciudad'] ?? '',
                    'pais' => $usuario['pais'] ?? ''
                ],
                'suscripcion' => [
                    'membresia' => $usuario['membresia'] ?? '',
                    'fecha_renovacion' => !empty($usuario['fecha_renovacion'])
                        ? date('d/m/Y', strtotime($usuario['fecha_renovacion']))
                        : 'No disponible',
                    'renovacion_automatica' => isset($usuario['renovacion_automatica']) && (int)$usuario['renovacion_automatica'] === 1 ? 'Si' : 'No'
                ]
            ]
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderJSON(false, 'Método no permitido.', 405);
    }

    $idAdmin = (int)$_SESSION['id_usuario'];

    $idUsuario = (int)($_POST['idUsuario'] ?? 0);

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

    $perfil = strtoupper(trim($_POST['perfil'] ?? ''));
    $estado = trim($_POST['estado'] ?? '');
    $fotoPerfil = trim($_POST['fotoPerfil'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    $membresia = trim($_POST['membresia'] ?? '');
    $renovacionAutomatica = trim($_POST['renovacionAutomatica'] ?? 'Si');

    if ($idUsuario <= 0) {
        responderJSON(false, 'ID de usuario no válido.');
    }

    if (
        $alias === '' || $dni === '' || $nombre === '' || $apellidos === '' || $correo === '' ||
        $telefono === '' || $calle === '' || $cp === '' || $ciudad === '' || $pais === '' ||
        $perfil === '' || $estado === ''
    ) {
        responderJSON(false, 'Debes completar todos los campos obligatorios.');
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

    $sqlUsuarioActual = "SELECT id_direccion
                         FROM usuario
                         WHERE id_usuario = :id_usuario
                         LIMIT 1";
    $stmtUsuarioActual = $conn->prepare($sqlUsuarioActual);
    $stmtUsuarioActual->execute([
        ':id_usuario' => $idUsuario
    ]);
    $usuarioActual = $stmtUsuarioActual->fetch(PDO::FETCH_ASSOC);

    if (!$usuarioActual) {
        $conn->rollBack();
        responderJSON(false, 'No se encontró el usuario.');
    }

    // REGLAS DE PROTECCIÓN
    if (esAdminOriginal($idUsuario)) {
        if ($perfil !== 'ADMIN') {
            $conn->rollBack();
            responderJSON(false, 'El administrador original siempre debe seguir siendo ADMIN.');
        }

        if ($estado !== 'Activo') {
            $conn->rollBack();
            responderJSON(false, 'El administrador original siempre debe permanecer Activo.');
        }
    }

    if ($perfil === 'ADMIN' && !adminLogueadoEsOriginal($idAdmin)) {
        $sqlPerfilActual = "SELECT p.nombre_perfil
                            FROM usuario u
                            INNER JOIN perfil p ON u.id_perfil = p.id_perfil
                            WHERE u.id_usuario = :id_usuario
                            LIMIT 1";
        $stmtPerfilActual = $conn->prepare($sqlPerfilActual);
        $stmtPerfilActual->execute([
            ':id_usuario' => $idUsuario
        ]);
        $perfilActual = strtoupper((string)$stmtPerfilActual->fetchColumn());

        if ($perfilActual !== 'ADMIN') {
            $conn->rollBack();
            responderJSON(false, 'Solo el administrador original puede asignar el perfil ADMIN.');
        }
    }

    $sqlDuplicados = "SELECT COUNT(*)
                      FROM usuario
                      WHERE (alias = :alias OR correo = :correo OR dni = :dni)
                        AND id_usuario <> :id_usuario";

    $stmtDuplicados = $conn->prepare($sqlDuplicados);
    $stmtDuplicados->execute([
        ':alias' => $alias,
        ':correo' => $correo,
        ':dni' => $dni,
        ':id_usuario' => $idUsuario
    ]);

    if ((int)$stmtDuplicados->fetchColumn() > 0) {
        $conn->rollBack();
        responderJSON(false, 'Ya existe otro usuario con ese alias, correo o DNI.');
    }

    $sqlPerfil = "SELECT id_perfil
                  FROM perfil
                  WHERE nombre_perfil = :perfil
                  LIMIT 1";
    $stmtPerfil = $conn->prepare($sqlPerfil);
    $stmtPerfil->execute([
        ':perfil' => $perfil
    ]);
    $idPerfil = $stmtPerfil->fetchColumn();

    if (!$idPerfil) {
        $conn->rollBack();
        responderJSON(false, 'No se encontró el perfil indicado.');
    }

    $idDireccion = $usuarioActual['id_direccion'];

    if ($idDireccion) {
        $sqlActualizarDireccion = "UPDATE direccion
                                   SET calle = :calle,
                                       portal = :portal,
                                       piso = :piso,
                                       cp = :cp,
                                       ciudad = :ciudad,
                                       pais = :pais
                                   WHERE id_direccion = :id_direccion";

        $stmtActualizarDireccion = $conn->prepare($sqlActualizarDireccion);
        $stmtActualizarDireccion->execute([
            ':calle' => $calle,
            ':portal' => $portal !== '' ? $portal : null,
            ':piso' => $piso !== '' ? $piso : null,
            ':cp' => $cp,
            ':ciudad' => $ciudad,
            ':pais' => $pais,
            ':id_direccion' => $idDireccion
        ]);
    } else {
        $sqlInsertDireccion = "INSERT INTO direccion (calle, portal, piso, cp, ciudad, pais)
                               VALUES (:calle, :portal, :piso, :cp, :ciudad, :pais)";
        $stmtInsertDireccion = $conn->prepare($sqlInsertDireccion);
        $stmtInsertDireccion->execute([
            ':calle' => $calle,
            ':portal' => $portal !== '' ? $portal : null,
            ':piso' => $piso !== '' ? $piso : null,
            ':cp' => $cp,
            ':ciudad' => $ciudad,
            ':pais' => $pais
        ]);
        $idDireccion = (int)$conn->lastInsertId();
    }

    if ($contrasena !== '') {
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);

        $sqlActualizarUsuario = "UPDATE usuario
                                 SET alias = :alias,
                                     nombre = :nombre,
                                     apellidos = :apellidos,
                                     fecha_nacimiento = :fecha_nacimiento,
                                     dni = :dni,
                                     telefono = :telefono,
                                     correo = :correo,
                                     contrasena = :contrasena,
                                     sexo = :sexo,
                                     estado = :estado,
                                     foto_perfil = :foto_perfil,
                                     id_direccion = :id_direccion,
                                     id_perfil = :id_perfil
                                 WHERE id_usuario = :id_usuario";

        $stmtActualizarUsuario = $conn->prepare($sqlActualizarUsuario);
        $stmtActualizarUsuario->execute([
            ':alias' => $alias,
            ':nombre' => $nombre,
            ':apellidos' => $apellidos,
            ':fecha_nacimiento' => $fechaNacimiento !== '' ? $fechaNacimiento : null,
            ':dni' => $dni,
            ':telefono' => $telefono,
            ':correo' => $correo,
            ':contrasena' => $hash,
            ':sexo' => $sexo !== '' ? $sexo : null,
            ':estado' => $estado,
            ':foto_perfil' => $fotoPerfil !== '' ? $fotoPerfil : null,
            ':id_direccion' => $idDireccion,
            ':id_perfil' => $idPerfil,
            ':id_usuario' => $idUsuario
        ]);
    } else {
        $sqlActualizarUsuario = "UPDATE usuario
                                 SET alias = :alias,
                                     nombre = :nombre,
                                     apellidos = :apellidos,
                                     fecha_nacimiento = :fecha_nacimiento,
                                     dni = :dni,
                                     telefono = :telefono,
                                     correo = :correo,
                                     sexo = :sexo,
                                     estado = :estado,
                                     foto_perfil = :foto_perfil,
                                     id_direccion = :id_direccion,
                                     id_perfil = :id_perfil
                                 WHERE id_usuario = :id_usuario";

        $stmtActualizarUsuario = $conn->prepare($sqlActualizarUsuario);
        $stmtActualizarUsuario->execute([
            ':alias' => $alias,
            ':nombre' => $nombre,
            ':apellidos' => $apellidos,
            ':fecha_nacimiento' => $fechaNacimiento !== '' ? $fechaNacimiento : null,
            ':dni' => $dni,
            ':telefono' => $telefono,
            ':correo' => $correo,
            ':sexo' => $sexo !== '' ? $sexo : null,
            ':estado' => $estado,
            ':foto_perfil' => $fotoPerfil !== '' ? $fotoPerfil : null,
            ':id_direccion' => $idDireccion,
            ':id_perfil' => $idPerfil,
            ':id_usuario' => $idUsuario
        ]);
    }

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

        $sqlSuscripcionActiva = "SELECT id_suscripcion
                                 FROM suscripcion
                                 WHERE id_usuario = :id_usuario
                                   AND estado IN ('Activa', 'Pausada')
                                 ORDER BY id_suscripcion DESC
                                 LIMIT 1";
        $stmtSuscripcionActiva = $conn->prepare($sqlSuscripcionActiva);
        $stmtSuscripcionActiva->execute([
            ':id_usuario' => $idUsuario
        ]);
        $idSuscripcion = $stmtSuscripcionActiva->fetchColumn();

        $renovacion = $renovacionAutomatica === 'Si' ? 1 : 0;
        $fechaRenovacion = date('Y-m-d', strtotime('+1 month'));

        if ($idSuscripcion) {
            $sqlActualizarSuscripcion = "UPDATE suscripcion
                                         SET id_membresia = :id_membresia,
                                             renovacion_automatica = :renovacion_automatica,
                                             fecha_renovacion = :fecha_renovacion
                                         WHERE id_suscripcion = :id_suscripcion";

            $stmtActualizarSuscripcion = $conn->prepare($sqlActualizarSuscripcion);
            $stmtActualizarSuscripcion->execute([
                ':id_membresia' => $idMembresia,
                ':renovacion_automatica' => $renovacion,
                ':fecha_renovacion' => $fechaRenovacion,
                ':id_suscripcion' => $idSuscripcion
            ]);
        } else {
            $sqlInsertarSuscripcion = "INSERT INTO suscripcion
                (id_usuario, id_membresia, fecha_inicio, fecha_renovacion, fecha_fin, renovacion_automatica, estado)
                VALUES
                (:id_usuario, :id_membresia, CURDATE(), :fecha_renovacion, NULL, :renovacion_automatica, 'Activa')";

            $stmtInsertarSuscripcion = $conn->prepare($sqlInsertarSuscripcion);
            $stmtInsertarSuscripcion->execute([
                ':id_usuario' => $idUsuario,
                ':id_membresia' => $idMembresia,
                ':fecha_renovacion' => $fechaRenovacion,
                ':renovacion_automatica' => $renovacion
            ]);
        }
    }

    $conn->commit();

    responderJSON(true, 'Usuario actualizado correctamente.');
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    responderJSON(false, 'Error al actualizar el usuario.', 500, [
        'error' => $e->getMessage()
    ]);
}
