<?php
require_once __DIR__ . '/../comprobar-login.php';
require_once __DIR__ . '/../conexion.php';

header('Content-Type: application/json; charset=utf-8');

$idUsuario = $_SESSION['id_usuario'];

function mensajeEntrenamientos(int $total): string
{
    if ($total <= 0) {
        return 'Empieza tu semana con energía';
    }

    if ($total <= 2) {
        return 'Buen comienzo de semana';
    }

    if ($total <= 4) {
        return 'Muy buena constancia';
    }

    return 'Ritmo excelente';
}

function porcentajeObjetivo(?string $fechaInicio, ?string $fechaFin, string $estado): ?int
{
    if ($estado === 'Completado') {
        return 100;
    }

    if (empty($fechaInicio) || empty($fechaFin)) {
        return null;
    }

    $inicio = strtotime($fechaInicio);
    $fin = strtotime($fechaFin);
    $hoy = strtotime(date('Y-m-d'));

    if ($inicio === false || $fin === false || $fin <= $inicio) {
        return null;
    }

    if ($hoy <= $inicio) {
        return 0;
    }

    if ($hoy >= $fin) {
        return 100;
    }

    $total = $fin - $inicio;
    $transcurrido = $hoy - $inicio;

    return (int) round(($transcurrido / $total) * 100);
}

try {
    $sqlUsuario = "SELECT 
                        u.nombre,
                        u.apellidos,
                        u.foto_perfil,
                        s.fecha_renovacion,
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

    $sqlProximaClase = "SELECT 
                            a.nombre AS actividad,
                            sa.fecha,
                            ha.hora_inicio
                        FROM reserva r
                        INNER JOIN sesion_actividad sa
                            ON r.id_sesion = sa.id_sesion
                        INNER JOIN horario_actividad ha
                            ON sa.id_horario = ha.id_horario
                        INNER JOIN actividad a
                            ON ha.id_actividad = a.id_actividad
                        WHERE r.id_usuario = :id_usuario
                          AND r.estado = 'Confirmada'
                          AND sa.estado = 'Programada'
                          AND sa.fecha >= CURDATE()
                        ORDER BY sa.fecha ASC, ha.hora_inicio ASC
                        LIMIT 1";

    $stmtProximaClase = $conn->prepare($sqlProximaClase);
    $stmtProximaClase->execute([
        ':id_usuario' => $idUsuario
    ]);
    $proximaClase = $stmtProximaClase->fetch(PDO::FETCH_ASSOC);

    $sqlEntrenamientos = "SELECT COUNT(*) 
                          FROM entrenamiento
                          WHERE id_usuario = :id_usuario
                            AND YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)";

    $stmtEntrenamientos = $conn->prepare($sqlEntrenamientos);
    $stmtEntrenamientos->execute([
        ':id_usuario' => $idUsuario
    ]);
    $totalEntrenamientosSemana = (int) $stmtEntrenamientos->fetchColumn();

    $sqlObjetivo = "SELECT 
                        objetivo,
                        fecha_inicio,
                        fecha_fin,
                        estado
                    FROM objetivo_fitness
                    WHERE id_usuario = :id_usuario
                      AND estado IN ('Activo', 'Completado', 'Pausado')
                    ORDER BY 
                        CASE estado
                            WHEN 'Activo' THEN 1
                            WHEN 'Pausado' THEN 2
                            WHEN 'Completado' THEN 3
                        END,
                        fecha_inicio DESC
                    LIMIT 1";

    $stmtObjetivo = $conn->prepare($sqlObjetivo);
    $stmtObjetivo->execute([
        ':id_usuario' => $idUsuario
    ]);
    $objetivo = $stmtObjetivo->fetch(PDO::FETCH_ASSOC);

    $fotoPerfil = !empty($usuario['foto_perfil'])
        ? $usuario['foto_perfil']
        : '../img-socios/socio1.png';

    $nombreCompleto = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));
    $membresia = $usuario['membresia'] ?? 'Sin suscripción activa';

    $renovacion = !empty($usuario['fecha_renovacion'])
        ? 'Renovación: ' . date('d/m/Y', strtotime($usuario['fecha_renovacion']))
        : 'Sin renovación programada';

    $proximaClaseNombre = $proximaClase['actividad'] ?? 'Sin reservas activas';
    $proximaClaseDetalle = 'No tienes próximas clases reservadas';

    if ($proximaClase) {
        $fechaClase = date('d/m/Y', strtotime($proximaClase['fecha']));
        $horaClase = substr($proximaClase['hora_inicio'], 0, 5);
        $proximaClaseDetalle = $fechaClase . ' · ' . $horaClase;
    }

    $objetivoTexto = 'Sin objetivo activo';
    $objetivoProgreso = '—';

    if ($objetivo) {
        $objetivoTexto = $objetivo['objetivo'] ?? 'Objetivo sin definir';

        $porcentaje = porcentajeObjetivo(
            $objetivo['fecha_inicio'] ?? null,
            $objetivo['fecha_fin'] ?? null,
            $objetivo['estado'] ?? ''
        );

        if ($porcentaje !== null) {
            $objetivoProgreso = $porcentaje . '%';
        } else {
            $objetivoProgreso = $objetivo['estado'] ?? 'Activo';
        }
    }

    echo json_encode([
        'ok' => true,
        'sidebar' => [
            'foto_perfil' => $fotoPerfil,
            'nombre_completo' => $nombreCompleto !== '' ? $nombreCompleto : 'Usuario',
            'membresia' => $membresia
        ],
        'resumen' => [
            'suscripcion' => $membresia,
            'renovacion' => $renovacion,
            'proxima_clase' => $proximaClaseNombre,
            'proxima_clase_detalle' => $proximaClaseDetalle,
            'entrenamientos_semana' => $totalEntrenamientosSemana,
            'entrenamientos_mensaje' => mensajeEntrenamientos($totalEntrenamientosSemana),
            'objetivo_progreso' => $objetivoProgreso,
            'objetivo_texto' => $objetivoTexto
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener el resumen del área de socios.',
        'error' => $e->getMessage()
    ]);
}
