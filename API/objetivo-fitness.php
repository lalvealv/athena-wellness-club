<?php
require_once __DIR__ . '/../comprobar-login.php';
require_once __DIR__ . '/../conexion.php';

header('Content-Type: application/json; charset=utf-8');

$idUsuario = $_SESSION['id_usuario'];

function calcularPorcentajeObjetivo(?string $fechaInicio, ?string $fechaFin, string $estado): int
{
    if ($estado === 'Completado') {
        return 100;
    }

    if (empty($fechaInicio) || empty($fechaFin)) {
        return 0;
    }

    $inicio = strtotime($fechaInicio);
    $fin = strtotime($fechaFin);
    $hoy = strtotime(date('Y-m-d'));

    if ($inicio === false || $fin === false || $fin <= $inicio) {
        return 0;
    }

    if ($hoy <= $inicio) {
        return 0;
    }

    if ($hoy >= $fin) {
        return 100;
    }

    $total = $fin - $inicio;
    $transcurrido = $hoy - $inicio;

    return (int) round(($transcurrido / $total) * 100);
}

try {
    // Sidebar
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

    // Objetivo fitness principal
    $sqlObjetivo = "SELECT
                        objetivo,
                        descripcion,
                        fecha_inicio,
                        fecha_fin,
                        estado
                    FROM objetivo_fitness
                    WHERE id_usuario = :id_usuario
                    ORDER BY
                        CASE estado
                            WHEN 'Activo' THEN 1
                            WHEN 'Pausado' THEN 2
                            WHEN 'Completado' THEN 3
                        END,
                        fecha_inicio DESC
                    LIMIT 1";

    $stmtObjetivo = $conn->prepare($sqlObjetivo);
    $stmtObjetivo->execute([
        ':id_usuario' => $idUsuario
    ]);
    $objetivo = $stmtObjetivo->fetch(PDO::FETCH_ASSOC);

    $fotoPerfil = !empty($usuario['foto_perfil'])
        ? $usuario['foto_perfil']
        : '../img-socios/socio1.png';

    $nombreCompleto = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));
    $membresia = $usuario['membresia'] ?? 'Sin suscripción activa';

    if (!$objetivo) {
        echo json_encode([
            'ok' => true,
            'sidebar' => [
                'foto_perfil' => $fotoPerfil,
                'nombre_completo' => $nombreCompleto !== '' ? $nombreCompleto : 'Usuario',
                'membresia' => $membresia
            ],
            'objetivo' => [
                'nombre' => 'Sin objetivo activo',
                'descripcion' => 'No tienes un objetivo fitness registrado.',
                'periodo' => 'No disponible',
                'estado' => 'No disponible',
                'progreso' => 0
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $fechaInicio = !empty($objetivo['fecha_inicio']) ? date('d/m/Y', strtotime($objetivo['fecha_inicio'])) : 'No disponible';
    $fechaFin = !empty($objetivo['fecha_fin']) ? date('d/m/Y', strtotime($objetivo['fecha_fin'])) : 'Sin fecha fin';

    $periodo = $fechaInicio . ' - ' . $fechaFin;
    $progreso = calcularPorcentajeObjetivo(
        $objetivo['fecha_inicio'] ?? null,
        $objetivo['fecha_fin'] ?? null,
        $objetivo['estado'] ?? ''
    );

    echo json_encode([
        'ok' => true,
        'sidebar' => [
            'foto_perfil' => $fotoPerfil,
            'nombre_completo' => $nombreCompleto !== '' ? $nombreCompleto : 'Usuario',
            'membresia' => $membresia
        ],
        'objetivo' => [
            'nombre' => $objetivo['objetivo'] ?? 'Sin objetivo',
            'descripcion' => $objetivo['descripcion'] ?? 'Sin descripción.',
            'periodo' => $periodo,
            'estado' => $objetivo['estado'] ?? 'No disponible',
            'progreso' => $progreso
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener el objetivo fitness.',
        'error' => $e->getMessage()
    ]);
}
