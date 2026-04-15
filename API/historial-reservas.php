<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Sesión no válida.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idUsuario = (int) $_SESSION['id_usuario'];

function responderJSON(array $datos): void
{
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function formatearFecha(?string $fecha): string
{
    if (!$fecha) {
        return 'No disponible';
    }

    return date('d/m/Y', strtotime($fecha));
}

function obtenerSidebar(PDO $conn, int $idUsuario): array
{
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
        return [
            'foto_perfil' => '../img-socios/socio1.png',
            'nombre_completo' => 'Usuario',
            'membresia' => 'Sin suscripción activa'
        ];
    }

    $fotoPerfil = !empty($usuario['foto_perfil'])
        ? $usuario['foto_perfil']
        : '../img-socios/socio1.png';

    $nombreCompleto = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));
    $membresia = $usuario['membresia'] ?? 'Sin suscripción activa';

    return [
        'foto_perfil' => $fotoPerfil,
        'nombre_completo' => $nombreCompleto !== '' ? $nombreCompleto : 'Usuario',
        'membresia' => $membresia
    ];
}

function obtenerHistorial(PDO $conn, int $idUsuario): array
{
    $sqlHistorial = "SELECT
                        r.id_reserva,
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
    $filas = [];

    foreach ($historial as $item) {
        $timestampClase = strtotime($item['fecha'] . ' ' . $item['hora_inicio']);
        $timestampAhora = time();

        $puedeCancelar = false;

        if (
            $item['estado'] === 'Confirmada' &&
            ($timestampClase - $timestampAhora) > 3600
        ) {
            $puedeCancelar = true;
        }

        $filas[] = [
            'id_reserva' => (int) $item['id_reserva'],
            'actividad' => $item['actividad'],
            'fecha' => formatearFecha($item['fecha']),
            'hora' => substr($item['hora_inicio'], 0, 5) . ' - ' . substr($item['hora_fin'], 0, 5),
            'sala' => $item['sala'] ?? 'Sin sala',
            'estado' => $item['estado'],
            'puede_cancelar' => $puedeCancelar
        ];
    }

    return $filas;
}

function cancelarReserva(PDO $conn, int $idUsuario, int $idReserva): array
{
    try {
        $conn->beginTransaction();

        $sqlReserva = "SELECT
                            r.id_reserva,
                            r.estado,
                            sa.fecha,
                            ha.hora_inicio
                       FROM reserva r
                       INNER JOIN sesion_actividad sa
                            ON r.id_sesion = sa.id_sesion
                       INNER JOIN horario_actividad ha
                            ON sa.id_horario = ha.id_horario
                       WHERE r.id_reserva = :id_reserva
                         AND r.id_usuario = :id_usuario
                       LIMIT 1
                       FOR UPDATE";

        $stmtReserva = $conn->prepare($sqlReserva);
        $stmtReserva->execute([
            ':id_reserva' => $idReserva,
            ':id_usuario' => $idUsuario
        ]);

        $reserva = $stmtReserva->fetch(PDO::FETCH_ASSOC);

        if (!$reserva) {
            $conn->rollBack();
            return [
                'ok' => false,
                'mensaje' => 'La reserva no existe.'
            ];
        }

        if ($reserva['estado'] !== 'Confirmada') {
            $conn->rollBack();
            return [
                'ok' => false,
                'mensaje' => 'Solo se pueden cancelar reservas confirmadas.'
            ];
        }

        $timestampClase = strtotime($reserva['fecha'] . ' ' . $reserva['hora_inicio']);
        $timestampAhora = time();

        if (($timestampClase - $timestampAhora) <= 3600) {
            $conn->rollBack();
            return [
                'ok' => false,
                'mensaje' => 'No puedes cancelar la reserva con menos de 1 hora de antelación.'
            ];
        }

        $sqlUpdate = "UPDATE reserva
                      SET estado = 'Cancelada'
                      WHERE id_reserva = :id_reserva";

        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':id_reserva' => $idReserva
        ]);

        $conn->commit();

        return [
            'ok' => true,
            'mensaje' => 'Reserva cancelada correctamente.'
        ];
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        return [
            'ok' => false,
            'mensaje' => 'Error al cancelar la reserva: ' . $e->getMessage()
        ];
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sidebar = obtenerSidebar($conn, $idUsuario);
        $historial = obtenerHistorial($conn, $idUsuario);

        responderJSON([
            'ok' => true,
            'sidebar' => $sidebar,
            'historial' => $historial
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $accion = $_POST['accion'] ?? '';

        if ($accion !== 'cancelar') {
            responderJSON([
                'ok' => false,
                'mensaje' => 'Acción no válida.'
            ]);
        }

        $idReserva = isset($_POST['id_reserva']) ? (int) $_POST['id_reserva'] : 0;

        if ($idReserva <= 0) {
            responderJSON([
                'ok' => false,
                'mensaje' => 'Reserva no válida.'
            ]);
        }

        $resultado = cancelarReserva($conn, $idUsuario, $idReserva);
        responderJSON($resultado);
    }

    responderJSON([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);
} catch (PDOException $e) {
    responderJSON([
        'ok' => false,
        'mensaje' => 'Error al obtener el historial de reservas: ' . $e->getMessage()
    ]);
}
