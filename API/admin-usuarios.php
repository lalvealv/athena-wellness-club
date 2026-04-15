<?php
require_once __DIR__ . '/../comprobar-admin.php';
require_once __DIR__ . '/../conexion.php';

header('Content-Type: application/json; charset=utf-8');

$busqueda = trim($_GET['buscar'] ?? '');
$idAdmin = $_SESSION['id_usuario'];

try {
    // Datos del admin logueado
    $sqlAdmin = "SELECT 
                    nombre,
                    apellidos,
                    foto_perfil
                 FROM usuario
                 WHERE id_usuario = :id_usuario
                 LIMIT 1";

    $stmtAdmin = $conn->prepare($sqlAdmin);
    $stmtAdmin->execute([
        ':id_usuario' => $idAdmin
    ]);
    $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se encontró el administrador logueado.'
        ]);
        exit;
    }

    $sqlUsuarios = "SELECT 
                        u.id_usuario,
                        u.alias,
                        u.nombre,
                        u.apellidos,
                        u.correo,
                        u.telefono,
                        u.estado,
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
            'id_usuario' => $usuario['id_usuario'],
            'alias' => $usuario['alias'] ?? '',
            'nombre_completo' => trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? '')),
            'correo' => $usuario['correo'] ?? '',
            'telefono' => $usuario['telefono'] ?? 'No disponible',
            'perfil' => $usuario['nombre_perfil'] ?? 'No disponible',
            'estado' => $usuario['estado'] ?? 'No disponible'
        ];
    }

    $fotoAdmin = !empty($admin['foto_perfil']) ? $admin['foto_perfil'] : '../img/admin.jpg';
    $nombreAdmin = trim(($admin['nombre'] ?? '') . ' ' . ($admin['apellidos'] ?? ''));

    echo json_encode([
        'ok' => true,
        'admin' => [
            'foto_perfil' => $fotoAdmin,
            'nombre_completo' => $nombreAdmin !== '' ? $nombreAdmin : 'Administrador ATHENA',
            'perfil' => 'Perfil ADMIN'
        ],
        'usuarios' => $listaUsuarios
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener los usuarios.',
        'error' => $e->getMessage()
    ]);
}
