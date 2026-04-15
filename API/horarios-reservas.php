<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion.php';

/* =========================================================
   1. COMPROBAR SESIÓN
========================================================= */
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Sesión no válida.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idUsuario = (int) $_SESSION['id_usuario'];

/* =========================================================
   2. FUNCIONES AUXILIARES
========================================================= */
function responderJSON(array $datos): void
{
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function formatearFecha(?string $fecha): string
{
    if (!$fecha) {
        return 'Sin fecha';
    }

    $timestamp = strtotime($fecha);
    if (!$timestamp) {
        return $fecha;
    }

    return date('d/m/Y', $timestamp);
}

function obtenerUsuarioSidebar(PDO $conn, int $idUsuario): array
{
    $sql = "SELECT 
                u.nombre,
                u.apellidos,
                u.foto_perfil,
                COALESCE(m.nombre, 'Sin membresía') AS membresia
            FROM usuario u
            LEFT JOIN suscripcion s
                ON u.id_usuario = s.id_usuario
                AND s.estado = 'Activa'
            LEFT JOIN membresia m
                ON s.id_membresia = m.id_membresia
            WHERE u.id_usuario = :id_usuario
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':id_usuario' => $idUsuario
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        return [
            'foto_perfil' => '../img-socios/socio1.png',
            'nombre_completo' => 'Usuario',
            'membresia' => 'Sin membresía'
        ];
    }

    $foto = !empty($usuario['foto_perfil'])
        ? $usuario['foto_perfil']
        : '../img-socios/socio1.png';

    return [
        'foto_perfil' => $foto,
        'nombre_completo' => trim($usuario['nombre'] . ' ' . $usuario['apellidos']),
        'membresia' => $usuario['membresia']
    ];
}

function obtenerResumen(PDO $conn, int $idUsuario): array
{
    $resumen = [
        'proxima_clase' => 'Sin reservas',
        'proxima_fecha' => 'No hay próximas sesiones',
        'reservas_activas' => 0,
        'ultima_reserva' => 'Sin reservas',
        'ultima_fecha' => 'No disponible',
        'sesiones_disponibles' => 0
    ];

    $sqlProxima = "SELECT 
                        a.nombre AS actividad,
                        sa.fecha,
                        h.hora_inicio
                   FROM reserva r
                   INNER JOIN sesion_actividad sa ON r.id_sesion = sa.id_sesion
                   INNER JOIN horario_actividad h ON sa.id_horario = h.id_horario
                   INNER JOIN actividad a ON h.id_actividad = a.id_actividad
                   WHERE r.id_usuario = :id_usuario
                     AND r.estado = 'Confirmada'
                     AND sa.fecha >= CURDATE()
                     AND sa.estado = 'Programada'
                   ORDER BY sa.fecha ASC, h.hora_inicio ASC
                   LIMIT 1";

    $stmtProxima = $conn->prepare($sqlProxima);
    $stmtProxima->execute([
        ':id_usuario' => $idUsuario
    ]);

    $proxima = $stmtProxima->fetch(PDO::FETCH_ASSOC);

    if ($proxima) {
        $resumen['proxima_clase'] = $proxima['actividad'];
        $resumen['proxima_fecha'] = formatearFecha($proxima['fecha']) . ' · ' . substr($proxima['hora_inicio'], 0, 5);
    }

    $sqlActivas = "SELECT COUNT(*)
                   FROM reserva r
                   INNER JOIN sesion_actividad sa ON r.id_sesion = sa.id_sesion
                   WHERE r.id_usuario = :id_usuario
                     AND r.estado = 'Confirmada'
                     AND sa.fecha >= CURDATE()
                     AND sa.estado = 'Programada'";

    $stmtActivas = $conn->prepare($sqlActivas);
    $stmtActivas->execute([
        ':id_usuario' => $idUsuario
    ]);

    $resumen['reservas_activas'] = (int) $stmtActivas->fetchColumn();

    $sqlUltima = "SELECT 
                    a.nombre AS actividad,
                    r.fecha_reserva
                  FROM reserva r
                  INNER JOIN sesion_actividad sa ON r.id_sesion = sa.id_sesion
                  INNER JOIN horario_actividad h ON sa.id_horario = h.id_horario
                  INNER JOIN actividad a ON h.id_actividad = a.id_actividad
                  WHERE r.id_usuario = :id_usuario
                  ORDER BY r.fecha_reserva DESC
                  LIMIT 1";

    $stmtUltima = $conn->prepare($sqlUltima);
    $stmtUltima->execute([
        ':id_usuario' => $idUsuario
    ]);

    $ultima = $stmtUltima->fetch(PDO::FETCH_ASSOC);

    if ($ultima) {
        $resumen['ultima_reserva'] = $ultima['actividad'];
        $resumen['ultima_fecha'] = formatearFecha($ultima['fecha_reserva']);
    }

    $sqlDisponibles = "SELECT COUNT(*)
                       FROM sesion_actividad sa
                       INNER JOIN horario_actividad h ON sa.id_horario = h.id_horario
                       INNER JOIN sala s ON h.id_sala = s.id_sala
                       LEFT JOIN (
                            SELECT id_sesion, COUNT(*) AS ocupadas
                            FROM reserva
                            WHERE estado = 'Confirmada'
                            GROUP BY id_sesion
                       ) r ON sa.id_sesion = r.id_sesion
                       WHERE sa.fecha >= CURDATE()
                         AND sa.estado = 'Programada'
                         AND COALESCE(r.ocupadas, 0) < COALESCE(s.capacidad, sa.plazas_totales)";

    $stmtDisponibles = $conn->query($sqlDisponibles);
    $resumen['sesiones_disponibles'] = (int) $stmtDisponibles->fetchColumn();

    return $resumen;
}

function obtenerTablaHorarios(PDO $conn, int $idUsuario): array
{
    $sql = "SELECT
                sa.id_sesion,
                sa.fecha,
                sa.instructor,
                sa.plazas_totales,
                sa.estado AS estado_sesion,
                h.dia_semana,
                h.hora_inicio,
                h.hora_fin,
                a.nombre AS actividad,
                s.nombre AS sala,
                s.capacidad,
                r.estado AS estado_reserva,
                COALESCE(rc.ocupadas, 0) AS plazas_ocupadas
            FROM sesion_actividad sa
            INNER JOIN horario_actividad h ON sa.id_horario = h.id_horario
            INNER JOIN actividad a ON h.id_actividad = a.id_actividad
            LEFT JOIN sala s ON h.id_sala = s.id_sala
            LEFT JOIN reserva r
                ON r.id_sesion = sa.id_sesion
               AND r.id_usuario = :id_usuario
            LEFT JOIN (
                SELECT id_sesion, COUNT(*) AS ocupadas
                FROM reserva
                WHERE estado = 'Confirmada'
                GROUP BY id_sesion
            ) rc ON rc.id_sesion = sa.id_sesion
            WHERE sa.fecha >= CURDATE()
              AND sa.estado = 'Programada'
            ORDER BY h.hora_inicio ASC, sa.fecha ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':id_usuario' => $idUsuario
    ]);

    $sesiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $filas = [];

    foreach ($sesiones as $sesion) {
        $franja = substr($sesion['hora_inicio'], 0, 5) . ' - ' . substr($sesion['hora_fin'], 0, 5);
        $dia = $sesion['dia_semana'];

        if (!isset($filas[$franja])) {
            $filas[$franja] = [
                'franja' => $franja,
                'dias' => []
            ];
        }

        $capacidadBase = !empty($sesion['capacidad']) ? (int)$sesion['capacidad'] : (int)$sesion['plazas_totales'];
        $ocupadas = (int)$sesion['plazas_ocupadas'];
        $libres = $capacidadBase - $ocupadas;

        if ($libres < 0) {
            $libres = 0;
        }

        $estado = 'Disponible';
        $puedeReservar = true;

        if ($sesion['estado_reserva'] === 'Confirmada') {
            $estado = 'Confirmada';
            $puedeReservar = false;
        } elseif ($libres <= 0) {
            $estado = 'Completa';
            $puedeReservar = false;
        }

        $filas[$franja]['dias'][$dia] = [
            'id_sesion' => (int)$sesion['id_sesion'],
            'actividad' => $sesion['actividad'],
            'sala' => $sesion['sala'] ?? 'Por asignar',
            'monitor' => !empty($sesion['instructor']) ? $sesion['instructor'] : 'Por confirmar',
            'fecha' => formatearFecha($sesion['fecha']),
            'plazas' => 'Plazas libres: ' . $libres,
            'estado' => $estado,
            'puede_reservar' => $puedeReservar
        ];
    }

    return array_values($filas);
}

function reservarSesion(PDO $conn, int $idUsuario, int $idSesion): array
{
    try {
        $conn->beginTransaction();

        $sqlSesion = "SELECT
                        sa.id_sesion,
                        sa.fecha,
                        sa.plazas_totales,
                        sa.estado,
                        s.capacidad
                      FROM sesion_actividad sa
                      INNER JOIN horario_actividad h ON sa.id_horario = h.id_horario
                      LEFT JOIN sala s ON h.id_sala = s.id_sala
                      WHERE sa.id_sesion = :id_sesion
                      LIMIT 1
                      FOR UPDATE";

        $stmtSesion = $conn->prepare($sqlSesion);
        $stmtSesion->execute([
            ':id_sesion' => $idSesion
        ]);

        $sesion = $stmtSesion->fetch(PDO::FETCH_ASSOC);

        if (!$sesion) {
            $conn->rollBack();
            return [
                'ok' => false,
                'mensaje' => 'La sesión no existe.'
            ];
        }

        if ($sesion['estado'] !== 'Programada') {
            $conn->rollBack();
            return [
                'ok' => false,
                'mensaje' => 'La sesión no está disponible para reservar.'
            ];
        }

        if ($sesion['fecha'] < date('Y-m-d')) {
            $conn->rollBack();
            return [
                'ok' => false,
                'mensaje' => 'No puedes reservar una sesión pasada.'
            ];
        }

        $sqlOcupadas = "SELECT COUNT(*) 
                        FROM reserva
                        WHERE id_sesion = :id_sesion
                          AND estado = 'Confirmada'";

        $stmtOcupadas = $conn->prepare($sqlOcupadas);
        $stmtOcupadas->execute([
            ':id_sesion' => $idSesion
        ]);

        $ocupadas = (int)$stmtOcupadas->fetchColumn();
        $capacidad = !empty($sesion['capacidad']) ? (int)$sesion['capacidad'] : (int)$sesion['plazas_totales'];

        if ($ocupadas >= $capacidad) {
            $conn->rollBack();
            return [
                'ok' => false,
                'mensaje' => 'No quedan plazas disponibles para esta sesión.'
            ];
        }

        $sqlExistente = "SELECT id_reserva, estado
                         FROM reserva
                         WHERE id_usuario = :id_usuario
                           AND id_sesion = :id_sesion
                         LIMIT 1
                         FOR UPDATE";

        $stmtExistente = $conn->prepare($sqlExistente);
        $stmtExistente->execute([
            ':id_usuario' => $idUsuario,
            ':id_sesion' => $idSesion
        ]);

        $reservaExistente = $stmtExistente->fetch(PDO::FETCH_ASSOC);

        if ($reservaExistente && $reservaExistente['estado'] === 'Confirmada') {
            $conn->rollBack();
            return [
                'ok' => false,
                'mensaje' => 'Ya tienes esta sesión reservada.'
            ];
        }

        if ($reservaExistente) {
            $sqlActualizar = "UPDATE reserva
                              SET estado = 'Confirmada',
                                  fecha_reserva = NOW()
                              WHERE id_reserva = :id_reserva";

            $stmtActualizar = $conn->prepare($sqlActualizar);
            $stmtActualizar->execute([
                ':id_reserva' => $reservaExistente['id_reserva']
            ]);
        } else {
            $sqlInsertar = "INSERT INTO reserva (id_usuario, id_sesion, fecha_reserva, estado)
                            VALUES (:id_usuario, :id_sesion, NOW(), 'Confirmada')";

            $stmtInsertar = $conn->prepare($sqlInsertar);
            $stmtInsertar->execute([
                ':id_usuario' => $idUsuario,
                ':id_sesion' => $idSesion
            ]);
        }

        $conn->commit();

        return [
            'ok' => true,
            'mensaje' => 'Reserva realizada correctamente.'
        ];
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        return [
            'ok' => false,
            'mensaje' => 'Error al realizar la reserva: ' . $e->getMessage()
        ];
    }
}

/* =========================================================
   3. PETICIONES GET Y POST
========================================================= */
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $usuario = obtenerUsuarioSidebar($conn, $idUsuario);
        $resumen = obtenerResumen($conn, $idUsuario);
        $tabla = obtenerTablaHorarios($conn, $idUsuario);

        responderJSON([
            'ok' => true,
            'usuario' => $usuario,
            'resumen' => $resumen,
            'tabla' => $tabla
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idSesion = isset($_POST['id_sesion']) ? (int) $_POST['id_sesion'] : 0;

        if ($idSesion <= 0) {
            responderJSON([
                'ok' => false,
                'mensaje' => 'Sesión no válida.'
            ]);
        }

        $resultado = reservarSesion($conn, $idUsuario, $idSesion);
        responderJSON($resultado);
    }

    responderJSON([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);
} catch (PDOException $e) {
    responderJSON([
        'ok' => false,
        'mensaje' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
