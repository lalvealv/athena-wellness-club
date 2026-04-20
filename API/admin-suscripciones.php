<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion.php';

$idAdmin = (int)($_SESSION['id_usuario'] ?? 0);
$busqueda = trim($_GET['buscar'] ?? '');
$plan = trim($_GET['plan'] ?? '');
$estado = trim($_GET['estado'] ?? '');

if ($idAdmin <= 0) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Sesión no válida.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['id_perfil']) || (int)$_SESSION['id_perfil'] !== 1) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Acceso no autorizado.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $sqlAdmin = "SELECT nombre, apellidos, foto_perfil
                 FROM usuario
                 WHERE id_usuario = :id_usuario
                 LIMIT 1";

    $stmtAdmin = $conn->prepare($sqlAdmin);
    $stmtAdmin->execute([
        ':id_usuario' => $idAdmin
    ]);
    $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se encontró el administrador logueado.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sqlSuscripciones = "SELECT 
                            s.id_suscripcion,
                            u.id_usuario,
                            CONCAT(u.nombre, ' ', u.apellidos) AS usuario,
                            u.alias,
                            u.correo,
                            m.nombre AS plan,
                            m.cuota,
                            s.fecha_inicio,
                            s.fecha_renovacion,
                            s.estado,
                            s.renovacion_automatica
                         FROM suscripcion s
                         INNER JOIN usuario u
                            ON s.id_usuario = u.id_usuario
                         INNER JOIN membresia m
                            ON s.id_membresia = m.id_membresia
                         WHERE (
                                :busqueda = ''
                                OR u.alias LIKE :like_busqueda
                                OR u.nombre LIKE :like_busqueda
                                OR u.apellidos LIKE :like_busqueda
                                OR u.correo LIKE :like_busqueda
                         )
                         AND (:plan = '' OR m.nombre = :plan)
                         AND (:estado = '' OR s.estado = :estado)
                         ORDER BY s.id_suscripcion ASC";

    $stmtSuscripciones = $conn->prepare($sqlSuscripciones);
    $stmtSuscripciones->execute([
        ':busqueda' => $busqueda,
        ':like_busqueda' => '%' . $busqueda . '%',
        ':plan' => $plan,
        ':estado' => $estado
    ]);
    $suscripciones = $stmtSuscripciones->fetchAll(PDO::FETCH_ASSOC);

    $listaSuscripciones = [];
    foreach ($suscripciones as $item) {
        $listaSuscripciones[] = [
            'id_suscripcion' => (int)$item['id_suscripcion'],
            'id_usuario' => (int)$item['id_usuario'],
            'usuario' => $item['usuario'] ?? '',
            'plan' => $item['plan'] ?? '',
            'precio' => isset($item['cuota']) ? number_format((float)$item['cuota'], 2, ',', '.') . ' €' : 'No disponible',
            'fecha_inicio' => !empty($item['fecha_inicio']) ? date('d/m/Y', strtotime($item['fecha_inicio'])) : 'No disponible',
            'fecha_renovacion' => !empty($item['fecha_renovacion']) ? date('d/m/Y', strtotime($item['fecha_renovacion'])) : 'No disponible',
            'estado' => $item['estado'] ?? 'No disponible',
            'renovacion_automatica' => isset($item['renovacion_automatica']) && (int)$item['renovacion_automatica'] === 1 ? 'Sí' : 'No'
        ];
    }

    $sqlResumen = "SELECT 
                        m.nombre AS plan,
                        COUNT(*) AS total
                   FROM suscripcion s
                   INNER JOIN membresia m
                        ON s.id_membresia = m.id_membresia
                   WHERE s.estado = 'Activa'
                   GROUP BY m.nombre";

    $stmtResumen = $conn->query($sqlResumen);
    $resumenPlanes = $stmtResumen->fetchAll(PDO::FETCH_ASSOC);

    $totalEssential = 0;
    $totalPremium = 0;
    $totalExecutive = 0;

    foreach ($resumenPlanes as $fila) {
        if ($fila['plan'] === 'Essential' || $fila['plan'] === 'Essential Morning') {
            $totalEssential += (int)$fila['total'];
        } elseif ($fila['plan'] === 'Premium') {
            $totalPremium += (int)$fila['total'];
        } elseif ($fila['plan'] === 'Executive') {
            $totalExecutive += (int)$fila['total'];
        }
    }

    $sqlCanceladas = "SELECT COUNT(*) FROM suscripcion WHERE estado = 'Cancelada'";
    $totalCanceladas = (int)$conn->query($sqlCanceladas)->fetchColumn();

    $fotoAdmin = !empty($admin['foto_perfil']) ? $admin['foto_perfil'] : '../img/athena_logo.png';
    $nombreAdmin = trim(($admin['nombre'] ?? '') . ' ' . ($admin['apellidos'] ?? ''));

    echo json_encode([
        'ok' => true,
        'admin' => [
            'foto_perfil' => $fotoAdmin,
            'nombre_completo' => $nombreAdmin !== '' ? $nombreAdmin : 'Administrador ATHENA',
            'perfil' => 'Perfil ADMIN'
        ],
        'suscripciones' => $listaSuscripciones,
        'resumen' => [
            'essential' => $totalEssential . ' usuarios',
            'premium' => $totalPremium . ' usuarios',
            'executive' => $totalExecutive . ' usuarios',
            'canceladas' => $totalCanceladas
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener las suscripciones.',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
