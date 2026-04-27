<?php
// Comprueba que el usuario ha iniciado sesión
require_once __DIR__ . '/../comprobar-login.php';

// Importa la conexión a la base de datos
require_once __DIR__ . '/../conexion.php';

// Indica que la respuesta será JSON
header('Content-Type: application/json; charset=utf-8');

// Obtiene el ID del usuario logueado
$idUsuario = $_SESSION['id_usuario'];

try {
    // Consulta los datos personales, dirección y membresía activa del usuario
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

    // Prepara y ejecuta la consulta
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':id_usuario' => $idUsuario
    ]);

    // Obtiene los datos del usuario
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si no se encuentra el usuario, devuelve error
    if (!$usuario) {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se encontró el usuario.'
        ]);
        exit;
    }

    // Array donde se irán guardando las partes de la dirección
    $partesDireccion = [];

    // Construye la primera parte de la dirección: calle, portal y piso
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

    // Construye la segunda parte de la dirección: código postal y ciudad
    $direccion2 = trim(
        (!empty($usuario['cp']) ? $usuario['cp'] . ' ' : '') .
            (!empty($usuario['ciudad']) ? $usuario['ciudad'] : '')
    );

    // Añade código postal y ciudad si existen
    if ($direccion2 !== '') {
        $partesDireccion[] = $direccion2;
    }

    // Añade el país si existe
    if (!empty($usuario['pais'])) {
        $partesDireccion[] = $usuario['pais'];
    }

    // Une todas las partes de la dirección en un único texto
    $direccionCompleta = !empty($partesDireccion)
        ? implode(', ', $partesDireccion)
        : 'Sin dirección registrada';

    // Usa la foto de perfil del usuario o una imagen por defecto
    $fotoPerfil = !empty($usuario['foto_perfil'])
        ? $usuario['foto_perfil']
        : '../img-socios/socio1.png';

    // Devuelve todos los datos del perfil al JavaScript
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
    // Si ocurre un error de base de datos, devuelve error JSON
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener el perfil.',
        'error' => $e->getMessage()
    ]);
}
