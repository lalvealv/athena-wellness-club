<?php
// Inicia la sesión para poder comprobar el usuario logueado
session_start();

// Indica que la respuesta será JSON
header('Content-Type: application/json; charset=utf-8');

// Importa la conexión a la base de datos
require_once __DIR__ . '/../conexion.php';

// ID del administrador original del sistema
define('ADMIN_ORIGINAL_ID', 1);

// Función para responder siempre en formato JSON
function responderJSON(bool $ok, string $mensaje, int $codigo = 200, array $extra = []): void
{
    http_response_code($codigo);

    echo json_encode(array_merge([
        'ok' => $ok,
        'mensaje' => $mensaje
    ], $extra), JSON_UNESCAPED_UNICODE);

    exit;
}

// Comprueba si un usuario es el administrador original
function esAdminOriginal(int $idUsuario): bool
{
    return $idUsuario === ADMIN_ORIGINAL_ID;
}

// Comprueba si el administrador logueado es el administrador original
function adminLogueadoEsOriginal(int $idAdmin): bool
{
    return $idAdmin === ADMIN_ORIGINAL_ID;
}

// Si no hay sesión iniciada, se bloquea el acceso
if (!isset($_SESSION['id_usuario'])) {
    responderJSON(false, 'Sesión no válida.', 401);
}

// Si el usuario logueado no tiene perfil ADMIN, se bloquea el acceso
if (!isset($_SESSION['id_perfil']) || (int)$_SESSION['id_perfil'] !== 1) {
    responderJSON(false, 'Acceso no autorizado.', 403);
}

try {
    // PETICIÓN GET: carga los datos del usuario que se quiere editar
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $idAdmin = (int)$_SESSION['id_usuario'];
        $idUsuario = (int)($_GET['id'] ?? 0);

        // Valida que el ID recibido sea correcto
        if ($idUsuario <= 0) {
            responderJSON(false, 'ID de usuario no válido.', 400);
        }

        // Consulta los datos del administrador logueado
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

        // Consulta todos los datos del usuario a editar
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
                            ON u.id_usuario = s.id_usuario AND s.estado IN ('Activa', 'Pausada', 'Cancelada')
                        LEFT JOIN membresia m
                            ON s.id_membresia = m.id_membresia
                        WHERE u.id_usuario = :id_usuario
                        LIMIT 1";

        $stmtUsuario = $conn->prepare($sqlUsuario);
        $stmtUsuario->execute([
            ':id_usuario' => $idUsuario
        ]);
        $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

        // Si no existe el usuario, se devuelve error
        if (!$usuario) {
            responderJSON(false, 'No se encontró el usuario indicado.', 404);
        }

        // Prepara los datos del administrador
        $fotoAdmin = !empty($admin['foto_perfil']) ? $admin['foto_perfil'] : '../img/athena_logo.png';
        $nombreAdmin = trim(($admin['nombre'] ?? '') . ' ' . ($admin['apellidos'] ?? ''));

        // Devuelve al JavaScript los datos del admin y del usuario
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
                    'estado' => $usuario['estado_suscripcion'] ?? 'Activa',
                    'fecha_renovacion' => !empty($usuario['fecha_renovacion'])
                        ? date('d/m/Y', strtotime($usuario['fecha_renovacion']))
                        : 'No disponible',
                    'renovacion_automatica' => isset($usuario['renovacion_automatica']) && (int)$usuario['renovacion_automatica'] === 1 ? 'Si' : 'No'
                ]
            ]
        ]);
    }

    // Si no es POST, se rechaza la petición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderJSON(false, 'Método no permitido.', 405);
    }

    // ID del administrador logueado
    $idAdmin = (int)$_SESSION['id_usuario'];

    // ID del usuario que se va a actualizar
    $idUsuario = (int)($_POST['idUsuario'] ?? 0);

    // Recoge los datos personales
    $alias = trim($_POST['alias'] ?? '');
    $dni = strtoupper(trim($_POST['dni'] ?? ''));
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $fechaNacimiento = trim($_POST['fechaNacimiento'] ?? '');
    $sexo = trim($_POST['sexo'] ?? '');

    // Recoge los datos de dirección
    $calle = trim($_POST['calle'] ?? '');
    $portal = trim($_POST['portal'] ?? '');
    $piso = trim($_POST['piso'] ?? '');
    $cp = trim($_POST['cp'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $pais = trim($_POST['pais'] ?? '');

    // Recoge permisos y estado
    $perfil = strtoupper(trim($_POST['perfil'] ?? ''));
    $estado = trim($_POST['estado'] ?? '');
    $fotoPerfil = trim($_POST['fotoPerfil'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    // Recoge datos de suscripción
    $membresia = trim($_POST['membresia'] ?? '');
    $estadoSuscripcion = trim($_POST['estadoSuscripcion'] ?? 'Activa');
    $renovacionAutomatica = trim($_POST['renovacionAutomatica'] ?? 'Si');

    // Valida ID
    if ($idUsuario <= 0) {
        responderJSON(false, 'ID de usuario no válido.');
    }

    // Valida campos obligatorios
    if (
        $alias === '' || $dni === '' || $nombre === '' || $apellidos === '' || $correo === '' ||
        $telefono === '' || $calle === '' || $cp === '' || $ciudad === '' || $pais === '' ||
        $perfil === '' || $estado === ''
    ) {
        responderJSON(false, 'Debes completar todos los campos obligatorios.');
    }

    // Valida correo
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        responderJSON(false, 'El correo electrónico no es válido.');
    }

    // Valida DNI
    if (!preg_match('/^[0-9]{8}[A-Z]$/', $dni)) {
        responderJSON(false, 'El DNI debe tener 8 números y una letra.');
    }

    // Valida teléfono
    if (!preg_match('/^[6-9][0-9]{8}$/', $telefono)) {
        responderJSON(false, 'El teléfono debe tener 9 dígitos y empezar por 6, 7, 8 o 9.');
    }

    // Valida código postal
    if (!preg_match('/^[0-9]{5}$/', $cp)) {
        responderJSON(false, 'El código postal debe tener 5 dígitos.');
    }

    // Inicia transacción para actualizar todo de forma segura
    $conn->beginTransaction();

    // Busca el usuario actual y su dirección
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

    // Protege al administrador original para que no pierda permisos
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

    // Solo el administrador original puede convertir a otros usuarios en ADMIN
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

    // Comprueba que alias, correo y DNI no estén repetidos en otro usuario
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

    // Busca el ID del perfil seleccionado
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

    // Obtiene el ID de dirección actual
    $idDireccion = $usuarioActual['id_direccion'];

    // Si el usuario ya tiene dirección, se actualiza
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
        // Si no tiene dirección, se crea una nueva
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

    // Si se escribe nueva contraseña, se actualiza también
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
        // Si no se escribe contraseña, se actualizan los datos sin modificarla
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

    // Actualiza o crea la suscripción del usuario
    if ($membresia !== '' || $estadoSuscripcion !== '' || $renovacionAutomatica !== '') {

        // Busca la suscripción más reciente del usuario
        $sqlSuscripcionActiva = "SELECT id_suscripcion
                                 FROM suscripcion
                                 WHERE id_usuario = :id_usuario
                                   AND estado IN ('Activa', 'Pausada', 'Cancelada')
                                 ORDER BY id_suscripcion DESC
                                 LIMIT 1";
        $stmtSuscripcionActiva = $conn->prepare($sqlSuscripcionActiva);
        $stmtSuscripcionActiva->execute([
            ':id_usuario' => $idUsuario
        ]);
        $idSuscripcion = $stmtSuscripcionActiva->fetchColumn();

        $idMembresia = null;

        // Si se ha elegido una membresía nueva, se busca su ID
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
        }

        // Convierte renovación automática a 1 o 0
        $renovacion = $renovacionAutomatica === 'Si' ? 1 : 0;

        // Si la suscripción se cancela, se desactiva la renovación automática
        if ($estadoSuscripcion === 'Cancelada') {
            $renovacion = 0;
        }

        // Nueva fecha de renovación
        $fechaRenovacion = date('Y-m-d', strtotime('+1 month'));

        if ($idSuscripcion) {
            // Prepara campos dinámicos para actualizar solo lo necesario
            $campos = [];
            $params = [':id_suscripcion' => $idSuscripcion];

            if ($idMembresia) {
                $campos[] = "id_membresia = :id_membresia";
                $params[':id_membresia'] = $idMembresia;
            }

            $campos[] = "estado = :estado";
            $params[':estado'] = $estadoSuscripcion;

            $campos[] = "renovacion_automatica = :renovacion_automatica";
            $params[':renovacion_automatica'] = $renovacion;

            if ($estadoSuscripcion === 'Finalizada') {
                $campos[] = "fecha_fin = CURDATE()";
            } else {
                $campos[] = "fecha_renovacion = :fecha_renovacion";
                $params[':fecha_renovacion'] = $fechaRenovacion;
            }

            // Actualiza la suscripción existente
            $sqlActualizarSuscripcion = "UPDATE suscripcion
                                         SET " . implode(', ', $campos) . "
                                         WHERE id_suscripcion = :id_suscripcion";

            $stmtActualizarSuscripcion = $conn->prepare($sqlActualizarSuscripcion);
            $stmtActualizarSuscripcion->execute($params);
        } else {
            // Si no existe suscripción, se crea una nueva
            if (!$idMembresia) {
                $conn->rollBack();
                responderJSON(false, 'Para crear una nueva suscripción debes indicar una membresía.');
            }

            $sqlInsertarSuscripcion = "INSERT INTO suscripcion
                (id_usuario, id_membresia, fecha_inicio, fecha_renovacion, fecha_fin, renovacion_automatica, estado)
                VALUES
                (:id_usuario, :id_membresia, CURDATE(), :fecha_renovacion, NULL, :renovacion_automatica, :estado)";

            $stmtInsertarSuscripcion = $conn->prepare($sqlInsertarSuscripcion);
            $stmtInsertarSuscripcion->execute([
                ':id_usuario' => $idUsuario,
                ':id_membresia' => $idMembresia,
                ':fecha_renovacion' => $fechaRenovacion,
                ':renovacion_automatica' => $renovacion,
                ':estado' => $estadoSuscripcion
            ]);
        }
    }

    // Confirma todos los cambios
    $conn->commit();

    // Respuesta correcta
    responderJSON(true, 'Usuario actualizado correctamente.');
} catch (PDOException $e) {
    // Si hay error, deshace la transacción
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Devuelve error en JSON
    responderJSON(false, 'Error al actualizar el usuario.', 500, [
        'error' => $e->getMessage()
    ]);
}
