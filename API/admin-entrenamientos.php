<?php
require_once __DIR__ . '/../comprobar-admin.php';
require_once __DIR__ . '/../conexion.php';

header('Content-Type: application/json; charset=utf-8');

$idAdmin = $_SESSION['id_usuario'];
$busqueda = trim($_GET['buscar'] ?? '');
$rutina = trim($_GET['rutina'] ?? '');
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

    $sqlEntrenamientos = "SELECT
                            e.id_entrenamiento,
                            u.id_usuario,
                            CONCAT(u.nombre, ' ', u.apellidos) AS usuario,
                            e.fecha,
                            e.duracion_minutos,
                            e.observaciones
                          FROM entrenamiento e
                          INNER JOIN usuario u
                            ON e.id_usuario = u.id_usuario
                          WHERE (
                                :busqueda = ''
                                OR u.alias LIKE :like_busqueda
                                OR u.nombre LIKE :like_busqueda
                                OR u.apellidos LIKE :like_busqueda
                          )
                          AND (
                                :rutina = ''
                                OR e.observaciones LIKE :like_rutina
                          )
                          AND (
                                :fecha = ''
                                OR e.fecha = :fecha
                          )
                          ORDER BY e.fecha DESC, e.id_entrenamiento DESC";

    $stmtEntrenamientos = $conn->prepare($sqlEntrenamientos);
    $stmtEntrenamientos->execute([
        ':busqueda' => $busqueda,
        ':like_busqueda' => '%' . $busqueda . '%',
        ':rutina' => $rutina,
        ':like_rutina' => '%' . $rutina . '%',
        ':fecha' => $fecha
    ]);
    $entrenamientos = $stmtEntrenamientos->fetchAll(PDO::FETCH_ASSOC);

    $listaEntrenamientos = [];
    foreach ($entrenamientos as $item) {
        $listaEntrenamientos[] = [
            'id_entrenamiento' => $item['id_entrenamiento'],
            'id_usuario' => $item['id_usuario'],
            'usuario' => $item['usuario'] ?? '',
            'rutina' => !empty($item['observaciones']) ? $item['observaciones'] : 'Sin especificar',
            'fecha' => !empty($item['fecha']) ? date('d/m/Y', strtotime($item['fecha'])) : 'No disponible',
            'duracion' => !empty($item['duracion_minutos']) ? $item['duracion_minutos'] . ' min' : 'No disponible',
            'observaciones' => !empty($item['observaciones']) ? $item['observaciones'] : 'Sin observaciones'
        ];
    }

    $entrenamientoSeleccionado = null;
    $detalleSeleccionado = [];

    if (!empty($entrenamientos)) {
        $primerEntrenamiento = $entrenamientos[0];
        $entrenamientoSeleccionado = [
            'usuario' => $primerEntrenamiento['usuario'] ?? 'No disponible',
            'subtexto_usuario' => 'Último entrenamiento listado',
            'rutina' => !empty($primerEntrenamiento['observaciones']) ? $primerEntrenamiento['observaciones'] : 'Sin especificar',
            'subtexto_rutina' => !empty($primerEntrenamiento['duracion_minutos'])
                ? $primerEntrenamiento['duracion_minutos'] . ' min'
                : 'Duración no disponible'
        ];

        $sqlDetalle = "SELECT ejercicio, series, repeticiones, peso
                       FROM detalle_entrenamiento
                       WHERE id_entrenamiento = :id_entrenamiento
                       ORDER BY id_detalle ASC";

        $stmtDetalle = $conn->prepare($sqlDetalle);
        $stmtDetalle->execute([
            ':id_entrenamiento' => $primerEntrenamiento['id_entrenamiento']
        ]);
        $detalle = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

        foreach ($detalle as $fila) {
            $detalleSeleccionado[] = [
                'ejercicio' => $fila['ejercicio'] ?? 'No disponible',
                'series' => $fila['series'] ?? '—',
                'repeticiones' => $fila['repeticiones'] ?? '—',
                'peso' => isset($fila['peso']) && $fila['peso'] !== null
                    ? number_format((float)$fila['peso'], 2, ',', '.') . ' kg'
                    : '—'
            ];
        }
    }

    $sqlResumenSemana = "SELECT COUNT(*)
                         FROM entrenamiento
                         WHERE YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)";
    $totalSemana = (int) $conn->query($sqlResumenSemana)->fetchColumn();

    $sqlRutinaMasUsada = "SELECT observaciones, COUNT(*) AS total
                          FROM entrenamiento
                          WHERE observaciones IS NOT NULL
                            AND observaciones <> ''
                          GROUP BY observaciones
                          ORDER BY total DESC
                          LIMIT 1";
    $rutinaMasUsada = $conn->query($sqlRutinaMasUsada)->fetch(PDO::FETCH_ASSOC);

    $sqlUsuariosActivos = "SELECT COUNT(DISTINCT id_usuario)
                           FROM entrenamiento
                           WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    $usuariosActivos = (int) $conn->query($sqlUsuariosActivos)->fetchColumn();

    $sqlSeguimientoBajo = "SELECT COUNT(*)
                           FROM usuario u
                           WHERE u.id_perfil = 2
                             AND NOT EXISTS (
                                 SELECT 1
                                 FROM entrenamiento e
                                 WHERE e.id_usuario = u.id_usuario
                                   AND e.fecha >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
                             )";
    $seguimientoBajo = (int) $conn->query($sqlSeguimientoBajo)->fetchColumn();

    $fotoAdmin = !empty($admin['foto_perfil']) ? $admin['foto_perfil'] : '../img/admin.jpg';
    $nombreAdmin = trim(($admin['nombre'] ?? '') . ' ' . ($admin['apellidos'] ?? ''));

    echo json_encode([
        'ok' => true,
        'admin' => [
            'foto_perfil' => $fotoAdmin,
            'nombre_completo' => $nombreAdmin !== '' ? $nombreAdmin : 'Administrador ATHENA',
            'perfil' => 'Perfil ADMIN'
        ],
        'entrenamientos' => $listaEntrenamientos,
        'detalle' => [
            'seleccionado' => $entrenamientoSeleccionado,
            'ejercicios' => $detalleSeleccionado
        ],
        'resumen' => [
            'entrenamientos_semana' => $totalSemana,
            'rutina_usada' => !empty($rutinaMasUsada['observaciones']) ? $rutinaMasUsada['observaciones'] : 'Sin datos',
            'usuarios_activos' => $usuariosActivos,
            'seguimiento_bajo' => $seguimientoBajo
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener los entrenamientos.',
        'error' => $e->getMessage()
    ]);
}
