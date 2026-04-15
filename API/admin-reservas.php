<?php
require_once __DIR__ . '/../comprobar-admin.php';
require_once __DIR__ . '/../conexion.php';

header('Content-Type: application/json; charset=utf-8');

$idAdmin = $_SESSION['id_usuario'];
$busqueda = trim($_GET['buscar'] ?? '');
$estado = trim($_GET['estado'] ?? '');
$fecha = trim($_GET['fecha'] ?? '');

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
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se encontró el administrador logueado.'
        ]);
        exit;
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
            'id_reserva' => $item['id_reserva'],
            'id_usuario' => $item['id_usuario'],
            'usuario' => $item['usuario'] ?? '',
            'actividad' => $item['actividad'] ?? '',
            'fecha' => !empty($item['fecha']) ? date('d/m/Y', strtotime($item['fecha'])) : 'No disponible',
            'horario' => substr($item['hora_inicio'], 0, 5) . ' - ' . substr($item['hora_fin'], 0, 5),
            'sala' => $item['sala'] ?? 'Sin sala',
            'instructor' => $item['instructor'] ?? 'No asignado',
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

    $fotoAdmin = !empty($admin['foto_perfil']) ? $admin['foto_perfil'] : '../img/admin.jpg';
    $nombreAdmin = trim(($admin['nombre'] ?? '') . ' ' . ($admin['apellidos'] ?? ''));

    echo json_encode([
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
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener las reservas.',
        'error' => $e->getMessage()
    ]);
}
