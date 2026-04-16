<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion.php';

$busqueda = trim($_GET['buscar'] ?? '');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Sesión no válida.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['id_perfil']) || (int)$_SESSION['id_perfil'] !== 1) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Acceso no autorizado.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idAdmin = (int) $_SESSION['id_usuario'];

function responderJSON(array $datos): void
{
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function obtenerAdmin(PDO $conn, int $idAdmin): array
{
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
        return [
            'foto_perfil' => '../img/athena_logo.png',
            'nombre_completo' => 'Administrador ATHENA',
            'perfil' => 'Perfil ADMIN'
        ];
    }

    $fotoAdmin = !empty($admin['foto_perfil']) ? $admin['foto_perfil'] : '../img/athena_logo.png';
    $nombreAdmin = trim(($admin['nombre'] ?? '') . ' ' . ($admin['apellidos'] ?? ''));

    return [
        'foto_perfil' => $fotoAdmin,
        'nombre_completo' => $nombreAdmin !== '' ? $nombreAdmin : 'Administrador ATHENA',
        'perfil' => 'Perfil ADMIN'
    ];
}

function obtenerUsuarios(PDO $conn, string $busqueda): array
{
    $sqlUsuarios = "SELECT 
                        u.id_usuario,
                        u.alias,
                        u.nombre,
                        u.apellidos,
                        u.correo,
                        u.telefono,
                        u.estado,
                        u.id_perfil,
                        p.nombre_perfil
                    FROM usuario u
                    INNER JOIN perfil p
                        ON u.id_perfil = p.id_perfil
                    WHERE (
                        :busqueda = ''
                        OR u.alias LIKE :like_busqueda
                        OR u.nombre LIKE :like_busqueda
                        OR u.apellidos LIKE :like_busqueda
                        OR u.correo LIKE :like_busqueda
                    )
                    ORDER BY u.id_usuario ASC";

    $stmtUsuarios = $conn->prepare($sqlUsuarios);
    $stmtUsuarios->execute([
        ':busqueda' => $busqueda,
        ':like_busqueda' => '%' . $busqueda . '%'
    ]);
    $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

    $listaUsuarios = [];
    foreach ($usuarios as $usuario) {
        $listaUsuarios[] = [
            'id_usuario' => (int)$usuario['id_usuario'],
            'alias' => $usuario['alias'] ?? '',
            'nombre_completo' => trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? '')),
            'correo' => $usuario['correo'] ?? '',
            'telefono' => !empty($usuario['telefono']) ? $usuario['telefono'] : 'No disponible',
            'perfil' => strtoupper($usuario['nombre_perfil'] ?? 'No disponible'),
            'id_perfil' => (int)$usuario['id_perfil'],
            'estado' => $usuario['estado'] ?? 'No disponible'
        ];
    }

    return $listaUsuarios;
}

function actualizarEstadoUsuario(PDO $conn, int $idUsuarioObjetivo, string $nuevoEstado, int $idAdmin): array
{
    $estadosPermitidos = ['Activo', 'Inactivo', 'Bloqueado'];

    if (!in_array($nuevoEstado, $estadosPermitidos, true)) {
        return [
            'ok' => false,
            'mensaje' => 'Estado no válido.'
        ];
    }

    if ($idUsuarioObjetivo === $idAdmin) {
        return [
            'ok' => false,
            'mensaje' => 'No puedes cambiar tu propio estado desde esta pantalla.'
        ];
    }

    try {
        $sqlComprobar = "SELECT id_usuario
                         FROM usuario
                         WHERE id_usuario = :id_usuario
                         LIMIT 1";

        $stmtComprobar = $conn->prepare($sqlComprobar);
        $stmtComprobar->execute([
            ':id_usuario' => $idUsuarioObjetivo
        ]);

        $usuario = $stmtComprobar->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            return [
                'ok' => false,
                'mensaje' => 'El usuario no existe.'
            ];
        }

        $sqlUpdate = "UPDATE usuario
                      SET estado = :estado
                      WHERE id_usuario = :id_usuario";

        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':estado' => $nuevoEstado,
            ':id_usuario' => $idUsuarioObjetivo
        ]);

        return [
            'ok' => true,
            'mensaje' => 'Estado actualizado correctamente.'
        ];
    } catch (PDOException $e) {
        return [
            'ok' => false,
            'mensaje' => 'Error al actualizar el estado: ' . $e->getMessage()
        ];
    }
}

function actualizarPerfilUsuario(PDO $conn, int $idUsuarioObjetivo, string $nuevoPerfil, int $idAdmin): array
{
    $perfilesPermitidos = ['ADMIN', 'CLIENTE'];

    if (!in_array($nuevoPerfil, $perfilesPermitidos, true)) {
        return [
            'ok' => false,
            'mensaje' => 'Perfil no válido.'
        ];
    }

    if ($idUsuarioObjetivo === $idAdmin) {
        return [
            'ok' => false,
            'mensaje' => 'No puedes cambiar tu propio perfil desde esta pantalla.'
        ];
    }

    try {
        $sqlComprobar = "SELECT id_usuario
                         FROM usuario
                         WHERE id_usuario = :id_usuario
                         LIMIT 1";

        $stmtComprobar = $conn->prepare($sqlComprobar);
        $stmtComprobar->execute([
            ':id_usuario' => $idUsuarioObjetivo
        ]);

        $usuario = $stmtComprobar->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            return [
                'ok' => false,
                'mensaje' => 'El usuario no existe.'
            ];
        }

        $sqlPerfil = "SELECT id_perfil
                      FROM perfil
                      WHERE nombre_perfil = :perfil
                      LIMIT 1";

        $stmtPerfil = $conn->prepare($sqlPerfil);
        $stmtPerfil->execute([
            ':perfil' => $nuevoPerfil
        ]);

        $idPerfil = $stmtPerfil->fetchColumn();

        if (!$idPerfil) {
            return [
                'ok' => false,
                'mensaje' => 'No se encontró el perfil indicado.'
            ];
        }

        $sqlUpdate = "UPDATE usuario
                      SET id_perfil = :id_perfil
                      WHERE id_usuario = :id_usuario";

        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':id_perfil' => $idPerfil,
            ':id_usuario' => $idUsuarioObjetivo
        ]);

        return [
            'ok' => true,
            'mensaje' => 'Perfil actualizado correctamente.'
        ];
    } catch (PDOException $e) {
        return [
            'ok' => false,
            'mensaje' => 'Error al actualizar el perfil: ' . $e->getMessage()
        ];
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        responderJSON([
            'ok' => true,
            'admin' => obtenerAdmin($conn, $idAdmin),
            'usuarios' => obtenerUsuarios($conn, $busqueda)
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $accion = $_POST['accion'] ?? '';
        $idUsuarioObjetivo = isset($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : 0;

        if ($accion === 'cambiar_estado') {
            $nuevoEstado = trim($_POST['estado'] ?? '');

            if ($idUsuarioObjetivo <= 0 || $nuevoEstado === '') {
                responderJSON([
                    'ok' => false,
                    'mensaje' => 'Datos no válidos.'
                ]);
            }

            responderJSON(actualizarEstadoUsuario($conn, $idUsuarioObjetivo, $nuevoEstado, $idAdmin));
        }

        if ($accion === 'cambiar_perfil') {
            $nuevoPerfil = strtoupper(trim($_POST['perfil'] ?? ''));

            if ($idUsuarioObjetivo <= 0 || $nuevoPerfil === '') {
                responderJSON([
                    'ok' => false,
                    'mensaje' => 'Datos no válidos.'
                ]);
            }

            responderJSON(actualizarPerfilUsuario($conn, $idUsuarioObjetivo, $nuevoPerfil, $idAdmin));
        }

        responderJSON([
            'ok' => false,
            'mensaje' => 'Acción no válida.'
        ]);
    }

    responderJSON([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);
} catch (PDOException $e) {
    responderJSON([
        'ok' => false,
        'mensaje' => 'Error al obtener los usuarios: ' . $e->getMessage()
    ]);
}
