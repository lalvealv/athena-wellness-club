<?php
require_once __DIR__ . '/../comprobar-login.php';
require_once __DIR__ . '/../conexion.php';

header('Content-Type: application/json; charset=utf-8');

$idUsuario = (int)$_SESSION['id_usuario'];

function responderJSON(array $datos, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if (isset($_GET['accion']) && $_GET['accion'] === 'conteo') {

        $sqlConteo = "SELECT COUNT(*) 
                  FROM usuario_notificacion
                  WHERE id_usuario = :id_usuario
                    AND leida = 0";

        $stmtConteo = $conn->prepare($sqlConteo);
        $stmtConteo->execute([
            ':id_usuario' => $idUsuario
        ]);

        $noLeidas = (int) $stmtConteo->fetchColumn();

        echo json_encode([
            'ok' => true,
            'no_leidas' => $noLeidas
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if (isset($_GET['accion']) && $_GET['accion'] === 'detalle') {
        $idNotificacion = (int)($_GET['id_notificacion'] ?? 0);

        if ($idNotificacion <= 0) {
            responderJSON([
                'ok' => false,
                'mensaje' => 'ID de notificación no válido.'
            ], 400);
        }

        $sqlDetalle = "SELECT
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
                         AND n.id_notificacion = :id_notificacion
                       LIMIT 1";

        $stmtDetalle = $conn->prepare($sqlDetalle);
        $stmtDetalle->execute([
            ':id_usuario' => $idUsuario,
            ':id_notificacion' => $idNotificacion
        ]);
        $notificacion = $stmtDetalle->fetch(PDO::FETCH_ASSOC);

        if (!$notificacion) {
            responderJSON([
                'ok' => false,
                'mensaje' => 'No se encontró la notificación.'
            ], 404);
        }

        $sqlMarcarLeida = "UPDATE usuario_notificacion
                           SET leida = 1,
                               fecha_lectura = NOW()
                           WHERE id_usuario = :id_usuario
                             AND id_notificacion = :id_notificacion
                             AND leida = 0";

        $stmtMarcarLeida = $conn->prepare($sqlMarcarLeida);
        $stmtMarcarLeida->execute([
            ':id_usuario' => $idUsuario,
            ':id_notificacion' => $idNotificacion
        ]);

        responderJSON([
            'ok' => true,
            'notificacion' => [
                'id_notificacion' => (int)$notificacion['id_notificacion'],
                'titulo' => $notificacion['titulo'] ?? 'Sin título',
                'mensaje' => $notificacion['mensaje'] ?? '',
                'tipo' => $notificacion['tipo'] ?? 'General',
                'fecha_envio' => !empty($notificacion['fecha_envio'])
                    ? date('d/m/Y H:i', strtotime($notificacion['fecha_envio']))
                    : 'No disponible',
                'leida' => 1
            ]
        ]);
    }

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
        responderJSON([
            'ok' => false,
            'mensaje' => 'No se encontró la información del usuario.'
        ], 404);
    }

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
                          ORDER BY un.leida ASC, n.fecha_envio DESC, n.id_notificacion DESC";

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
    $noLeidas = 0;

    foreach ($notificaciones as $item) {
        $leida = isset($item['leida']) ? (int)$item['leida'] : 0;

        if ($leida === 0) {
            $noLeidas++;
        }

        $mensaje = $item['mensaje'] ?? '';
        $resumen = mb_strlen($mensaje) > 90 ? mb_substr($mensaje, 0, 90) . '...' : $mensaje;

        $lista[] = [
            'id_notificacion' => (int)$item['id_notificacion'],
            'titulo' => $item['titulo'] ?? 'Sin título',
            'mensaje' => $mensaje,
            'resumen' => $resumen !== '' ? $resumen : 'Sin contenido',
            'tipo' => $item['tipo'] ?? 'General',
            'fecha_envio' => !empty($item['fecha_envio'])
                ? date('d/m/Y H:i', strtotime($item['fecha_envio']))
                : 'No disponible',
            'leida' => $leida
        ];
    }

    responderJSON([
        'ok' => true,
        'sidebar' => [
            'foto_perfil' => $fotoPerfil,
            'nombre_completo' => $nombreCompleto !== '' ? $nombreCompleto : 'Usuario',
            'membresia' => $membresia
        ],
        'no_leidas' => $noLeidas,
        'notificaciones' => $lista
    ]);
} catch (PDOException $e) {
    responderJSON([
        'ok' => false,
        'mensaje' => 'Error al obtener las notificaciones.',
        'error' => $e->getMessage()
    ], 500);
}
