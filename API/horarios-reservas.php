<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion.php';

require_once __DIR__ . '/../actualizar-suscripciones.php';
actualizarSuscripcionesAutomaticamente($conn);

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Sesión no válida.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idUsuario = (int) $_SESSION['id_usuario'];
$diasMostrados = 28;

function responderJSON(array $datos): void
{
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function formatearFecha(?string $fecha): string
{
    if (!$fecha) {
        return 'Sin fecha';
    }

    $timestamp = strtotime($fecha);
    if (!$timestamp) {
        return $fecha;
    }

    return date('d/m/Y', $timestamp);
}

function diaSemanaDesdeFecha(string $fecha): string
{
    $mapa = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miercoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sabado',
        7 => 'Domingo'
    ];

    $numero = (int) date('N', strtotime($fecha));
    return $mapa[$numero] ?? '';
}

function diaCortoDesdeFecha(string $fecha): string
{
    $mapa = [
        1 => 'Lun',
        2 => 'Mar',
        3 => 'Mié',
        4 => 'Jue',
        5 => 'Vie',
        6 => 'Sáb',
        7 => 'Dom'
    ];

    $numero = (int) date('N', strtotime($fecha));
    return $mapa[$numero] ?? '';
}

function esFechaHoraReservable(string $fecha, string $horaInicio): bool
{
    $timestampClase = strtotime($fecha . ' ' . $horaInicio);
    return $timestampClase > time();
}

function obtenerUsuarioSidebar(PDO $conn, int $idUsuario): array
{
    $sql = "SELECT 
                u.nombre,
                u.apellidos,
                u.foto_perfil,
                COALESCE(m.nombre, 'Sin membresía') AS membresia
            FROM usuario u
            LEFT JOIN suscripcion s
                ON u.id_usuario = s.id_usuario
                AND s.estado = 'Activa'
            LEFT JOIN membresia m
                ON s.id_membresia = m.id_membresia
            WHERE u.id_usuario = :id_usuario
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':id_usuario' => $idUsuario
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        return [
            'foto_perfil' => '../img-socios/socio1.png',
            'nombre_completo' => 'Usuario',
            'membresia' => 'Sin membresía'
        ];
    }

    $foto = !empty($usuario['foto_perfil'])
        ? $usuario['foto_perfil']
        : '../img-socios/socio1.png';

    return [
        'foto_perfil' => $foto,
        'nombre_completo' => trim($usuario['nombre'] . ' ' . $usuario['apellidos']),
        'membresia' => $usuario['membresia']
    ];
}

function obtenerResumen(PDO $conn, int $idUsuario): array
{
    $resumen = [
        'proxima_clase' => 'Sin reservas',
        'proxima_fecha' => 'No hay próximas sesiones',
        'reservas_activas' => 0,
        'ultima_reserva' => 'Sin reservas',
        'ultima_fecha' => 'No disponible',
        'sesiones_disponibles' => 0
    ];

    $sqlProxima = "SELECT 
                        a.nombre AS actividad,
                        sa.fecha,
                        h.hora_inicio
                   FROM reserva r
                   INNER JOIN sesion_actividad sa ON r.id_sesion = sa.id_sesion
                   INNER JOIN horario_actividad h ON sa.id_horario = h.id_horario
                   INNER JOIN actividad a ON h.id_actividad = a.id_actividad
                   WHERE r.id_usuario = :id_usuario
                     AND r.estado = 'Confirmada'
                     AND sa.estado = 'Programada'
                     AND TIMESTAMP(sa.fecha, h.hora_inicio) > NOW()
                   ORDER BY sa.fecha ASC, h.hora_inicio ASC
                   LIMIT 1";

    $stmtProxima = $conn->prepare($sqlProxima);
    $stmtProxima->execute([
        ':id_usuario' => $idUsuario
    ]);

    $proxima = $stmtProxima->fetch(PDO::FETCH_ASSOC);

    if ($proxima) {
        $resumen['proxima_clase'] = $proxima['actividad'];
        $resumen['proxima_fecha'] = formatearFecha($proxima['fecha']) . ' · ' . substr($proxima['hora_inicio'], 0, 5);
    }

    $sqlActivas = "SELECT COUNT(*)
                   FROM reserva r
                   INNER JOIN sesion_actividad sa ON r.id_sesion = sa.id_sesion
                   INNER JOIN horario_actividad h ON sa.id_horario = h.id_horario
                   WHERE r.id_usuario = :id_usuario
                     AND r.estado = 'Confirmada'
                     AND sa.estado = 'Programada'
                     AND TIMESTAMP(sa.fecha, h.hora_inicio) > NOW()";

    $stmtActivas = $conn->prepare($sqlActivas);
    $stmtActivas->execute([
        ':id_usuario' => $idUsuario
    ]);

    $resumen['reservas_activas'] = (int) $stmtActivas->fetchColumn();

    $sqlUltima = "SELECT 
                    a.nombre AS actividad,
                    r.fecha_reserva
                  FROM reserva r
                  INNER JOIN sesion_actividad sa ON r.id_sesion = sa.id_sesion
                  INNER JOIN horario_actividad h ON sa.id_horario = h.id_horario
                  INNER JOIN actividad a ON h.id_actividad = a.id_actividad
                  WHERE r.id_usuario = :id_usuario
                  ORDER BY r.fecha_reserva DESC
                  LIMIT 1";

    $stmtUltima = $conn->prepare($sqlUltima);
    $stmtUltima->execute([
        ':id_usuario' => $idUsuario
    ]);

    $ultima = $stmtUltima->fetch(PDO::FETCH_ASSOC);

    if ($ultima) {
        $resumen['ultima_reserva'] = $ultima['actividad'];
        $resumen['ultima_fecha'] = formatearFecha($ultima['fecha_reserva']);
    }

    return $resumen;
}

function obtenerHorariosBase(PDO $conn): array
{
    $sql = "SELECT
                h.id_horario,
                h.dia_semana,
                h.hora_inicio,
                h.hora_fin,
                a.nombre AS actividad,
                COALESCE(s.nombre, 'Por asignar') AS sala,
                COALESCE(s.capacidad, 20) AS capacidad
            FROM horario_actividad h
            INNER JOIN actividad a ON h.id_actividad = a.id_actividad
            LEFT JOIN sala s ON h.id_sala = s.id_sala
            WHERE h.activo = 1
              AND a.activa = 1
            ORDER BY h.hora_inicio ASC, h.hora_fin ASC, h.id_horario ASC";

    $stmt = $conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerSesionesRango(PDO $conn, int $idUsuario, string $fechaInicio, string $fechaFin): array
{
    $sql = "SELECT
                sa.id_sesion,
                sa.id_horario,
                sa.fecha,
                COALESCE(sa.instructor, 'Por confirmar') AS instructor,
                sa.plazas_totales,
                sa.estado AS estado_sesion,
                COALESCE(r.estado, '') AS estado_reserva,
                COALESCE(oc.ocupadas, 0) AS ocupadas,
                COALESCE(s.capacidad, sa.plazas_totales, 20) AS capacidad_real
            FROM sesion_actividad sa
            INNER JOIN horario_actividad h ON sa.id_horario = h.id_horario
            LEFT JOIN sala s ON h.id_sala = s.id_sala
            LEFT JOIN reserva r
                ON r.id_sesion = sa.id_sesion
               AND r.id_usuario = :id_usuario
            LEFT JOIN (
                SELECT id_sesion, COUNT(*) AS ocupadas
                FROM reserva
                WHERE estado = 'Confirmada'
                GROUP BY id_sesion
            ) oc ON oc.id_sesion = sa.id_sesion
            WHERE sa.fecha BETWEEN :fecha_inicio AND :fecha_fin";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':id_usuario' => $idUsuario,
        ':fecha_inicio' => $fechaInicio,
        ':fecha_fin' => $fechaFin
    ]);

    $resultado = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $clave = $fila['id_horario'] . '|' . $fila['fecha'];
        $resultado[$clave] = $fila;
    }

    return $resultado;
}

function obtenerTablaHorarios(PDO $conn, int $idUsuario, int $diasMostrados): array
{
    $horarios = obtenerHorariosBase($conn);
    $fechaInicio = date('Y-m-d');
    $fechaFin = date('Y-m-d', strtotime('+' . ($diasMostrados - 1) . ' days'));
    $sesiones = obtenerSesionesRango($conn, $idUsuario, $fechaInicio, $fechaFin);

    $horariosPorDiaFranja = [];
    $franjas = [];
    $franjasVista = [];

    foreach ($horarios as $horario) {
        $franja = substr($horario['hora_inicio'], 0, 5) . ' - ' . substr($horario['hora_fin'], 0, 5);
        $claveDiaFranja = $horario['dia_semana'] . '|' . $franja;

        if (!isset($horariosPorDiaFranja[$claveDiaFranja])) {
            $horariosPorDiaFranja[$claveDiaFranja] = $horario;
        }

        if (!isset($franjas[$franja])) {
            $franjas[$franja] = [
                'franja' => $franja,
                'hora_inicio' => $horario['hora_inicio']
            ];
        }
    }

    usort($franjas, function ($a, $b) {
        return strcmp($a['hora_inicio'], $b['hora_inicio']);
    });

    foreach ($franjas as $item) {
        $franjasVista[] = $item['franja'];
    }

    $columnas = [];
    for ($i = 0; $i < $diasMostrados; $i++) {
        $fechaIso = date('Y-m-d', strtotime('+' . $i . ' days'));
        $columnas[] = [
            'fecha_iso' => $fechaIso,
            'dia' => diaCortoDesdeFecha($fechaIso),
            'fecha' => formatearFecha($fechaIso)
        ];
    }

    $filas = [];
    $sesionesDisponibles = 0;

    foreach ($franjasVista as $franja) {
        $fila = [
            'franja' => $franja,
            'celdas' => []
        ];

        foreach ($columnas as $columna) {
            $fechaIso = $columna['fecha_iso'];
            $diaSemana = diaSemanaDesdeFecha($fechaIso);
            $claveHorario = $diaSemana . '|' . $franja;

            if (!isset($horariosPorDiaFranja[$claveHorario])) {
                $fila['celdas'][] = [
                    'vacia' => true
                ];
                continue;
            }

            $horario = $horariosPorDiaFranja[$claveHorario];
            $claveSesion = $horario['id_horario'] . '|' . $fechaIso;
            $sesion = $sesiones[$claveSesion] ?? null;

            $capacidad = (int) ($horario['capacidad'] ?? 20);
            $ocupadas = 0;
            $monitor = 'Por confirmar';
            $estado = 'Disponible';
            $puedeReservar = esFechaHoraReservable($fechaIso, $horario['hora_inicio']);

            if ($sesion) {
                $capacidad = (int) ($sesion['capacidad_real'] ?? $capacidad);
                $ocupadas = (int) ($sesion['ocupadas'] ?? 0);
                $monitor = !empty($sesion['instructor']) ? $sesion['instructor'] : 'Por confirmar';

                if ($sesion['estado_sesion'] === 'Cancelada') {
                    $estado = 'Cancelada';
                    $puedeReservar = false;
                } elseif ($sesion['estado_sesion'] === 'Completada') {
                    $estado = 'Completada';
                    $puedeReservar = false;
                } elseif ($sesion['estado_reserva'] === 'Confirmada') {
                    $estado = 'Confirmada';
                    $puedeReservar = false;
                }
            }

            $libres = $capacidad - $ocupadas;
            if ($libres < 0) {
                $libres = 0;
            }

            if ($estado === 'Disponible' && $libres <= 0) {
                $estado = 'Completa';
                $puedeReservar = false;
            }

            if ($puedeReservar) {
                $sesionesDisponibles++;
            }

            $fila['celdas'][] = [
                'vacia' => false,
                'id_horario' => (int) $horario['id_horario'],
                'fecha_iso' => $fechaIso,
                'actividad' => $horario['actividad'],
                'sala' => $horario['sala'],
                'monitor' => $monitor,
                'fecha' => formatearFecha($fechaIso),
                'plazas' => 'Plazas libres: ' . $libres,
                'estado' => $estado,
                'puede_reservar' => $puedeReservar
            ];
        }

        $filas[] = $fila;
    }

    return [
        'columnas' => $columnas,
        'filas' => $filas,
        'sesiones_disponibles' => $sesionesDisponibles
    ];
}

function obtenerHorarioParaReserva(PDO $conn, int $idHorario): ?array
{
    $sql = "SELECT
                h.id_horario,
                h.dia_semana,
                h.hora_inicio,
                h.hora_fin,
                COALESCE(s.capacidad, 20) AS capacidad
            FROM horario_actividad h
            LEFT JOIN sala s ON h.id_sala = s.id_sala
            WHERE h.id_horario = :id_horario
              AND h.activo = 1
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':id_horario' => $idHorario
    ]);

    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ?: null;
}

function obtenerOCrearSesion(PDO $conn, int $idHorario, string $fecha): ?array
{
    $horario = obtenerHorarioParaReserva($conn, $idHorario);

    if (!$horario) {
        return null;
    }

    if (diaSemanaDesdeFecha($fecha) !== $horario['dia_semana']) {
        return null;
    }

    $sqlSesion = "SELECT
                    sa.id_sesion,
                    sa.id_horario,
                    sa.fecha,
                    sa.instructor,
                    sa.plazas_totales,
                    sa.estado
                  FROM sesion_actividad sa
                  WHERE sa.id_horario = :id_horario
                    AND sa.fecha = :fecha
                  LIMIT 1
                  FOR UPDATE";

    $stmtSesion = $conn->prepare($sqlSesion);
    $stmtSesion->execute([
        ':id_horario' => $idHorario,
        ':fecha' => $fecha
    ]);

    $sesion = $stmtSesion->fetch(PDO::FETCH_ASSOC);

    if ($sesion) {
        return [
            'id_sesion' => (int) $sesion['id_sesion'],
            'fecha' => $sesion['fecha'],
            'hora_inicio' => $horario['hora_inicio'],
            'capacidad' => (int) ($horario['capacidad'] ?? $sesion['plazas_totales'] ?? 20),
            'estado' => $sesion['estado']
        ];
    }

    $capacidad = (int) ($horario['capacidad'] ?? 20);

    $sqlInsertar = "INSERT INTO sesion_actividad (id_horario, fecha, instructor, plazas_totales, estado)
                    VALUES (:id_horario, :fecha, NULL, :plazas_totales, 'Programada')";

    $stmtInsertar = $conn->prepare($sqlInsertar);
    $stmtInsertar->execute([
        ':id_horario' => $idHorario,
        ':fecha' => $fecha,
        ':plazas_totales' => $capacidad
    ]);

    return [
        'id_sesion' => (int) $conn->lastInsertId(),
        'fecha' => $fecha,
        'hora_inicio' => $horario['hora_inicio'],
        'capacidad' => $capacidad,
        'estado' => 'Programada'
    ];
}

function reservarHorario(PDO $conn, int $idUsuario, int $idHorario, string $fecha): array
{
    try {
        $conn->beginTransaction();

        $datosSesion = obtenerOCrearSesion($conn, $idHorario, $fecha);

        if (!$datosSesion) {
            $conn->rollBack();
            return [
                'ok' => false,
                'mensaje' => 'No existe un horario válido para esa fecha.'
            ];
        }

        if (!esFechaHoraReservable($datosSesion['fecha'], $datosSesion['hora_inicio'])) {
            $conn->rollBack();
            return [
                'ok' => false,
                'mensaje' => 'No puedes reservar una clase pasada.'
            ];
        }

        if ($datosSesion['estado'] !== 'Programada') {
            $conn->rollBack();
            return [
                'ok' => false,
                'mensaje' => 'La sesión no está disponible para reservar.'
            ];
        }

        $idSesion = (int) $datosSesion['id_sesion'];

        $sqlOcupadas = "SELECT COUNT(*)
                        FROM reserva
                        WHERE id_sesion = :id_sesion
                          AND estado = 'Confirmada'";

        $stmtOcupadas = $conn->prepare($sqlOcupadas);
        $stmtOcupadas->execute([
            ':id_sesion' => $idSesion
        ]);

        $ocupadas = (int) $stmtOcupadas->fetchColumn();
        $capacidad = (int) ($datosSesion['capacidad'] ?? 20);

        if ($ocupadas >= $capacidad) {
            $conn->rollBack();
            return [
                'ok' => false,
                'mensaje' => 'No quedan plazas disponibles para esta sesión.'
            ];
        }

        $sqlExistente = "SELECT id_reserva, estado
                         FROM reserva
                         WHERE id_usuario = :id_usuario
                           AND id_sesion = :id_sesion
                         LIMIT 1
                         FOR UPDATE";

        $stmtExistente = $conn->prepare($sqlExistente);
        $stmtExistente->execute([
            ':id_usuario' => $idUsuario,
            ':id_sesion' => $idSesion
        ]);

        $reservaExistente = $stmtExistente->fetch(PDO::FETCH_ASSOC);

        if ($reservaExistente && $reservaExistente['estado'] === 'Confirmada') {
            $conn->rollBack();
            return [
                'ok' => false,
                'mensaje' => 'Ya tienes esta sesión reservada.'
            ];
        }

        if ($reservaExistente) {
            $sqlActualizar = "UPDATE reserva
                              SET estado = 'Confirmada',
                                  fecha_reserva = NOW()
                              WHERE id_reserva = :id_reserva";

            $stmtActualizar = $conn->prepare($sqlActualizar);
            $stmtActualizar->execute([
                ':id_reserva' => $reservaExistente['id_reserva']
            ]);
        } else {
            $sqlInsertar = "INSERT INTO reserva (id_usuario, id_sesion, fecha_reserva, estado)
                            VALUES (:id_usuario, :id_sesion, NOW(), 'Confirmada')";

            $stmtInsertar = $conn->prepare($sqlInsertar);
            $stmtInsertar->execute([
                ':id_usuario' => $idUsuario,
                ':id_sesion' => $idSesion
            ]);
        }

        $conn->commit();

        return [
            'ok' => true,
            'mensaje' => 'Reserva realizada correctamente.'
        ];
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        return [
            'ok' => false,
            'mensaje' => 'Error al realizar la reserva: ' . $e->getMessage()
        ];
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $usuario = obtenerUsuarioSidebar($conn, $idUsuario);
        $resumen = obtenerResumen($conn, $idUsuario);
        $tablaData = obtenerTablaHorarios($conn, $idUsuario, $diasMostrados);
        $resumen['sesiones_disponibles'] = $tablaData['sesiones_disponibles'];

        responderJSON([
            'ok' => true,
            'usuario' => $usuario,
            'resumen' => $resumen,
            'columnas' => $tablaData['columnas'],
            'filas' => $tablaData['filas']
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idHorario = isset($_POST['id_horario']) ? (int) $_POST['id_horario'] : 0;
        $fecha = trim($_POST['fecha'] ?? '');

        if ($idHorario <= 0 || $fecha === '') {
            responderJSON([
                'ok' => false,
                'mensaje' => 'Datos de reserva no válidos.'
            ]);
        }

        $resultado = reservarHorario($conn, $idUsuario, $idHorario, $fecha);
        responderJSON($resultado);
    }

    responderJSON([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);
} catch (PDOException $e) {
    responderJSON([
        'ok' => false,
        'mensaje' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
