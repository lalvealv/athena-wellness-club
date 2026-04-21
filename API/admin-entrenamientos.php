<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion.php';

$idAdmin = (int)($_SESSION['id_usuario'] ?? 0);
$busqueda = trim($_GET['buscar'] ?? '');
$rutina = trim($_GET['rutina'] ?? '');
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

    if (isset($_GET['accion']) && $_GET['accion'] === 'usuarios') {
        $sqlUsuarios = "SELECT id_usuario, CONCAT(nombre, ' ', apellidos) AS nombre_completo
                        FROM usuario
                        WHERE id_perfil = 2
                        ORDER BY nombre, apellidos";

        $usuarios = $conn->query($sqlUsuarios)->fetchAll(PDO::FETCH_ASSOC);

        responderJSON([
            'ok' => true,
            'usuarios' => $usuarios
        ]);
    }

    if (isset($_GET['accion']) && $_GET['accion'] === 'detalle') {
        $idEntrenamiento = (int)($_GET['id_entrenamiento'] ?? 0);

        if ($idEntrenamiento <= 0) {
            responderJSON([
                'ok' => false,
                'mensaje' => 'ID de entrenamiento no válido.'
            ]);
        }

        $sqlEntrenamiento = "SELECT id_entrenamiento, id_usuario, fecha, duracion_minutos, estado, observaciones
                             FROM entrenamiento
                             WHERE id_entrenamiento = :id_entrenamiento
                             LIMIT 1";

        $stmtEntrenamiento = $conn->prepare($sqlEntrenamiento);
        $stmtEntrenamiento->execute([
            ':id_entrenamiento' => $idEntrenamiento
        ]);
        $entrenamiento = $stmtEntrenamiento->fetch(PDO::FETCH_ASSOC);

        if (!$entrenamiento) {
            responderJSON([
                'ok' => false,
                'mensaje' => 'Entrenamiento no encontrado.'
            ]);
        }

        $sqlDetalle = "SELECT id_detalle, ejercicio, series, repeticiones, peso
                       FROM detalle_entrenamiento
                       WHERE id_entrenamiento = :id_entrenamiento
                       ORDER BY id_detalle ASC";

        $stmtDetalle = $conn->prepare($sqlDetalle);
        $stmtDetalle->execute([
            ':id_entrenamiento' => $idEntrenamiento
        ]);
        $ejercicios = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ejercicios as &$ejercicio) {
            $ejercicio['peso_num'] = $ejercicio['peso'];
        }

        responderJSON([
            'ok' => true,
            'entrenamiento' => $entrenamiento,
            'ejercicios' => $ejercicios
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        $accion = $input['accion'] ?? '';

        if ($accion === 'eliminar') {
            $idEntrenamiento = (int)($input['id_entrenamiento'] ?? 0);

            if ($idEntrenamiento <= 0) {
                responderJSON([
                    'ok' => false,
                    'mensaje' => 'ID de entrenamiento no válido.'
                ]);
            }

            $sqlEliminar = "DELETE FROM entrenamiento
                            WHERE id_entrenamiento = :id_entrenamiento";

            $stmtEliminar = $conn->prepare($sqlEliminar);
            $stmtEliminar->execute([
                ':id_entrenamiento' => $idEntrenamiento
            ]);

            responderJSON([
                'ok' => true,
                'mensaje' => 'Entrenamiento eliminado correctamente.'
            ]);
        }

        if ($accion === 'crear' || $accion === 'editar') {
            $idEntrenamiento = (int)($input['id_entrenamiento'] ?? 0);
            $idUsuario = (int)($input['id_usuario'] ?? 0);
            $fechaForm = trim($input['fecha'] ?? '');
            $duracionMinutos = (int)($input['duracion_minutos'] ?? 0);
            $estadoForm = trim($input['estado'] ?? 'Programado');
            $observaciones = trim($input['observaciones'] ?? '');
            $ejercicios = $input['ejercicios'] ?? [];

            if ($idUsuario <= 0 || $fechaForm === '' || $duracionMinutos <= 0) {
                responderJSON([
                    'ok' => false,
                    'mensaje' => 'Datos básicos del entrenamiento no válidos.'
                ]);
            }

            if (!in_array($estadoForm, ['Programado', 'Completado'], true)) {
                responderJSON([
                    'ok' => false,
                    'mensaje' => 'Estado del entrenamiento no válido.'
                ]);
            }

            if (!is_array($ejercicios) || count($ejercicios) === 0) {
                responderJSON([
                    'ok' => false,
                    'mensaje' => 'Debes indicar al menos un ejercicio.'
                ]);
            }

            $conn->beginTransaction();

            if ($accion === 'crear') {
                $sqlInsert = "INSERT INTO entrenamiento (id_usuario, fecha, duracion_minutos, estado, observaciones)
                              VALUES (:id_usuario, :fecha, :duracion_minutos, :estado, :observaciones)";

                $stmtInsert = $conn->prepare($sqlInsert);
                $stmtInsert->execute([
                    ':id_usuario' => $idUsuario,
                    ':fecha' => $fechaForm,
                    ':duracion_minutos' => $duracionMinutos,
                    ':estado' => $estadoForm,
                    ':observaciones' => $observaciones !== '' ? $observaciones : null
                ]);

                $idEntrenamiento = (int)$conn->lastInsertId();
            } else {
                if ($idEntrenamiento <= 0) {
                    $conn->rollBack();
                    responderJSON([
                        'ok' => false,
                        'mensaje' => 'ID de entrenamiento no válido.'
                    ]);
                }

                $sqlUpdate = "UPDATE entrenamiento
                              SET id_usuario = :id_usuario,
                                  fecha = :fecha,
                                  duracion_minutos = :duracion_minutos,
                                  estado = :estado,
                                  observaciones = :observaciones
                              WHERE id_entrenamiento = :id_entrenamiento";

                $stmtUpdate = $conn->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    ':id_usuario' => $idUsuario,
                    ':fecha' => $fechaForm,
                    ':duracion_minutos' => $duracionMinutos,
                    ':estado' => $estadoForm,
                    ':observaciones' => $observaciones !== '' ? $observaciones : null,
                    ':id_entrenamiento' => $idEntrenamiento
                ]);

                $sqlDeleteDetalle = "DELETE FROM detalle_entrenamiento
                                     WHERE id_entrenamiento = :id_entrenamiento";

                $stmtDeleteDetalle = $conn->prepare($sqlDeleteDetalle);
                $stmtDeleteDetalle->execute([
                    ':id_entrenamiento' => $idEntrenamiento
                ]);
            }

            $sqlInsertDetalle = "INSERT INTO detalle_entrenamiento
                                 (id_entrenamiento, ejercicio, series, repeticiones, peso)
                                 VALUES
                                 (:id_entrenamiento, :ejercicio, :series, :repeticiones, :peso)";

            $stmtInsertDetalle = $conn->prepare($sqlInsertDetalle);

            foreach ($ejercicios as $ejercicio) {
                $nombreEjercicio = trim($ejercicio['ejercicio'] ?? '');

                if ($nombreEjercicio === '') {
                    continue;
                }

                $series = isset($ejercicio['series']) && $ejercicio['series'] !== '' ? (int)$ejercicio['series'] : null;
                $repeticiones = isset($ejercicio['repeticiones']) && $ejercicio['repeticiones'] !== '' ? (int)$ejercicio['repeticiones'] : null;
                $peso = isset($ejercicio['peso']) && $ejercicio['peso'] !== '' ? (float)$ejercicio['peso'] : null;

                $stmtInsertDetalle->execute([
                    ':id_entrenamiento' => $idEntrenamiento,
                    ':ejercicio' => $nombreEjercicio,
                    ':series' => $series,
                    ':repeticiones' => $repeticiones,
                    ':peso' => $peso
                ]);
            }

            $conn->commit();

            responderJSON([
                'ok' => true,
                'mensaje' => $accion === 'crear'
                    ? 'Entrenamiento creado correctamente.'
                    : 'Entrenamiento actualizado correctamente.'
            ]);
        }

        responderJSON([
            'ok' => false,
            'mensaje' => 'Acción no válida.'
        ]);
    }

    $sqlEntrenamientos = "SELECT
                            e.id_entrenamiento,
                            u.id_usuario,
                            CONCAT(u.nombre, ' ', u.apellidos) AS usuario,
                            e.fecha,
                            e.duracion_minutos,
                            e.estado,
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
            'id_entrenamiento' => (int)$item['id_entrenamiento'],
            'id_usuario' => (int)$item['id_usuario'],
            'usuario' => $item['usuario'] ?? '',
            'rutina' => !empty($item['observaciones']) ? $item['observaciones'] : 'Sin especificar',
            'fecha' => !empty($item['fecha']) ? date('d/m/Y', strtotime($item['fecha'])) : 'No disponible',
            'duracion' => !empty($item['duracion_minutos']) ? $item['duracion_minutos'] . ' min' : 'No disponible',
            'estado' => $item['estado'] ?? 'Programado',
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
                ? $primerEntrenamiento['duracion_minutos'] . ' min · ' . ($primerEntrenamiento['estado'] ?? 'Programado')
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
    $totalSemana = (int)$conn->query($sqlResumenSemana)->fetchColumn();

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
    $usuariosActivos = (int)$conn->query($sqlUsuariosActivos)->fetchColumn();

    $sqlSeguimientoBajo = "SELECT COUNT(*)
                           FROM usuario u
                           WHERE u.id_perfil = 2
                             AND NOT EXISTS (
                                 SELECT 1
                                 FROM entrenamiento e
                                 WHERE e.id_usuario = u.id_usuario
                                   AND e.fecha >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
                             )";
    $seguimientoBajo = (int)$conn->query($sqlSeguimientoBajo)->fetchColumn();

    $fotoAdmin = !empty($admin['foto_perfil']) ? $admin['foto_perfil'] : '../img/athena_logo.png';
    $nombreAdmin = trim(($admin['nombre'] ?? '') . ' ' . ($admin['apellidos'] ?? ''));

    responderJSON([
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
    ]);
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    responderJSON([
        'ok' => false,
        'mensaje' => 'Error al obtener los entrenamientos.',
        'error' => $e->getMessage()
    ]);
}
