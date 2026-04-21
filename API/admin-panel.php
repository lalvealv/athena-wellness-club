<?php
require_once __DIR__ . '/../comprobar-admin.php';
require_once __DIR__ . '/../conexion.php';

require_once __DIR__ . '/../actualizar-suscripciones.php';
actualizarSuscripcionesAutomaticamente($conn);

header('Content-Type: application/json; charset=utf-8');

$idAdmin = $_SESSION['id_usuario'];

try {
    // Datos del admin logueado
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
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se encontró el administrador logueado.'
        ]);
        exit;
    }

    // Usuarios activos
    $sqlActivos = "SELECT COUNT(*) FROM usuario WHERE estado = 'Activo'";
    $usuariosActivos = (int) $conn->query($sqlActivos)->fetchColumn();

    // Usuarios bloqueados
    $sqlBloqueados = "SELECT COUNT(*) FROM usuario WHERE estado = 'Bloqueado'";
    $usuariosBloqueados = (int) $conn->query($sqlBloqueados)->fetchColumn();

    // Reservas hoy
    $sqlReservasHoy = "SELECT COUNT(*)
                       FROM reserva r
                       INNER JOIN sesion_actividad sa ON r.id_sesion = sa.id_sesion
                       WHERE sa.fecha = CURDATE()
                         AND r.estado = 'Confirmada'";
    $reservasHoy = (int) $conn->query($sqlReservasHoy)->fetchColumn();

    // Nuevas altas últimos 7 días
    $sqlAltas = "SELECT COUNT(*)
                 FROM usuario
                 WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $nuevasAltas = (int) $conn->query($sqlAltas)->fetchColumn();

    // Últimos usuarios registrados
    $sqlUltimos = "SELECT 
                        id_usuario,
                        alias,
                        CONCAT(nombre, ' ', apellidos) AS nombre_completo,
                        correo,
                        fecha_registro,
                        estado
                   FROM usuario
                   ORDER BY fecha_registro DESC, id_usuario DESC
                   LIMIT 5";

    $stmtUltimos = $conn->query($sqlUltimos);
    $ultimosUsuarios = $stmtUltimos->fetchAll(PDO::FETCH_ASSOC);

    $listaUltimos = [];
    foreach ($ultimosUsuarios as $usuario) {
        $listaUltimos[] = [
            'id_usuario' => $usuario['id_usuario'],
            'alias' => $usuario['alias'] ?? '',
            'nombre_completo' => $usuario['nombre_completo'] ?? '',
            'correo' => $usuario['correo'] ?? '',
            'fecha_registro' => !empty($usuario['fecha_registro'])
                ? date('d/m/Y', strtotime($usuario['fecha_registro']))
                : 'No disponible',
            'estado' => $usuario['estado'] ?? 'No disponible'
        ];
    }

    $fotoAdmin = !empty($admin['foto_perfil']) ? $admin['foto_perfil'] : '../img/athena_logo.png';
    $nombreAdmin = trim(($admin['nombre'] ?? '') . ' ' . ($admin['apellidos'] ?? ''));

    echo json_encode([
        'ok' => true,
        'admin' => [
            'foto_perfil' => $fotoAdmin,
            'nombre_completo' => $nombreAdmin !== '' ? $nombreAdmin : 'Administrador ATHENA',
            'perfil' => 'Perfil ADMIN'
        ],
        'resumen' => [
            'usuarios_activos' => $usuariosActivos,
            'usuarios_bloqueados' => $usuariosBloqueados,
            'reservas_hoy' => $reservasHoy,
            'nuevas_altas' => $nuevasAltas
        ],
        'ultimos_usuarios' => $listaUltimos
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener el panel de administración.',
        'error' => $e->getMessage()
    ]);
}
