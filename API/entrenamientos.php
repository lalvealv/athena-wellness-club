<?php
require_once __DIR__ . '/../comprobar-login.php';
require_once __DIR__ . '/../conexion.php';

header('Content-Type: application/json; charset=utf-8');

$idUsuario = $_SESSION['id_usuario'];

try {
    // Datos sidebar
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
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se encontró la información del usuario.'
        ]);
        exit;
    }

    // Último entrenamiento del usuario
    $sqlUltimoEntrenamiento = "SELECT 
                                   id_entrenamiento,
                                   fecha,
                                   duracion_minutos,
                                   observaciones
                               FROM entrenamiento
                               WHERE id_usuario = :id_usuario
                               ORDER BY fecha DESC, id_entrenamiento DESC
                               LIMIT 1";

    $stmtUltimoEntrenamiento = $conn->prepare($sqlUltimoEntrenamiento);
    $stmtUltimoEntrenamiento->execute([
        ':id_usuario' => $idUsuario
    ]);
    $ultimoEntrenamiento = $stmtUltimoEntrenamiento->fetch(PDO::FETCH_ASSOC);

    // Contar entrenamientos recientes para detalle rutina
    $sqlConteo = "SELECT COUNT(*)
                  FROM entrenamiento
                  WHERE id_usuario = :id_usuario
                    AND fecha >= DATE_SUB(CURDATE(), INTERVAL 28 DAY)";

    $stmtConteo = $conn->prepare($sqlConteo);
    $stmtConteo->execute([
        ':id_usuario' => $idUsuario
    ]);
    $entrenamientosUltimoMes = (int) $stmtConteo->fetchColumn();

    $detalleEjercicios = [];

    if ($ultimoEntrenamiento) {
        $sqlDetalle = "SELECT
                           ejercicio,
                           series,
                           repeticiones,
                           peso
                       FROM detalle_entrenamiento
                       WHERE id_entrenamiento = :id_entrenamiento
                       ORDER BY id_detalle ASC";

        $stmtDetalle = $conn->prepare($sqlDetalle);
        $stmtDetalle->execute([
            ':id_entrenamiento' => $ultimoEntrenamiento['id_entrenamiento']
        ]);
        $detalleEjercicios = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);
    }

    $fotoPerfil = !empty($usuario['foto_perfil'])
        ? $usuario['foto_perfil']
        : '../img-socios/socio1.png';

    $nombreCompleto = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));
    $membresia = $usuario['membresia'] ?? 'Sin suscripción activa';

    // Como en tu BD no existe tabla rutina, usamos un nombre descriptivo derivado
    $rutinaNombre = 'Sin entrenamientos registrados';
    $rutinaDetalle = 'Añade entrenamientos para ver tu progresión';

    if ($ultimoEntrenamiento) {
        $rutinaNombre = 'Rutina personal activa';
        $rutinaDetalle = $entrenamientosUltimoMes . ' entrenamiento(s) en los últimos 28 días';
    }

    $ultimoFecha = 'No disponible';
    $ultimoDetalle = 'Sin entrenamientos registrados';

    if ($ultimoEntrenamiento) {
        $ultimoFecha = date('d/m/Y', strtotime($ultimoEntrenamiento['fecha']));

        $duracion = !empty($ultimoEntrenamiento['duracion_minutos'])
            ? $ultimoEntrenamiento['duracion_minutos'] . ' min'
            : 'Duración no indicada';

        $observaciones = !empty($ultimoEntrenamiento['observaciones'])
            ? $ultimoEntrenamiento['observaciones']
            : 'Sin observaciones';

        $ultimoDetalle = $duracion . ' · ' . $observaciones;
    }

    $filas = [];
    foreach ($detalleEjercicios as $ejercicio) {
        $filas[] = [
            'ejercicio' => $ejercicio['ejercicio'] ?? 'No disponible',
            'series' => $ejercicio['series'] ?? '—',
            'repeticiones' => $ejercicio['repeticiones'] ?? '—',
            'peso' => isset($ejercicio['peso']) && $ejercicio['peso'] !== null
                ? number_format((float)$ejercicio['peso'], 2, ',', '.') . ' kg'
                : '—'
        ];
    }

    echo json_encode([
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
        'detalle' => $filas
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener los entrenamientos.',
        'error' => $e->getMessage()
    ]);
}
