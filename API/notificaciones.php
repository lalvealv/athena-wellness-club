<?php
require_once __DIR__ . '/../comprobar-login.php';
require_once __DIR__ . '/../conexion.php';

header('Content-Type: application/json; charset=utf-8');

$idUsuario = $_SESSION['id_usuario'];

try {
    // Datos sidebar
    $sqlUsuario = "SELECT 
                        u.nombre,
                        u.apellidos,
                        u.foto_perfil,
                        m.nombre AS membresia
                   FROM usuario u
                   LEFT JOIN suscripcion s
                        ON u.id_usuario = s.id_usuario AND s.estado = 'Activa'
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
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se encontró la información del usuario.'
        ]);
        exit;
    }

    // Notificaciones del usuario
    $sqlNotificaciones = "SELECT
                              n.id_notificacion,
                              n.titulo,
                              n.mensaje,
                              n.tipo,
                              n.fecha_envio,
                              un.leida
                          FROM usuario_notificacion un
                          INNER JOIN notificacion n
                              ON un.id_notificacion = n.id_notificacion
                          WHERE un.id_usuario = :id_usuario
                          ORDER BY n.fecha_envio DESC, n.id_notificacion DESC";

    $stmtNotificaciones = $conn->prepare($sqlNotificaciones);
    $stmtNotificaciones->execute([
        ':id_usuario' => $idUsuario
    ]);
    $notificaciones = $stmtNotificaciones->fetchAll(PDO::FETCH_ASSOC);

    $fotoPerfil = !empty($usuario['foto_perfil'])
        ? $usuario['foto_perfil']
        : '../img-socios/socio1.png';

    $nombreCompleto = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));
    $membresia = $usuario['membresia'] ?? 'Sin suscripción activa';

    $lista = [];
    foreach ($notificaciones as $item) {
        $lista[] = [
            'titulo' => $item['titulo'] ?? 'Sin título',
            'mensaje' => $item['mensaje'] ?? '',
            'tipo' => $item['tipo'] ?? 'General',
            'fecha_envio' => !empty($item['fecha_envio'])
                ? date('d/m/Y H:i', strtotime($item['fecha_envio']))
                : 'No disponible',
            'leida' => isset($item['leida']) ? (int) $item['leida'] : 0
        ];
    }

    echo json_encode([
        'ok' => true,
        'sidebar' => [
            'foto_perfil' => $fotoPerfil,
            'nombre_completo' => $nombreCompleto !== '' ? $nombreCompleto : 'Usuario',
            'membresia' => $membresia
        ],
        'notificaciones' => $lista
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener las notificaciones.',
        'error' => $e->getMessage()
    ]);
}
