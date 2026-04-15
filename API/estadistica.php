<?php
require_once __DIR__ . '/../comprobar-login.php';
require_once __DIR__ . '/../conexion.php';

header('Content-Type: application/json; charset=utf-8');

$idUsuario = $_SESSION['id_usuario'];

function mensajeEntrenamientosSemanales(int $total): string
{
    if ($total <= 0) {
        return 'Aún no has entrenado esta semana';
    }

    if ($total <= 2) {
        return 'Buen comienzo';
    }

    if ($total <= 4) {
        return 'Muy buena regularidad';
    }

    return 'Ritmo excelente';
}

try {
    // Sidebar
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

    // Clases este mes (reservas asistidas o confirmadas del mes actual)
    $sqlClasesMes = "SELECT COUNT(*)
                     FROM reserva r
                     INNER JOIN sesion_actividad sa
                        ON r.id_sesion = sa.id_sesion
                     WHERE r.id_usuario = :id_usuario
                       AND MONTH(sa.fecha) = MONTH(CURDATE())
                       AND YEAR(sa.fecha) = YEAR(CURDATE())
                       AND r.estado IN ('Confirmada', 'Asistida')";

    $stmtClasesMes = $conn->prepare($sqlClasesMes);
    $stmtClasesMes->execute([
        ':id_usuario' => $idUsuario
    ]);
    $clasesMes = (int) $stmtClasesMes->fetchColumn();

    // Actividad favorita
    $sqlActividadFavorita = "SELECT
                                a.nombre,
                                COUNT(*) AS total
                             FROM reserva r
                             INNER JOIN sesion_actividad sa
                                ON r.id_sesion = sa.id_sesion
                             INNER JOIN horario_actividad ha
                                ON sa.id_horario = ha.id_horario
                             INNER JOIN actividad a
                                ON ha.id_actividad = a.id_actividad
                             WHERE r.id_usuario = :id_usuario
                             GROUP BY a.id_actividad, a.nombre
                             ORDER BY total DESC, a.nombre ASC
                             LIMIT 1";

    $stmtActividadFavorita = $conn->prepare($sqlActividadFavorita);
    $stmtActividadFavorita->execute([
        ':id_usuario' => $idUsuario
    ]);
    $actividadFavorita = $stmtActividadFavorita->fetch(PDO::FETCH_ASSOC);

    // Entrenamientos esta semana
    $sqlEntrenamientosSemana = "SELECT COUNT(*)
                                FROM entrenamiento
                                WHERE id_usuario = :id_usuario
                                  AND YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)";

    $stmtEntrenamientosSemana = $conn->prepare($sqlEntrenamientosSemana);
    $stmtEntrenamientosSemana->execute([
        ':id_usuario' => $idUsuario
    ]);
    $entrenamientosSemana = (int) $stmtEntrenamientosSemana->fetchColumn();

    // Asistencia
    $sqlAsistencia = "SELECT
                        COUNT(*) AS total,
                        SUM(CASE WHEN estado = 'Asistida' THEN 1 ELSE 0 END) AS asistidas
                      FROM reserva
                      WHERE id_usuario = :id_usuario";

    $stmtAsistencia = $conn->prepare($sqlAsistencia);
    $stmtAsistencia->execute([
        ':id_usuario' => $idUsuario
    ]);
    $asistencia = $stmtAsistencia->fetch(PDO::FETCH_ASSOC);

    $totalReservas = (int) ($asistencia['total'] ?? 0);
    $totalAsistidas = (int) ($asistencia['asistidas'] ?? 0);

    $porcentajeAsistencia = $totalReservas > 0
        ? round(($totalAsistidas / $totalReservas) * 100) . '%'
        : '—';

    $fotoPerfil = !empty($usuario['foto_perfil'])
        ? $usuario['foto_perfil']
        : '../img-socios/socio1.png';

    $nombreCompleto = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));
    $membresia = $usuario['membresia'] ?? 'Sin suscripción activa';

    $nombreActividadFavorita = $actividadFavorita['nombre'] ?? 'Sin datos';
    $detalleActividadFavorita = $actividadFavorita
        ? 'Reservada ' . $actividadFavorita['total'] . ' vez/veces'
        : 'Aún no hay actividad favorita';

    echo json_encode([
        'ok' => true,
        'sidebar' => [
            'foto_perfil' => $fotoPerfil,
            'nombre_completo' => $nombreCompleto !== '' ? $nombreCompleto : 'Usuario',
            'membresia' => $membresia
        ],
        'estadisticas' => [
            'clases_mes' => $clasesMes,
            'clases_mes_detalle' => 'Clases confirmadas o asistidas este mes',
            'actividad_favorita' => $nombreActividadFavorita,
            'actividad_favorita_detalle' => $detalleActividadFavorita,
            'entrenamientos_semanales' => $entrenamientosSemana,
            'entrenamientos_semanales_detalle' => mensajeEntrenamientosSemanales($entrenamientosSemana),
            'asistencia' => $porcentajeAsistencia,
            'asistencia_detalle' => $totalReservas > 0
                ? $totalAsistidas . ' asistencia(s) de ' . $totalReservas . ' reserva(s)'
                : 'Aún no hay reservas registradas'
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener las estadísticas.',
        'error' => $e->getMessage()
    ]);
}
