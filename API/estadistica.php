<?php
require_once __DIR__ . '/../comprobar-login.php';
require_once __DIR__ . '/../conexion.php';

header('Content-Type: application/json; charset=utf-8');

$idUsuario = (int)$_SESSION['id_usuario'];

function mensajeEntrenamientosSemanales(int $total): string
{
    if ($total <= 0) return 'Aún no has entrenado esta semana';
    if ($total <= 2) return 'Buen comienzo';
    if ($total <= 4) return 'Muy buena regularidad';
    return 'Ritmo excelente';
}

try {
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
    $stmtUsuario->execute([':id_usuario' => $idUsuario]);
    $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        echo json_encode(['ok' => false, 'mensaje' => 'Usuario no encontrado']);
        exit;
    }

    $sqlClasesMes = "SELECT COUNT(*)
                     FROM reserva r
                     INNER JOIN sesion_actividad sa ON r.id_sesion = sa.id_sesion
                     WHERE r.id_usuario = :id_usuario
                       AND MONTH(sa.fecha) = MONTH(CURDATE())
                       AND YEAR(sa.fecha) = YEAR(CURDATE())
                       AND r.estado IN ('Confirmada', 'Asistida')";

    $stmt = $conn->prepare($sqlClasesMes);
    $stmt->execute([':id_usuario' => $idUsuario]);
    $clasesMes = (int)$stmt->fetchColumn();

    $sqlFav = "SELECT a.nombre, COUNT(*) total
               FROM reserva r
               INNER JOIN sesion_actividad sa ON r.id_sesion = sa.id_sesion
               INNER JOIN horario_actividad ha ON sa.id_horario = ha.id_horario
               INNER JOIN actividad a ON ha.id_actividad = a.id_actividad
               WHERE r.id_usuario = :id_usuario
               GROUP BY a.id_actividad, a.nombre
               ORDER BY total DESC, a.nombre ASC
               LIMIT 1";

    $stmt = $conn->prepare($sqlFav);
    $stmt->execute([':id_usuario' => $idUsuario]);
    $fav = $stmt->fetch(PDO::FETCH_ASSOC);

    $sqlRanking = "SELECT a.nombre AS actividad, COUNT(*) AS total
                   FROM reserva r
                   INNER JOIN sesion_actividad sa ON r.id_sesion = sa.id_sesion
                   INNER JOIN horario_actividad ha ON sa.id_horario = ha.id_horario
                   INNER JOIN actividad a ON ha.id_actividad = a.id_actividad
                   WHERE r.id_usuario = :id_usuario
                   GROUP BY a.id_actividad, a.nombre
                   ORDER BY total DESC, a.nombre ASC
                   LIMIT 5";

    $stmt = $conn->prepare($sqlRanking);
    $stmt->execute([':id_usuario' => $idUsuario]);
    $ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sqlSemana = "SELECT COUNT(*)
                  FROM entrenamiento
                  WHERE id_usuario = :id_usuario
                  AND YEARWEEK(fecha,1)=YEARWEEK(CURDATE(),1)";

    $stmt = $conn->prepare($sqlSemana);
    $stmt->execute([':id_usuario' => $idUsuario]);
    $entrenamientosSemana = (int)$stmt->fetchColumn();

    $sqlAsistencia = "SELECT COUNT(*) total,
                      SUM(CASE WHEN estado='Asistida' THEN 1 ELSE 0 END) asistidas
                      FROM reserva
                      WHERE id_usuario = :id_usuario";

    $stmt = $conn->prepare($sqlAsistencia);
    $stmt->execute([':id_usuario' => $idUsuario]);
    $asistencia = $stmt->fetch(PDO::FETCH_ASSOC);

    $totalReservas = (int)($asistencia['total'] ?? 0);
    $asistidas = (int)($asistencia['asistidas'] ?? 0);

    $porcentajeNumero = $totalReservas > 0 ? (int)round(($asistidas / $totalReservas) * 100) : 0;
    $porcentajeTexto = $totalReservas > 0 ? $porcentajeNumero . '%' : '—';

    $fotoPerfil = !empty($usuario['foto_perfil'])
        ? $usuario['foto_perfil']
        : '../img-socios/socio1.png';

    $nombreCompleto = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));
    $membresia = $usuario['membresia'] ?? 'Sin suscripción';

    $nombreActividadFavorita = $fav['nombre'] ?? 'Sin datos';
    $detalleActividadFavorita = $fav ? 'Reservada ' . $fav['total'] . ' veces' : 'Sin actividad';

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
            'clases_mes_numero' => $clasesMes,

            'actividad_favorita' => $nombreActividadFavorita,
            'actividad_favorita_detalle' => $detalleActividadFavorita,

            'entrenamientos_semanales' => $entrenamientosSemana,
            'entrenamientos_semanales_detalle' => mensajeEntrenamientosSemanales($entrenamientosSemana),
            'entrenamientos_semanales_numero' => $entrenamientosSemana,

            'asistencia' => $porcentajeTexto,
            'asistencia_detalle' => $totalReservas > 0 ? "$asistidas de $totalReservas reservas" : 'Sin datos',
            'asistencia_porcentaje_numero' => $porcentajeNumero,
            'asistencias_totales_numero' => $asistidas
        ],
        'ranking_actividad' => array_map(function ($item) {
            return [
                'actividad' => $item['actividad'],
                'total' => (int)$item['total']
            ];
        }, $ranking)
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error en estadísticas',
        'error' => $e->getMessage()
    ]);
}
