<?php
// Comprueba que el usuario ha iniciado sesión
require_once __DIR__ . '/../comprobar-login.php';

// Importa la conexión a la base de datos
require_once __DIR__ . '/../conexion.php';

// Indica que la respuesta será JSON
header('Content-Type: application/json; charset=utf-8');

// Obtiene el ID del usuario logueado
$idUsuario = (int)$_SESSION['id_usuario'];

// Función reutilizable para devolver respuestas JSON con código HTTP
function responderJSON(array $datos, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // ACCIÓN: devolver solo el número de notificaciones no leídas
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

    // ACCIÓN: cargar el detalle de una notificación concreta
    if (isset($_GET['accion']) && $_GET['accion'] === 'detalle') {
        $idNotificacion = (int)($_GET['id_notificacion'] ?? 0);

        // Valida el ID de notificación
        if ($idNotificacion <= 0) {
            responderJSON([
                'ok' => false,
                'mensaje' => 'ID de notificación no válido.'
            ], 400);
        }

        // Consulta la notificación del usuario
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

        // Si la notificación no existe o no pertenece al usuario
        if (!$notificacion) {
            responderJSON([
                'ok' => false,
                'mensaje' => 'No se encontró la notificación.'
            ], 404);
        }

        // Marca la notificación como leída
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

        // Devuelve el detalle de la notificación
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

    // Consulta datos del usuario para el sidebar
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

    // Si no se encuentra el usuario, devuelve error
    if (!$usuario) {
        responderJSON([
            'ok' => false,
            'mensaje' => 'No se encontró la información del usuario.'
        ], 404);
    }

    // Consulta todas las notificaciones del usuario
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

    // Define foto de perfil o imagen por defecto
    $fotoPerfil = !empty($usuario['foto_perfil'])
        ? $usuario['foto_perfil']
        : '../img-socios/socio1.png';

    // Construye nombre completo y membresía
    $nombreCompleto = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));
    $membresia = $usuario['membresia'] ?? 'Sin suscripción activa';

    // Prepara listado de notificaciones
    $lista = [];
    $noLeidas = 0;

    foreach ($notificaciones as $item) {
        // Convierte el estado de lectura a número
        $leida = isset($item['leida']) ? (int)$item['leida'] : 0;

        // Cuenta las no leídas
        if ($leida === 0) {
            $noLeidas++;
        }

        // Crea un resumen corto del mensaje
        $mensaje = $item['mensaje'] ?? '';
        $resumen = mb_strlen($mensaje) > 90 ? mb_substr($mensaje, 0, 90) . '...' : $mensaje;

        // Añade la notificación formateada
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

    // Devuelve sidebar, contador y listado de notificaciones
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
    // Devuelve error si falla la base de datos
    responderJSON([
        'ok' => false,
        'mensaje' => 'Error al obtener las notificaciones.',
        'error' => $e->getMessage()
    ], 500);
}
