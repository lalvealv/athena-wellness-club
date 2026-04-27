<?php
// Comprueba que el usuario ha iniciado sesión
require_once __DIR__ . '/../comprobar-login.php';

// Importa la conexión a la base de datos
require_once __DIR__ . '/../conexion.php';

// Indica que la respuesta será JSON
header('Content-Type: application/json; charset=utf-8');

// Obtiene el ID del usuario logueado
$idUsuario = (int)$_SESSION['id_usuario'];

// Función reutilizable para devolver respuestas JSON
function responderJSON(array $datos): void
{
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

// Devuelve la semana y el año actuales en formato ISO
function obtenerSemanaActual(): array
{
    return [
        'semana' => (int)date('W'),
        'anio' => (int)date('o')
    ];
}

try {
    // PETICIONES POST: acciones del usuario sobre entrenamientos
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $accion = $_POST['accion'] ?? '';

        // Acción: marcar entrenamiento como completado
        if ($accion === 'marcar_completado') {
            $idEntrenamiento = isset($_POST['id_entrenamiento']) ? (int)$_POST['id_entrenamiento'] : 0;

            // Valida el ID recibido
            if ($idEntrenamiento <= 0) {
                responderJSON([
                    'ok' => false,
                    'mensaje' => 'Datos no válidos.'
                ]);
            }

            // Comprueba que el entrenamiento pertenece al usuario
            $sqlComprobar = "SELECT id_entrenamiento, estado
                             FROM entrenamiento
                             WHERE id_entrenamiento = :id_entrenamiento
                               AND id_usuario = :id_usuario
                             LIMIT 1";

            $stmtComprobar = $conn->prepare($sqlComprobar);
            $stmtComprobar->execute([
                ':id_entrenamiento' => $idEntrenamiento,
                ':id_usuario' => $idUsuario
            ]);

            $entrenamiento = $stmtComprobar->fetch(PDO::FETCH_ASSOC);

            // Si no existe o no pertenece al usuario, devuelve error
            if (!$entrenamiento) {
                responderJSON([
                    'ok' => false,
                    'mensaje' => 'Entrenamiento no encontrado.'
                ]);
            }

            // Evita marcar dos veces el mismo entrenamiento
            if (($entrenamiento['estado'] ?? '') === 'Completado') {
                responderJSON([
                    'ok' => false,
                    'mensaje' => 'Este entrenamiento ya estaba completado.'
                ]);
            }

            // Actualiza el estado a Completado
            $sqlActualizar = "UPDATE entrenamiento
                              SET estado = 'Completado'
                              WHERE id_entrenamiento = :id_entrenamiento
                                AND id_usuario = :id_usuario";

            $stmtActualizar = $conn->prepare($sqlActualizar);
            $stmtActualizar->execute([
                ':id_entrenamiento' => $idEntrenamiento,
                ':id_usuario' => $idUsuario
            ]);

            responderJSON([
                'ok' => true,
                'mensaje' => 'Entrenamiento marcado como completado.'
            ]);
        }

        // Acción: guardar objetivo semanal del usuario
        if ($accion === 'guardar_objetivo_semanal') {
            $objetivoTotal = isset($_POST['objetivo_total']) ? (int)$_POST['objetivo_total'] : 0;

            // Valida el objetivo semanal
            if ($objetivoTotal <= 0) {
                responderJSON([
                    'ok' => false,
                    'mensaje' => 'El objetivo semanal no es válido.'
                ]);
            }

            // Obtiene semana y año actuales
            $semanaActual = obtenerSemanaActual();

            // Comprueba si ya existe objetivo para esa semana
            $sqlExiste = "SELECT id_objetivo_semanal
                          FROM objetivo_entrenamiento_semanal
                          WHERE id_usuario = :id_usuario
                            AND semana = :semana
                            AND anio = :anio
                          LIMIT 1";

            $stmtExiste = $conn->prepare($sqlExiste);
            $stmtExiste->execute([
                ':id_usuario' => $idUsuario,
                ':semana' => $semanaActual['semana'],
                ':anio' => $semanaActual['anio']
            ]);

            $idObjetivo = $stmtExiste->fetchColumn();

            // Si existe, actualiza el objetivo semanal
            if ($idObjetivo) {
                $sqlUpdateObjetivo = "UPDATE objetivo_entrenamiento_semanal
                                      SET objetivo_total = :objetivo_total
                                      WHERE id_objetivo_semanal = :id_objetivo_semanal";

                $stmtUpdateObjetivo = $conn->prepare($sqlUpdateObjetivo);
                $stmtUpdateObjetivo->execute([
                    ':objetivo_total' => $objetivoTotal,
                    ':id_objetivo_semanal' => $idObjetivo
                ]);
            } else {
                // Si no existe, crea un nuevo objetivo semanal
                $sqlInsertObjetivo = "INSERT INTO objetivo_entrenamiento_semanal
                                      (id_usuario, semana, anio, objetivo_total)
                                      VALUES
                                      (:id_usuario, :semana, :anio, :objetivo_total)";

                $stmtInsertObjetivo = $conn->prepare($sqlInsertObjetivo);
                $stmtInsertObjetivo->execute([
                    ':id_usuario' => $idUsuario,
                    ':semana' => $semanaActual['semana'],
                    ':anio' => $semanaActual['anio'],
                    ':objetivo_total' => $objetivoTotal
                ]);
            }

            responderJSON([
                'ok' => true,
                'mensaje' => 'Objetivo semanal guardado correctamente.'
            ]);
        }

        // Si la acción POST no existe
        responderJSON([
            'ok' => false,
            'mensaje' => 'Acción no válida.'
        ]);
    }

    // PETICIÓN GET: detalle de un entrenamiento concreto
    if (isset($_GET['accion']) && $_GET['accion'] === 'detalle') {
        $idEntrenamiento = (int)($_GET['id_entrenamiento'] ?? 0);

        // Valida el ID
        if ($idEntrenamiento <= 0) {
            responderJSON([
                'ok' => false,
                'mensaje' => 'ID de entrenamiento no válido.'
            ]);
        }

        // Consulta el entrenamiento seleccionado del usuario
        $sqlDetalleEntrenamiento = "SELECT
                                        id_entrenamiento,
                                        fecha,
                                        duracion_minutos,
                                        estado,
                                        observaciones
                                    FROM entrenamiento
                                    WHERE id_entrenamiento = :id_entrenamiento
                                      AND id_usuario = :id_usuario
                                    LIMIT 1";

        $stmtDetalleEntrenamiento = $conn->prepare($sqlDetalleEntrenamiento);
        $stmtDetalleEntrenamiento->execute([
            ':id_entrenamiento' => $idEntrenamiento,
            ':id_usuario' => $idUsuario
        ]);

        $entrenamiento = $stmtDetalleEntrenamiento->fetch(PDO::FETCH_ASSOC);

        // Si no existe o no pertenece al usuario, devuelve error
        if (!$entrenamiento) {
            responderJSON([
                'ok' => false,
                'mensaje' => 'Entrenamiento no encontrado.'
            ]);
        }

        // Consulta los ejercicios del entrenamiento
        $sqlEjercicios = "SELECT ejercicio, series, repeticiones, peso
                          FROM detalle_entrenamiento
                          WHERE id_entrenamiento = :id_entrenamiento
                          ORDER BY id_detalle ASC";

        $stmtEjercicios = $conn->prepare($sqlEjercicios);
        $stmtEjercicios->execute([
            ':id_entrenamiento' => $idEntrenamiento
        ]);

        $ejercicios = $stmtEjercicios->fetchAll(PDO::FETCH_ASSOC);

        // Formatea los datos principales del entrenamiento
        $detalleFormateado = [
            'fecha' => !empty($entrenamiento['fecha']) ? date('d/m/Y', strtotime($entrenamiento['fecha'])) : 'No disponible',
            'subtexto' => !empty($entrenamiento['duracion_minutos']) ? $entrenamiento['duracion_minutos'] . ' min' : 'Duración no indicada',
            'estado' => $entrenamiento['estado'] ?? 'Programado',
            'observaciones' => !empty($entrenamiento['observaciones']) ? $entrenamiento['observaciones'] : 'Sin observaciones'
        ];

        // Formatea los ejercicios del entrenamiento
        $filas = [];
        foreach ($ejercicios as $ejercicio) {
            $filas[] = [
                'ejercicio' => $ejercicio['ejercicio'] ?? 'No disponible',
                'series' => $ejercicio['series'] ?? '—',
                'repeticiones' => $ejercicio['repeticiones'] ?? '—',
                'peso' => isset($ejercicio['peso']) && $ejercicio['peso'] !== null
                    ? number_format((float)$ejercicio['peso'], 2, ',', '.') . ' kg'
                    : '—'
            ];
        }

        // Devuelve detalle y ejercicios
        responderJSON([
            'ok' => true,
            'detalle' => $detalleFormateado,
            'ejercicios' => $filas
        ]);
    }

    // Consulta datos del usuario para el sidebar
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

    // Si no encuentra usuario, devuelve error
    if (!$usuario) {
        responderJSON([
            'ok' => false,
            'mensaje' => 'No se encontró la información del usuario.'
        ]);
    }

    // Consulta todos los entrenamientos del usuario
    $sqlEntrenamientos = "SELECT
                              id_entrenamiento,
                              fecha,
                              duracion_minutos,
                              estado,
                              observaciones
                          FROM entrenamiento
                          WHERE id_usuario = :id_usuario
                          ORDER BY fecha DESC, id_entrenamiento DESC";

    $stmtEntrenamientos = $conn->prepare($sqlEntrenamientos);
    $stmtEntrenamientos->execute([
        ':id_usuario' => $idUsuario
    ]);
    $entrenamientos = $stmtEntrenamientos->fetchAll(PDO::FETCH_ASSOC);

    // Consulta el último entrenamiento completado
    $sqlUltimoCompletado = "SELECT
                                id_entrenamiento,
                                fecha,
                                duracion_minutos,
                                estado,
                                observaciones
                            FROM entrenamiento
                            WHERE id_usuario = :id_usuario
                              AND estado = 'Completado'
                            ORDER BY fecha DESC, id_entrenamiento DESC
                            LIMIT 1";

    $stmtUltimoCompletado = $conn->prepare($sqlUltimoCompletado);
    $stmtUltimoCompletado->execute([
        ':id_usuario' => $idUsuario
    ]);
    $ultimoCompletado = $stmtUltimoCompletado->fetch(PDO::FETCH_ASSOC);

    // Consulta el próximo entrenamiento programado
    $sqlProximoProgramado = "SELECT
                                 id_entrenamiento,
                                 fecha,
                                 duracion_minutos,
                                 estado,
                                 observaciones
                             FROM entrenamiento
                             WHERE id_usuario = :id_usuario
                               AND estado = 'Programado'
                             ORDER BY fecha ASC, id_entrenamiento ASC
                             LIMIT 1";

    $stmtProximoProgramado = $conn->prepare($sqlProximoProgramado);
    $stmtProximoProgramado->execute([
        ':id_usuario' => $idUsuario
    ]);
    $proximoProgramado = $stmtProximoProgramado->fetch(PDO::FETCH_ASSOC);

    // Variables del detalle mostrado por defecto
    $detalleSeleccionado = null;
    $detalleEjercicios = [];

    // Prioriza mostrar próximo programado, luego último completado, luego el primero de la lista
    $entrenamientoBase = $proximoProgramado ?: $ultimoCompletado ?: ($entrenamientos[0] ?? null);

    // Si hay entrenamiento base, prepara su detalle
    if ($entrenamientoBase) {
        $detalleSeleccionado = [
            'fecha' => !empty($entrenamientoBase['fecha']) ? date('d/m/Y', strtotime($entrenamientoBase['fecha'])) : 'No disponible',
            'subtexto' => !empty($entrenamientoBase['duracion_minutos']) ? $entrenamientoBase['duracion_minutos'] . ' min' : 'Duración no indicada',
            'estado' => $entrenamientoBase['estado'] ?? 'Programado',
            'observaciones' => !empty($entrenamientoBase['observaciones']) ? $entrenamientoBase['observaciones'] : 'Sin observaciones'
        ];

        // Consulta ejercicios del entrenamiento base
        $sqlDetalle = "SELECT ejercicio, series, repeticiones, peso
                       FROM detalle_entrenamiento
                       WHERE id_entrenamiento = :id_entrenamiento
                       ORDER BY id_detalle ASC";

        $stmtDetalle = $conn->prepare($sqlDetalle);
        $stmtDetalle->execute([
            ':id_entrenamiento' => $entrenamientoBase['id_entrenamiento']
        ]);
        $ejercicios = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

        // Formatea ejercicios
        foreach ($ejercicios as $ejercicio) {
            $detalleEjercicios[] = [
                'ejercicio' => $ejercicio['ejercicio'] ?? 'No disponible',
                'series' => $ejercicio['series'] ?? '—',
                'repeticiones' => $ejercicio['repeticiones'] ?? '—',
                'peso' => isset($ejercicio['peso']) && $ejercicio['peso'] !== null
                    ? number_format((float)$ejercicio['peso'], 2, ',', '.') . ' kg'
                    : '—'
            ];
        }
    }

    // Obtiene semana y año actuales
    $semanaActual = obtenerSemanaActual();

    // Consulta el objetivo semanal guardado por el usuario
    $sqlObjetivoSemanal = "SELECT objetivo_total
                           FROM objetivo_entrenamiento_semanal
                           WHERE id_usuario = :id_usuario
                             AND semana = :semana
                             AND anio = :anio
                           LIMIT 1";

    $stmtObjetivoSemanal = $conn->prepare($sqlObjetivoSemanal);
    $stmtObjetivoSemanal->execute([
        ':id_usuario' => $idUsuario,
        ':semana' => $semanaActual['semana'],
        ':anio' => $semanaActual['anio']
    ]);

    // Si no hay objetivo semanal, se usa 4 por defecto
    $objetivoTotal = (int)$stmtObjetivoSemanal->fetchColumn();
    if ($objetivoTotal <= 0) {
        $objetivoTotal = 4;
    }

    // Cuenta entrenamientos completados esta semana
    $sqlCompletadosSemana = "SELECT COUNT(*)
                             FROM entrenamiento
                             WHERE id_usuario = :id_usuario
                               AND estado = 'Completado'
                               AND YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)";

    $stmtCompletadosSemana = $conn->prepare($sqlCompletadosSemana);
    $stmtCompletadosSemana->execute([
        ':id_usuario' => $idUsuario
    ]);

    $completadosSemana = (int)$stmtCompletadosSemana->fetchColumn();

    // Prepara datos del sidebar
    $fotoPerfil = !empty($usuario['foto_perfil']) ? $usuario['foto_perfil'] : '../img-socios/socio1.png';
    $nombreCompleto = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));
    $membresia = $usuario['membresia'] ?? 'Sin suscripción activa';

    // Valores por defecto de rutina actual
    $rutinaNombre = 'Sin entrenamientos programados';
    $rutinaDetalle = 'Todavía no tienes rutinas asignadas';

    // Si hay próximo entrenamiento, se muestra como rutina actual
    if ($proximoProgramado) {
        $rutinaNombre = 'Próximo entrenamiento programado';
        $rutinaDetalle = date('d/m/Y', strtotime($proximoProgramado['fecha'])) . ' · ' .
            (!empty($proximoProgramado['observaciones']) ? $proximoProgramado['observaciones'] : 'Sin observaciones');
    } elseif ($ultimoCompletado) {
        // Si no hay próximo, se muestra la última completada
        $rutinaNombre = 'Última rutina completada';
        $rutinaDetalle = date('d/m/Y', strtotime($ultimoCompletado['fecha'])) . ' · ' .
            (!empty($ultimoCompletado['observaciones']) ? $ultimoCompletado['observaciones'] : 'Sin observaciones');
    }

    // Valores por defecto del último entrenamiento
    $ultimoFecha = 'No disponible';
    $ultimoDetalle = 'Sin entrenamientos completados';

    // Si existe último completado, se formatea
    if ($ultimoCompletado) {
        $ultimoFecha = date('d/m/Y', strtotime($ultimoCompletado['fecha']));
        $ultimoDetalle = (!empty($ultimoCompletado['duracion_minutos']) ? $ultimoCompletado['duracion_minutos'] . ' min' : 'Duración no indicada')
            . ' · ' .
            (!empty($ultimoCompletado['observaciones']) ? $ultimoCompletado['observaciones'] : 'Sin observaciones');
    }

    // Formatea la lista de entrenamientos
    $listaEntrenamientos = [];
    foreach ($entrenamientos as $item) {
        $listaEntrenamientos[] = [
            'id_entrenamiento' => (int)$item['id_entrenamiento'],
            'fecha' => !empty($item['fecha']) ? date('d/m/Y', strtotime($item['fecha'])) : 'No disponible',
            'rutina' => !empty($item['observaciones']) ? $item['observaciones'] : 'Sin especificar',
            'duracion' => !empty($item['duracion_minutos']) ? $item['duracion_minutos'] . ' min' : 'No disponible',
            'estado' => $item['estado'] ?? 'Programado'
        ];
    }

    // Devuelve todos los datos al JavaScript
    responderJSON([
        'ok' => true,
        'sidebar' => [
            'foto_perfil' => $fotoPerfil,
            'nombre_completo' => $nombreCompleto !== '' ? $nombreCompleto : 'Usuario',
            'membresia' => $membresia
        ],
        'resumen' => [
            'rutina_nombre' => $rutinaNombre,
            'rutina_detalle' => $rutinaDetalle,
            'ultimo_fecha' => $ultimoFecha,
            'ultimo_detalle' => $ultimoDetalle
        ],
        'objetivo_semanal' => [
            'semana' => $semanaActual['semana'],
            'anio' => $semanaActual['anio'],
            'objetivo_total' => $objetivoTotal,
            'completados' => $completadosSemana
        ],
        'entrenamientos' => $listaEntrenamientos,
        'detalle_seleccionado' => $detalleSeleccionado,
        'detalle_ejercicios' => $detalleEjercicios
    ]);
} catch (PDOException $e) {
    // Devuelve error si falla la base de datos
    responderJSON([
        'ok' => false,
        'mensaje' => 'Error al obtener los entrenamientos.',
        'error' => $e->getMessage()
    ]);
}
