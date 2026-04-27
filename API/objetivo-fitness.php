<?php
// Comprueba que el usuario ha iniciado sesión
require_once __DIR__ . '/../comprobar-login.php';

// Importa la conexión a la base de datos
require_once __DIR__ . '/../conexion.php';

// Indica que la respuesta será JSON
header('Content-Type: application/json; charset=utf-8');

// Obtiene el ID del usuario logueado
$idUsuario = $_SESSION['id_usuario'];

// Calcula el porcentaje de progreso del objetivo fitness
function calcularPorcentajeObjetivo(?string $fechaInicio, ?string $fechaFin, string $estado): int
{
    // Si el objetivo está completado, el progreso es 100%
    if ($estado === 'Completado') {
        return 100;
    }

    // Si faltan fechas, no se puede calcular progreso
    if (empty($fechaInicio) || empty($fechaFin)) {
        return 0;
    }

    // Convierte las fechas a timestamp
    $inicio = strtotime($fechaInicio);
    $fin = strtotime($fechaFin);
    $hoy = strtotime(date('Y-m-d'));

    // Valida que las fechas sean correctas
    if ($inicio === false || $fin === false || $fin <= $inicio) {
        return 0;
    }

    // Si el objetivo todavía no ha empezado
    if ($hoy <= $inicio) {
        return 0;
    }

    // Si ya se ha superado la fecha final
    if ($hoy >= $fin) {
        return 100;
    }

    // Calcula el porcentaje transcurrido del periodo
    $total = $fin - $inicio;
    $transcurrido = $hoy - $inicio;

    return (int) round(($transcurrido / $total) * 100);
}

try {
    // Consulta datos básicos del usuario para el sidebar
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

    // Si no se encuentra el usuario, devuelve error
    if (!$usuario) {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se encontró la información del usuario.'
        ]);
        exit;
    }

    // Consulta el objetivo fitness principal del usuario
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

    // Define foto de perfil o imagen por defecto
    $fotoPerfil = !empty($usuario['foto_perfil'])
        ? $usuario['foto_perfil']
        : '../img-socios/socio1.png';

    // Construye nombre completo y membresía
    $nombreCompleto = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));
    $membresia = $usuario['membresia'] ?? 'Sin suscripción activa';

    // Si no hay objetivo registrado, devuelve datos por defecto
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

    // Formatea fechas del objetivo
    $fechaInicio = !empty($objetivo['fecha_inicio']) ? date('d/m/Y', strtotime($objetivo['fecha_inicio'])) : 'No disponible';
    $fechaFin = !empty($objetivo['fecha_fin']) ? date('d/m/Y', strtotime($objetivo['fecha_fin'])) : 'Sin fecha fin';

    // Construye el texto del periodo
    $periodo = $fechaInicio . ' - ' . $fechaFin;

    // Calcula el progreso del objetivo
    $progreso = calcularPorcentajeObjetivo(
        $objetivo['fecha_inicio'] ?? null,
        $objetivo['fecha_fin'] ?? null,
        $objetivo['estado'] ?? ''
    );

    // Devuelve objetivo, sidebar y progreso al frontend
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
    // Devuelve error si falla la base de datos
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener el objetivo fitness.',
        'error' => $e->getMessage()
    ]);
}
