<?php
require_once __DIR__ . '/../comprobar-login.php';
require_once __DIR__ . '/../conexion.php';

header('Content-Type: application/json; charset=utf-8');

$idUsuario = $_SESSION['id_usuario'];

try {
    $sql = "SELECT 
                u.nombre,
                u.apellidos,
                u.correo,
                u.telefono,
                u.foto_perfil,
                d.calle,
                d.portal,
                d.piso,
                d.cp,
                d.ciudad,
                d.pais,
                m.nombre AS membresia
            FROM usuario u
            LEFT JOIN direccion d 
                ON u.id_direccion = d.id_direccion
            LEFT JOIN suscripcion s 
                ON u.id_usuario = s.id_usuario AND s.estado = 'Activa'
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
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se encontró el usuario.'
        ]);
        exit;
    }

    $partesDireccion = [];

    if (!empty($usuario['calle'])) {
        $direccion1 = $usuario['calle'];

        if (!empty($usuario['portal'])) {
            $direccion1 .= ' ' . $usuario['portal'];
        }

        if (!empty($usuario['piso'])) {
            $direccion1 .= ' ' . $usuario['piso'];
        }

        $partesDireccion[] = $direccion1;
    }

    $direccion2 = trim(
        (!empty($usuario['cp']) ? $usuario['cp'] . ' ' : '') .
            (!empty($usuario['ciudad']) ? $usuario['ciudad'] : '')
    );

    if ($direccion2 !== '') {
        $partesDireccion[] = $direccion2;
    }

    if (!empty($usuario['pais'])) {
        $partesDireccion[] = $usuario['pais'];
    }

    $direccionCompleta = !empty($partesDireccion)
        ? implode(', ', $partesDireccion)
        : 'Sin dirección registrada';

    $fotoPerfil = !empty($usuario['foto_perfil'])
        ? $usuario['foto_perfil']
        : '../img-socios/socio1.png';

    echo json_encode([
        'ok' => true,
        'nombre' => $usuario['nombre'] ?? '',
        'apellidos' => $usuario['apellidos'] ?? '',
        'correo' => $usuario['correo'] ?? '',
        'telefono' => $usuario['telefono'] ?? 'No disponible',
        'direccion' => $direccionCompleta,
        'membresia' => $usuario['membresia'] ?? 'Sin suscripción activa',
        'foto_perfil' => $fotoPerfil
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener el perfil.',
        'error' => $e->getMessage()
    ]);
}
