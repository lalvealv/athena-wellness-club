<?php
require_once __DIR__ . '/../comprobar-login.php';
require_once __DIR__ . '/../conexion.php';

header('Content-Type: application/json; charset=utf-8');

$idUsuario = $_SESSION['id_usuario'];

try {
    $sql = "SELECT 
                u.nombre,
                u.apellidos,
                u.foto_perfil,
                s.fecha_inicio,
                s.fecha_renovacion,
                s.estado,
                s.renovacion_automatica,
                m.nombre AS membresia,
                m.cuota,
                m.horario,
                m.descripcion,
                p.nombre_periodo
            FROM usuario u
            LEFT JOIN suscripcion s 
                ON u.id_usuario = s.id_usuario AND s.estado = 'Activa'
            LEFT JOIN membresia m 
                ON s.id_membresia = m.id_membresia
            LEFT JOIN periodo p
                ON m.id_periodo = p.id_periodo
            WHERE u.id_usuario = :id_usuario
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':id_usuario' => $idUsuario
    ]);

    $suscripcion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$suscripcion) {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se encontraron datos de la suscripción.'
        ]);
        exit;
    }

    $fotoPerfil = !empty($suscripcion['foto_perfil'])
        ? $suscripcion['foto_perfil']
        : '../img-socios/socio1.png';

    echo json_encode([
        'ok' => true,
        'nombre' => $suscripcion['nombre'] ?? '',
        'apellidos' => $suscripcion['apellidos'] ?? '',
        'foto_perfil' => $fotoPerfil,
        'membresia' => $suscripcion['membresia'] ?? 'Sin suscripción activa',
        'cuota' => isset($suscripcion['cuota'])
            ? number_format((float)$suscripcion['cuota'], 2, ',', '.') . ' €/mes'
            : 'No disponible',
        'periodo' => $suscripcion['nombre_periodo'] ?? 'No disponible',
        'horario' => $suscripcion['horario'] ?? 'No disponible',
        'descripcion' => $suscripcion['descripcion'] ?? 'Sin descripción disponible.',
        'fecha_inicio' => !empty($suscripcion['fecha_inicio'])
            ? date('d/m/Y', strtotime($suscripcion['fecha_inicio']))
            : 'No disponible',
        'fecha_renovacion' => !empty($suscripcion['fecha_renovacion'])
            ? date('d/m/Y', strtotime($suscripcion['fecha_renovacion']))
            : 'No disponible',
        'estado' => $suscripcion['estado'] ?? 'No disponible',
        'renovacion_automatica' => isset($suscripcion['renovacion_automatica'])
            ? ((int)$suscripcion['renovacion_automatica'] === 1 ? 'Sí' : 'No')
            : 'No disponible'
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener la suscripción.',
        'error' => $e->getMessage()
    ]);
}
