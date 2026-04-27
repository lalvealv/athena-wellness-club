<?php
// Inicia la sesión para comprobar el usuario logueado
session_start();

// Indica que la respuesta será JSON
header('Content-Type: application/json; charset=utf-8');

// Importa la conexión a la base de datos
require_once __DIR__ . '/../conexion.php';

// Importa y ejecuta la actualización automática de suscripciones
require_once __DIR__ . '/../actualizar-suscripciones.php';
actualizarSuscripcionesAutomaticamente($conn);

// Comprueba si existe una sesión válida
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Sesión no válida.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Obtiene el ID del usuario logueado
$idUsuario = (int) $_SESSION['id_usuario'];

// Función reutilizable para devolver respuestas JSON
function responderJSON(array $datos): void
{
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

// Formatea una fecha de base de datos a formato día/mes/año
function formatearFecha(?string $fecha): string
{
    if (!$fecha) {
        return 'No disponible';
    }

    return date('d/m/Y', strtotime($fecha));
}

// Obtiene los datos del sidebar del usuario
function obtenerSidebar(PDO $conn, int $idUsuario): array
{
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

    // Si no se encuentra usuario, devuelve datos por defecto
    if (!$usuario) {
        return [
            'foto_perfil' => '../img-socios/socio1.png',
            'nombre_completo' => 'Usuario',
            'membresia' => 'Sin suscripción activa'
        ];
    }

    // Define foto de perfil o imagen por defecto
    $fotoPerfil = !empty($usuario['foto_perfil'])
        ? $usuario['foto_perfil']
        : '../img-socios/socio1.png';

    // Construye el nombre completo
    $nombreCompleto = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));

    // Obtiene la membresía activa
    $membresia = $usuario['membresia'] ?? 'Sin suscripción activa';

    return [
        'foto_perfil' => $fotoPerfil,
        'nombre_completo' => $nombreCompleto !== '' ? $nombreCompleto : 'Usuario',
        'membresia' => $membresia
    ];
}

// Obtiene el historial de reservas del usuario
function obtenerHistorial(PDO $conn, int $idUsuario): array
{
    $sqlHistorial = "SELECT
                        r.id_reserva,
                        a.nombre AS actividad,
                        sa.fecha,
                        ha.hora_inicio,
                        ha.hora_fin,
                        s.nombre AS sala,
                        r.estado
                     FROM reserva r
                     INNER JOIN sesion_actividad sa
                        ON r.id_sesion = sa.id_sesion
                     INNER JOIN horario_actividad ha
                        ON sa.id_horario = ha.id_horario
                     INNER JOIN actividad a
                        ON ha.id_actividad = a.id_actividad
                     LEFT JOIN sala s
                        ON ha.id_sala = s.id_sala
                     WHERE r.id_usuario = :id_usuario
                     ORDER BY sa.fecha DESC, ha.hora_inicio DESC";

    $stmtHistorial = $conn->prepare($sqlHistorial);
    $stmtHistorial->execute([
        ':id_usuario' => $idUsuario
    ]);

    $historial = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);
    $filas = [];

    // Recorre las reservas y las formatea para el frontend
    foreach ($historial as $item) {
        // Calcula la fecha y hora exacta de la clase
        $timestampClase = strtotime($item['fecha'] . ' ' . $item['hora_inicio']);
        $timestampAhora = time();

        // Por defecto no se puede cancelar
        $puedeCancelar = false;

        // Permite cancelar solo si está confirmada y falta más de 1 hora
        if (
            $item['estado'] === 'Confirmada' &&
            ($timestampClase - $timestampAhora) > 3600
        ) {
            $puedeCancelar = true;
        }

        $filas[] = [
            'id_reserva' => (int) $item['id_reserva'],
            'actividad' => $item['actividad'],
            'fecha' => formatearFecha($item['fecha']),
            'hora' => substr($item['hora_inicio'], 0, 5) . ' - ' . substr($item['hora_fin'], 0, 5),
            'sala' => $item['sala'] ?? 'Sin sala',
            'estado' => $item['estado'],
            'puede_cancelar' => $puedeCancelar
        ];
    }

    return $filas;
}

// Cancela una reserva del usuario si cumple las condiciones
function cancelarReserva(PDO $conn, int $idUsuario, int $idReserva): array
{
    try {
        // Inicia transacción para evitar cambios inconsistentes
        $conn->beginTransaction();

        // Busca la reserva del usuario y la bloquea durante la operación
        $sqlReserva = "SELECT
                            r.id_reserva,
                            r.estado,
                            sa.fecha,
                            ha.hora_inicio
                       FROM reserva r
                       INNER JOIN sesion_actividad sa
                            ON r.id_sesion = sa.id_sesion
                       INNER JOIN horario_actividad ha
                            ON sa.id_horario = ha.id_horario
                       WHERE r.id_reserva = :id_reserva
                         AND r.id_usuario = :id_usuario
                       LIMIT 1
                       FOR UPDATE";

        $stmtReserva = $conn->prepare($sqlReserva);
        $stmtReserva->execute([
            ':id_reserva' => $idReserva,
            ':id_usuario' => $idUsuario
        ]);

        $reserva = $stmtReserva->fetch(PDO::FETCH_ASSOC);

        // Si no existe, se cancela la operación
        if (!$reserva) {
            $conn->rollBack();
            return [
                'ok' => false,
                'mensaje' => 'La reserva no existe.'
            ];
        }

        // Solo se pueden cancelar reservas confirmadas
        if ($reserva['estado'] !== 'Confirmada') {
            $conn->rollBack();
            return [
                'ok' => false,
                'mensaje' => 'Solo se pueden cancelar reservas confirmadas.'
            ];
        }

        // Calcula cuánto falta para la clase
        $timestampClase = strtotime($reserva['fecha'] . ' ' . $reserva['hora_inicio']);
        $timestampAhora = time();

        // No permite cancelar con menos de 1 hora de antelación
        if (($timestampClase - $timestampAhora) <= 3600) {
            $conn->rollBack();
            return [
                'ok' => false,
                'mensaje' => 'No puedes cancelar la reserva con menos de 1 hora de antelación.'
            ];
        }

        // Actualiza el estado de la reserva a Cancelada
        $sqlUpdate = "UPDATE reserva
                      SET estado = 'Cancelada'
                      WHERE id_reserva = :id_reserva";

        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':id_reserva' => $idReserva
        ]);

        // Confirma la cancelación
        $conn->commit();

        return [
            'ok' => true,
            'mensaje' => 'Reserva cancelada correctamente.'
        ];
    } catch (PDOException $e) {
        // Si hay error, deshace la transacción
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        return [
            'ok' => false,
            'mensaje' => 'Error al cancelar la reserva: ' . $e->getMessage()
        ];
    }
}

try {
    // PETICIÓN GET: devuelve sidebar e historial de reservas
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sidebar = obtenerSidebar($conn, $idUsuario);
        $historial = obtenerHistorial($conn, $idUsuario);

        responderJSON([
            'ok' => true,
            'sidebar' => $sidebar,
            'historial' => $historial
        ]);
    }

    // PETICIÓN POST: cancela una reserva
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $accion = $_POST['accion'] ?? '';

        // Valida la acción recibida
        if ($accion !== 'cancelar') {
            responderJSON([
                'ok' => false,
                'mensaje' => 'Acción no válida.'
            ]);
        }

        // Recoge el ID de la reserva
        $idReserva = isset($_POST['id_reserva']) ? (int) $_POST['id_reserva'] : 0;

        // Valida el ID de reserva
        if ($idReserva <= 0) {
            responderJSON([
                'ok' => false,
                'mensaje' => 'Reserva no válida.'
            ]);
        }

        // Cancela la reserva y devuelve el resultado
        $resultado = cancelarReserva($conn, $idUsuario, $idReserva);
        responderJSON($resultado);
    }

    // Si llega otro método HTTP, devuelve error
    responderJSON([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);
} catch (PDOException $e) {
    // Devuelve error si falla la consulta principal
    responderJSON([
        'ok' => false,
        'mensaje' => 'Error al obtener el historial de reservas: ' . $e->getMessage()
    ]);
}
