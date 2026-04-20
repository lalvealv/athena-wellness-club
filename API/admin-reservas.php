<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion.php';

$idAdmin = (int)($_SESSION['id_usuario'] ?? 0);
$busqueda = trim($_GET['buscar'] ?? '');
$estado = trim($_GET['estado'] ?? '');
$fecha = trim($_GET['fecha'] ?? '');

if ($idAdmin <= 0) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Sesión no válida.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['id_perfil']) || (int)$_SESSION['id_perfil'] !== 1) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Acceso no autorizado.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function responderJSON(array $datos): void
{
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $sqlAdmin = "SELECT nombre, apellidos, foto_perfil
                 FROM usuario
                 WHERE id_usuario = :id_usuario
                 LIMIT 1";

    $stmtAdmin = $conn->prepare($sqlAdmin);
    $stmtAdmin->execute([
        ':id_usuario' => $idAdmin
    ]);
    $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        responderJSON([
            'ok' => false,
            'mensaje' => 'No se encontró el administrador logueado.'
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $accion = $_POST['accion'] ?? '';
        $idReserva = isset($_POST['id_reserva']) ? (int)$_POST['id_reserva'] : 0;

        if ($accion !== 'eliminar' || $idReserva <= 0) {
            responderJSON([
                'ok' => false,
                'mensaje' => 'Datos no válidos.'
            ]);
        }

        $sqlComprobar = "SELECT id_reserva
                         FROM reserva
                         WHERE id_reserva = :id_reserva
                         LIMIT 1";

        $stmtComprobar = $conn->prepare($sqlComprobar);
        $stmtComprobar->execute([
            ':id_reserva' => $idReserva
        ]);

        $reserva = $stmtComprobar->fetch(PDO::FETCH_ASSOC);

        if (!$reserva) {
            responderJSON([
                'ok' => false,
                'mensaje' => 'La reserva no existe.'
            ]);
        }

        $sqlEliminar = "DELETE FROM reserva
                        WHERE id_reserva = :id_reserva
                        LIMIT 1";

        $stmtEliminar = $conn->prepare($sqlEliminar);
        $stmtEliminar->execute([
            ':id_reserva' => $idReserva
        ]);

        responderJSON([
            'ok' => true,
            'mensaje' => 'Reserva eliminada correctamente.'
        ]);
    }

    $sqlReservas = "SELECT
                        r.id_reserva,
                        u.id_usuario,
                        CONCAT(u.nombre, ' ', u.apellidos) AS usuario,
                        a.nombre AS actividad,
                        sa.fecha,
                        ha.hora_inicio,
                        ha.hora_fin,
                        s.nombre AS sala,
                        sa.instructor,
                        r.estado
                    FROM reserva r
                    INNER JOIN usuario u
                        ON r.id_usuario = u.id_usuario
                    INNER JOIN sesion_actividad sa
                        ON r.id_sesion = sa.id_sesion
                    INNER JOIN horario_actividad ha
                        ON sa.id_horario = ha.id_horario
                    INNER JOIN actividad a
                        ON ha.id_actividad = a.id_actividad
                    LEFT JOIN sala s
                        ON ha.id_sala = s.id_sala
                    WHERE (
                        :busqueda = ''
                        OR u.nombre LIKE :like_busqueda
                        OR u.apellidos LIKE :like_busqueda
                        OR a.nombre LIKE :like_busqueda
                    )
                    AND (:estado = '' OR r.estado = :estado)
                    AND (:fecha = '' OR sa.fecha = :fecha)
                    ORDER BY sa.fecha DESC, ha.hora_inicio DESC, r.id_reserva DESC";

    $stmtReservas = $conn->prepare($sqlReservas);
    $stmtReservas->execute([
        ':busqueda' => $busqueda,
        ':like_busqueda' => '%' . $busqueda . '%',
        ':estado' => $estado,
        ':fecha' => $fecha
    ]);
    $reservas = $stmtReservas->fetchAll(PDO::FETCH_ASSOC);

    $listaReservas = [];
    foreach ($reservas as $item) {
        $listaReservas[] = [
            'id_reserva' => (int)$item['id_reserva'],
            'id_usuario' => (int)$item['id_usuario'],
            'usuario' => $item['usuario'] ?? '',
            'actividad' => $item['actividad'] ?? '',
            'fecha' => !empty($item['fecha']) ? date('d/m/Y', strtotime($item['fecha'])) : 'No disponible',
            'horario' => substr($item['hora_inicio'], 0, 5) . ' - ' . substr($item['hora_fin'], 0, 5),
            'sala' => $item['sala'] ?? 'Sin sala',
            'instructor' => !empty($item['instructor']) ? $item['instructor'] : 'No asignado',
            'estado' => $item['estado'] ?? 'No disponible'
        ];
    }

    $sqlResumen = "SELECT
                        SUM(CASE WHEN r.estado = 'Confirmada' AND sa.fecha = CURDATE() THEN 1 ELSE 0 END) AS confirmadas,
                        SUM(CASE WHEN r.estado = 'Asistida' AND sa.fecha = CURDATE() THEN 1 ELSE 0 END) AS asistidas,
                        SUM(CASE WHEN r.estado = 'Cancelada' AND sa.fecha = CURDATE() THEN 1 ELSE 0 END) AS canceladas,
                        SUM(CASE WHEN r.estado = 'No asistida' AND sa.fecha = CURDATE() THEN 1 ELSE 0 END) AS no_asistidas
                    FROM reserva r
                    INNER JOIN sesion_actividad sa
                        ON r.id_sesion = sa.id_sesion";

    $resumen = $conn->query($sqlResumen)->fetch(PDO::FETCH_ASSOC);

    $fotoAdmin = !empty($admin['foto_perfil']) ? $admin['foto_perfil'] : '../img/athena_logo.png';
    $nombreAdmin = trim(($admin['nombre'] ?? '') . ' ' . ($admin['apellidos'] ?? ''));

    responderJSON([
        'ok' => true,
        'admin' => [
            'foto_perfil' => $fotoAdmin,
            'nombre_completo' => $nombreAdmin !== '' ? $nombreAdmin : 'Administrador ATHENA',
            'perfil' => 'Perfil ADMIN'
        ],
        'reservas' => $listaReservas,
        'resumen' => [
            'confirmadas' => (int)($resumen['confirmadas'] ?? 0),
            'asistidas' => (int)($resumen['asistidas'] ?? 0),
            'canceladas' => (int)($resumen['canceladas'] ?? 0),
            'no_asistidas' => (int)($resumen['no_asistidas'] ?? 0)
        ]
    ]);
} catch (PDOException $e) {
    responderJSON([
        'ok' => false,
        'mensaje' => 'Error al obtener las reservas.',
        'error' => $e->getMessage()
    ]);
}
