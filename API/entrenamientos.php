<?php
require_once __DIR__ . '/../comprobar-login.php';
require_once __DIR__ . '/../conexion.php';

header('Content-Type: application/json; charset=utf-8');

$idUsuario = (int)$_SESSION['id_usuario'];

function responderJSON(array $datos): void
{
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function obtenerSemanaActual(): array
{
    return [
        'semana' => (int)date('W'),
        'anio' => (int)date('o')
    ];
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'marcar_completado') {
            $idEntrenamiento = isset($_POST['id_entrenamiento']) ? (int)$_POST['id_entrenamiento'] : 0;

            if ($idEntrenamiento <= 0) {
                responderJSON([
                    'ok' => false,
                    'mensaje' => 'Datos no válidos.'
                ]);
            }

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

            if (!$entrenamiento) {
                responderJSON([
                    'ok' => false,
                    'mensaje' => 'Entrenamiento no encontrado.'
                ]);
            }

            if (($entrenamiento['estado'] ?? '') === 'Completado') {
                responderJSON([
                    'ok' => false,
                    'mensaje' => 'Este entrenamiento ya estaba completado.'
                ]);
            }

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

        if ($accion === 'guardar_objetivo_semanal') {
            $objetivoTotal = isset($_POST['objetivo_total']) ? (int)$_POST['objetivo_total'] : 0;

            if ($objetivoTotal <= 0) {
                responderJSON([
                    'ok' => false,
                    'mensaje' => 'El objetivo semanal no es válido.'
                ]);
            }

            $semanaActual = obtenerSemanaActual();

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

        responderJSON([
            'ok' => false,
            'mensaje' => 'Acción no válida.'
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

        if (!$entrenamiento) {
            responderJSON([
                'ok' => false,
                'mensaje' => 'Entrenamiento no encontrado.'
            ]);
        }

        $sqlEjercicios = "SELECT ejercicio, series, repeticiones, peso
                          FROM detalle_entrenamiento
                          WHERE id_entrenamiento = :id_entrenamiento
                          ORDER BY id_detalle ASC";

        $stmtEjercicios = $conn->prepare($sqlEjercicios);
        $stmtEjercicios->execute([
            ':id_entrenamiento' => $idEntrenamiento
        ]);

        $ejercicios = $stmtEjercicios->fetchAll(PDO::FETCH_ASSOC);

        $detalleFormateado = [
            'fecha' => !empty($entrenamiento['fecha']) ? date('d/m/Y', strtotime($entrenamiento['fecha'])) : 'No disponible',
            'subtexto' => !empty($entrenamiento['duracion_minutos']) ? $entrenamiento['duracion_minutos'] . ' min' : 'Duración no indicada',
            'estado' => $entrenamiento['estado'] ?? 'Programado',
            'observaciones' => !empty($entrenamiento['observaciones']) ? $entrenamiento['observaciones'] : 'Sin observaciones'
        ];

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

        responderJSON([
            'ok' => true,
            'detalle' => $detalleFormateado,
            'ejercicios' => $filas
        ]);
    }

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
        responderJSON([
            'ok' => false,
            'mensaje' => 'No se encontró la información del usuario.'
        ]);
    }

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

    $detalleSeleccionado = null;
    $detalleEjercicios = [];

    $entrenamientoBase = $proximoProgramado ?: $ultimoCompletado ?: ($entrenamientos[0] ?? null);

    if ($entrenamientoBase) {
        $detalleSeleccionado = [
            'fecha' => !empty($entrenamientoBase['fecha']) ? date('d/m/Y', strtotime($entrenamientoBase['fecha'])) : 'No disponible',
            'subtexto' => !empty($entrenamientoBase['duracion_minutos']) ? $entrenamientoBase['duracion_minutos'] . ' min' : 'Duración no indicada',
            'estado' => $entrenamientoBase['estado'] ?? 'Programado',
            'observaciones' => !empty($entrenamientoBase['observaciones']) ? $entrenamientoBase['observaciones'] : 'Sin observaciones'
        ];

        $sqlDetalle = "SELECT ejercicio, series, repeticiones, peso
                       FROM detalle_entrenamiento
                       WHERE id_entrenamiento = :id_entrenamiento
                       ORDER BY id_detalle ASC";

        $stmtDetalle = $conn->prepare($sqlDetalle);
        $stmtDetalle->execute([
            ':id_entrenamiento' => $entrenamientoBase['id_entrenamiento']
        ]);
        $ejercicios = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

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

    $semanaActual = obtenerSemanaActual();

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

    $objetivoTotal = (int)$stmtObjetivoSemanal->fetchColumn();
    if ($objetivoTotal <= 0) {
        $objetivoTotal = 4;
    }

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

    $fotoPerfil = !empty($usuario['foto_perfil']) ? $usuario['foto_perfil'] : '../img-socios/socio1.png';
    $nombreCompleto = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));
    $membresia = $usuario['membresia'] ?? 'Sin suscripción activa';

    $rutinaNombre = 'Sin entrenamientos programados';
    $rutinaDetalle = 'Todavía no tienes rutinas asignadas';

    if ($proximoProgramado) {
        $rutinaNombre = 'Próximo entrenamiento programado';
        $rutinaDetalle = date('d/m/Y', strtotime($proximoProgramado['fecha'])) . ' · ' .
            (!empty($proximoProgramado['observaciones']) ? $proximoProgramado['observaciones'] : 'Sin observaciones');
    } elseif ($ultimoCompletado) {
        $rutinaNombre = 'Última rutina completada';
        $rutinaDetalle = date('d/m/Y', strtotime($ultimoCompletado['fecha'])) . ' · ' .
            (!empty($ultimoCompletado['observaciones']) ? $ultimoCompletado['observaciones'] : 'Sin observaciones');
    }

    $ultimoFecha = 'No disponible';
    $ultimoDetalle = 'Sin entrenamientos completados';

    if ($ultimoCompletado) {
        $ultimoFecha = date('d/m/Y', strtotime($ultimoCompletado['fecha']));
        $ultimoDetalle = (!empty($ultimoCompletado['duracion_minutos']) ? $ultimoCompletado['duracion_minutos'] . ' min' : 'Duración no indicada')
            . ' · ' .
            (!empty($ultimoCompletado['observaciones']) ? $ultimoCompletado['observaciones'] : 'Sin observaciones');
    }

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
    responderJSON([
        'ok' => false,
        'mensaje' => 'Error al obtener los entrenamientos.',
        'error' => $e->getMessage()
    ]);
}
