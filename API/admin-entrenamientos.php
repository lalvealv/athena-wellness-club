<?php
// Inicia la sesión para comprobar si hay un administrador logueado
session_start();

// Indica que la respuesta será en formato JSON
header('Content-Type: application/json; charset=utf-8');

// Importa la conexión a la base de datos
require_once __DIR__ . '/../conexion.php';

// Recoge el ID del administrador desde la sesión
$idAdmin = (int)($_SESSION['id_usuario'] ?? 0);

// Recoge los filtros enviados por GET
$busqueda = trim($_GET['buscar'] ?? '');
$rutina = trim($_GET['rutina'] ?? '');
$fecha = trim($_GET['fecha'] ?? '');

// Comprueba si la sesión es válida
if ($idAdmin <= 0) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Sesión no válida.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Comprueba si el usuario logueado tiene perfil de administrador
if (!isset($_SESSION['id_perfil']) || (int)$_SESSION['id_perfil'] !== 1) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Acceso no autorizado.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Función reutilizable para devolver respuestas JSON
function responderJSON(array $datos): void
{
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Consulta los datos del administrador logueado
    $sqlAdmin = "SELECT nombre, apellidos, foto_perfil
                 FROM usuario
                 WHERE id_usuario = :id_usuario
                 LIMIT 1";

    $stmtAdmin = $conn->prepare($sqlAdmin);
    $stmtAdmin->execute([
        ':id_usuario' => $idAdmin
    ]);
    $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

    // Si no se encuentra el administrador, se devuelve error
    if (!$admin) {
        responderJSON([
            'ok' => false,
            'mensaje' => 'No se encontró el administrador logueado.'
        ]);
    }

    // ACCIÓN GET: cargar usuarios clientes para el select del formulario
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

    // ACCIÓN GET: cargar detalle de un entrenamiento concreto
    if (isset($_GET['accion']) && $_GET['accion'] === 'detalle') {
        $idEntrenamiento = (int)($_GET['id_entrenamiento'] ?? 0);

        // Valida el ID del entrenamiento
        if ($idEntrenamiento <= 0) {
            responderJSON([
                'ok' => false,
                'mensaje' => 'ID de entrenamiento no válido.'
            ]);
        }

        // Consulta los datos principales del entrenamiento
        $sqlEntrenamiento = "SELECT id_entrenamiento, id_usuario, fecha, duracion_minutos, estado, observaciones
                             FROM entrenamiento
                             WHERE id_entrenamiento = :id_entrenamiento
                             LIMIT 1";

        $stmtEntrenamiento = $conn->prepare($sqlEntrenamiento);
        $stmtEntrenamiento->execute([
            ':id_entrenamiento' => $idEntrenamiento
        ]);
        $entrenamiento = $stmtEntrenamiento->fetch(PDO::FETCH_ASSOC);

        // Si no existe, devuelve error
        if (!$entrenamiento) {
            responderJSON([
                'ok' => false,
                'mensaje' => 'Entrenamiento no encontrado.'
            ]);
        }

        // Consulta los ejercicios asociados a ese entrenamiento
        $sqlDetalle = "SELECT id_detalle, ejercicio, series, repeticiones, peso
                       FROM detalle_entrenamiento
                       WHERE id_entrenamiento = :id_entrenamiento
                       ORDER BY id_detalle ASC";

        $stmtDetalle = $conn->prepare($sqlDetalle);
        $stmtDetalle->execute([
            ':id_entrenamiento' => $idEntrenamiento
        ]);
        $ejercicios = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

        // Añade una copia del peso en formato numérico para usarlo en edición
        foreach ($ejercicios as &$ejercicio) {
            $ejercicio['peso_num'] = $ejercicio['peso'];
        }

        // Devuelve entrenamiento y ejercicios
        responderJSON([
            'ok' => true,
            'entrenamiento' => $entrenamiento,
            'ejercicios' => $ejercicios
        ]);
    }

    // PETICIONES POST: eliminar, crear o editar entrenamientos
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Lee el JSON recibido desde JavaScript
        $input = json_decode(file_get_contents('php://input'), true);

        // Acción solicitada
        $accion = $input['accion'] ?? '';

        // ACCIÓN: eliminar entrenamiento
        if ($accion === 'eliminar') {
            $idEntrenamiento = (int)($input['id_entrenamiento'] ?? 0);

            // Valida el ID
            if ($idEntrenamiento <= 0) {
                responderJSON([
                    'ok' => false,
                    'mensaje' => 'ID de entrenamiento no válido.'
                ]);
            }

            // Elimina el entrenamiento indicado
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

        // ACCIÓN: crear o editar entrenamiento
        if ($accion === 'crear' || $accion === 'editar') {
            // Recoge los datos principales del entrenamiento
            $idEntrenamiento = (int)($input['id_entrenamiento'] ?? 0);
            $idUsuario = (int)($input['id_usuario'] ?? 0);
            $fechaForm = trim($input['fecha'] ?? '');
            $duracionMinutos = (int)($input['duracion_minutos'] ?? 0);
            $estadoForm = trim($input['estado'] ?? 'Programado');
            $observaciones = trim($input['observaciones'] ?? '');
            $ejercicios = $input['ejercicios'] ?? [];

            // Valida los datos básicos
            if ($idUsuario <= 0 || $fechaForm === '' || $duracionMinutos <= 0) {
                responderJSON([
                    'ok' => false,
                    'mensaje' => 'Datos básicos del entrenamiento no válidos.'
                ]);
            }

            // Valida que el estado sea uno de los permitidos
            if (!in_array($estadoForm, ['Programado', 'Completado'], true)) {
                responderJSON([
                    'ok' => false,
                    'mensaje' => 'Estado del entrenamiento no válido.'
                ]);
            }

            // Valida que exista al menos un ejercicio
            if (!is_array($ejercicios) || count($ejercicios) === 0) {
                responderJSON([
                    'ok' => false,
                    'mensaje' => 'Debes indicar al menos un ejercicio.'
                ]);
            }

            // Inicia transacción para guardar entrenamiento y ejercicios juntos
            $conn->beginTransaction();

            // Si la acción es crear, inserta un nuevo entrenamiento
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

                // Guarda el ID del entrenamiento recién creado
                $idEntrenamiento = (int)$conn->lastInsertId();
            } else {
                // Si se edita, primero valida el ID del entrenamiento
                if ($idEntrenamiento <= 0) {
                    $conn->rollBack();
                    responderJSON([
                        'ok' => false,
                        'mensaje' => 'ID de entrenamiento no válido.'
                    ]);
                }

                // Actualiza los datos principales del entrenamiento
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

                // Borra los ejercicios anteriores para insertar los nuevos
                $sqlDeleteDetalle = "DELETE FROM detalle_entrenamiento
                                     WHERE id_entrenamiento = :id_entrenamiento";

                $stmtDeleteDetalle = $conn->prepare($sqlDeleteDetalle);
                $stmtDeleteDetalle->execute([
                    ':id_entrenamiento' => $idEntrenamiento
                ]);
            }

            // Prepara la inserción de ejercicios
            $sqlInsertDetalle = "INSERT INTO detalle_entrenamiento
                                 (id_entrenamiento, ejercicio, series, repeticiones, peso)
                                 VALUES
                                 (:id_entrenamiento, :ejercicio, :series, :repeticiones, :peso)";

            $stmtInsertDetalle = $conn->prepare($sqlInsertDetalle);

            // Recorre e inserta cada ejercicio
            foreach ($ejercicios as $ejercicio) {
                $nombreEjercicio = trim($ejercicio['ejercicio'] ?? '');

                // Si el nombre está vacío, se ignora esa fila
                if ($nombreEjercicio === '') {
                    continue;
                }

                // Convierte valores numéricos o permite NULL
                $series = isset($ejercicio['series']) && $ejercicio['series'] !== '' ? (int)$ejercicio['series'] : null;
                $repeticiones = isset($ejercicio['repeticiones']) && $ejercicio['repeticiones'] !== '' ? (int)$ejercicio['repeticiones'] : null;
                $peso = isset($ejercicio['peso']) && $ejercicio['peso'] !== '' ? (float)$ejercicio['peso'] : null;

                // Inserta el ejercicio
                $stmtInsertDetalle->execute([
                    ':id_entrenamiento' => $idEntrenamiento,
                    ':ejercicio' => $nombreEjercicio,
                    ':series' => $series,
                    ':repeticiones' => $repeticiones,
                    ':peso' => $peso
                ]);
            }

            // Confirma los cambios
            $conn->commit();

            responderJSON([
                'ok' => true,
                'mensaje' => $accion === 'crear'
                    ? 'Entrenamiento creado correctamente.'
                    : 'Entrenamiento actualizado correctamente.'
            ]);
        }

        // Si la acción no existe, devuelve error
        responderJSON([
            'ok' => false,
            'mensaje' => 'Acción no válida.'
        ]);
    }

    // Consulta principal de entrenamientos con filtros opcionales
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

    // Prepara la lista de entrenamientos para el frontend
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

    // Variables para mostrar por defecto el detalle del primer entrenamiento
    $entrenamientoSeleccionado = null;
    $detalleSeleccionado = [];

    // Si hay entrenamientos, selecciona el primero para mostrar su detalle
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

        // Consulta los ejercicios del primer entrenamiento listado
        $sqlDetalle = "SELECT ejercicio, series, repeticiones, peso
                       FROM detalle_entrenamiento
                       WHERE id_entrenamiento = :id_entrenamiento
                       ORDER BY id_detalle ASC";

        $stmtDetalle = $conn->prepare($sqlDetalle);
        $stmtDetalle->execute([
            ':id_entrenamiento' => $primerEntrenamiento['id_entrenamiento']
        ]);
        $detalle = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

        // Formatea el detalle de ejercicios
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

    // Cuenta entrenamientos de la semana actual
    $sqlResumenSemana = "SELECT COUNT(*)
                         FROM entrenamiento
                         WHERE YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)";
    $totalSemana = (int)$conn->query($sqlResumenSemana)->fetchColumn();

    // Obtiene la observación/rutina más repetida
    $sqlRutinaMasUsada = "SELECT observaciones, COUNT(*) AS total
                          FROM entrenamiento
                          WHERE observaciones IS NOT NULL
                            AND observaciones <> ''
                          GROUP BY observaciones
                          ORDER BY total DESC
                          LIMIT 1";
    $rutinaMasUsada = $conn->query($sqlRutinaMasUsada)->fetch(PDO::FETCH_ASSOC);

    // Cuenta usuarios que han entrenado en los últimos 7 días
    $sqlUsuariosActivos = "SELECT COUNT(DISTINCT id_usuario)
                           FROM entrenamiento
                           WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    $usuariosActivos = (int)$conn->query($sqlUsuariosActivos)->fetchColumn();

    // Cuenta usuarios cliente sin entrenamientos recientes en los últimos 14 días
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

    // Prepara datos del administrador para mostrarlos en la interfaz
    $fotoAdmin = !empty($admin['foto_perfil']) ? $admin['foto_perfil'] : '../img/athena_logo.png';
    $nombreAdmin = trim(($admin['nombre'] ?? '') . ' ' . ($admin['apellidos'] ?? ''));

    // Respuesta final con admin, tabla, detalle y resumen
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
    // Si hay una transacción activa, se deshacen los cambios
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Devuelve error en JSON
    responderJSON([
        'ok' => false,
        'mensaje' => 'Error al obtener los entrenamientos.',
        'error' => $e->getMessage()
    ]);
}
