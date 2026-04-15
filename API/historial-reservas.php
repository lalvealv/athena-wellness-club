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

    // Historial de reservas del usuario
    $sqlHistorial = "SELECT
                        a.nombre AS actividad,
                        sa.fecha,
                        ha.hora_inicio,
                        ha.hora_fin,
                        s.nombre AS sala,
                        r.estado
                     FROM reserva r
                     INNER JOIN sesion_actividad sa
                        ON r.id_sesion = sa.id_sesion
                     INNER JOIN horario_actividad ha
                        ON sa.id_horario = ha.id_horario
                     INNER JOIN actividad a
                        ON ha.id_actividad = a.id_actividad
                     LEFT JOIN sala s
                        ON ha.id_sala = s.id_sala
                     WHERE r.id_usuario = :id_usuario
                     ORDER BY sa.fecha DESC, ha.hora_inicio DESC";

    $stmtHistorial = $conn->prepare($sqlHistorial);
    $stmtHistorial->execute([
        ':id_usuario' => $idUsuario
    ]);
    $historial = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);

    $fotoPerfil = !empty($usuario['foto_perfil'])
        ? $usuario['foto_perfil']
        : '../img-socios/socio1.png';

    $nombreCompleto = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));
    $membresia = $usuario['membresia'] ?? 'Sin suscripción activa';

    $filas = [];
    foreach ($historial as $item) {
        $filas[] = [
            'actividad' => $item['actividad'],
            'fecha' => !empty($item['fecha']) ? date('d/m/Y', strtotime($item['fecha'])) : 'No disponible',
            'hora' => substr($item['hora_inicio'], 0, 5) . ' - ' . substr($item['hora_fin'], 0, 5),
            'sala' => $item['sala'] ?? 'Sin sala',
            'estado' => $item['estado']
        ];
    }

    echo json_encode([
        'ok' => true,
        'sidebar' => [
            'foto_perfil' => $fotoPerfil,
            'nombre_completo' => $nombreCompleto !== '' ? $nombreCompleto : 'Usuario',
            'membresia' => $membresia
        ],
        'historial' => $filas
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener el historial de reservas.',
        'error' => $e->getMessage()
    ]);
}
