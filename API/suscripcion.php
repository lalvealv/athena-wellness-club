<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../actualizar-suscripciones.php';

actualizarSuscripcionesAutomaticamente($conn);

function responderJSON(array $datos): void
{
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function formatearFecha(?string $fecha): string
{
    if (!$fecha) {
        return 'No disponible';
    }

    $timestamp = strtotime($fecha);
    if (!$timestamp) {
        return $fecha;
    }

    return date('d/m/Y', $timestamp);
}

function obtenerDetallesMembresia(?string $membresia): array
{
    switch ($membresia) {
        case 'Essential Morning':
            return [
                'periodo' => 'Mensual',
                'horario' => 'Acceso de mañana',
                'descripcion' => 'Plan con acceso al club en horario de mañana y uso de las zonas principales.'
            ];

        case 'Essential':
            return [
                'periodo' => 'Mensual',
                'horario' => 'Horario general',
                'descripcion' => 'Plan básico con acceso al club y uso de instalaciones esenciales.'
            ];

        case 'Premium':
            return [
                'periodo' => 'Mensual',
                'horario' => 'Horario completo',
                'descripcion' => 'Plan premium con acceso completo, clases dirigidas y una experiencia más completa en el club.'
            ];

        case 'Executive':
            return [
                'periodo' => 'Mensual',
                'horario' => 'Horario completo prioritario',
                'descripcion' => 'Plan executive con servicios exclusivos, acceso prioritario y beneficios premium avanzados.'
            ];

        default:
            return [
                'periodo' => 'No disponible',
                'horario' => 'No disponible',
                'descripcion' => 'Sin descripción disponible.'
            ];
    }
}

if (!isset($_SESSION['id_usuario'])) {
    responderJSON([
        'ok' => false,
        'mensaje' => 'Sesión no válida.'
    ]);
}

$idUsuario = (int)$_SESSION['id_usuario'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "SELECT
                    u.nombre,
                    u.apellidos,
                    u.foto_perfil,
                    s.id_suscripcion,
                    s.fecha_inicio,
                    s.fecha_renovacion,
                    s.estado,
                    s.renovacion_automatica,
                    m.nombre AS membresia,
                    m.cuota
                FROM usuario u
                LEFT JOIN suscripcion s
                    ON u.id_usuario = s.id_usuario
                    AND s.estado IN ('Activa', 'Pausada', 'Cancelada', 'Finalizada')
                LEFT JOIN membresia m
                    ON s.id_membresia = m.id_membresia
                WHERE u.id_usuario = :id_usuario
                ORDER BY s.id_suscripcion DESC
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':id_usuario' => $idUsuario
        ]);

        $datos = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$datos) {
            responderJSON([
                'ok' => false,
                'mensaje' => 'No se ha encontrado la información del usuario.'
            ]);
        }

        $detallesMembresia = obtenerDetallesMembresia($datos['membresia'] ?? null);

        responderJSON([
            'ok' => true,
            'id_suscripcion' => !empty($datos['id_suscripcion']) ? (int)$datos['id_suscripcion'] : 0,
            'nombre' => $datos['nombre'] ?? '',
            'apellidos' => $datos['apellidos'] ?? '',
            'foto_perfil' => !empty($datos['foto_perfil']) ? $datos['foto_perfil'] : '../img-socios/socio1.png',
            'membresia' => $datos['membresia'] ?? 'Sin suscripción activa',
            'cuota' => isset($datos['cuota']) ? number_format((float)$datos['cuota'], 2, ',', '.') . ' €' : 'No disponible',
            'periodo' => $detallesMembresia['periodo'],
            'horario' => $detallesMembresia['horario'],
            'fecha_inicio' => formatearFecha($datos['fecha_inicio'] ?? null),
            'fecha_renovacion' => formatearFecha($datos['fecha_renovacion'] ?? null),
            'estado' => $datos['estado'] ?? 'Sin suscripción activa',
            'renovacion_automatica' => isset($datos['renovacion_automatica']) && (int)$datos['renovacion_automatica'] === 1 ? 'Sí' : 'No',
            'descripcion' => $detallesMembresia['descripcion']
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $accion = $_POST['accion'] ?? '';

        if ($accion !== 'cancelar') {
            responderJSON([
                'ok' => false,
                'mensaje' => 'Acción no válida.'
            ]);
        }

        $sqlSuscripcion = "SELECT id_suscripcion, estado
                           FROM suscripcion
                           WHERE id_usuario = :id_usuario
                             AND estado IN ('Activa', 'Pausada')
                           ORDER BY id_suscripcion DESC
                           LIMIT 1";

        $stmtSuscripcion = $conn->prepare($sqlSuscripcion);
        $stmtSuscripcion->execute([
            ':id_usuario' => $idUsuario
        ]);

        $suscripcion = $stmtSuscripcion->fetch(PDO::FETCH_ASSOC);

        if (!$suscripcion) {
            responderJSON([
                'ok' => false,
                'mensaje' => 'No tienes ninguna suscripción activa para cancelar.'
            ]);
        }

        $sqlCancelar = "UPDATE suscripcion
                        SET estado = 'Cancelada',
                            renovacion_automatica = 0
                        WHERE id_suscripcion = :id_suscripcion";

        $stmtCancelar = $conn->prepare($sqlCancelar);
        $stmtCancelar->execute([
            ':id_suscripcion' => $suscripcion['id_suscripcion']
        ]);

        responderJSON([
            'ok' => true,
            'mensaje' => 'Tu suscripción ha sido cancelada correctamente. No se realizará el siguiente cargo automático.'
        ]);
    }

    responderJSON([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);
} catch (PDOException $e) {
    responderJSON([
        'ok' => false,
        'mensaje' => 'Error al gestionar la suscripción.',
        'error' => $e->getMessage()
    ]);
}
