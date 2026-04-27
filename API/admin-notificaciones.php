<?php
// Comprueba que el usuario logueado es administrador
require_once __DIR__ . '/../comprobar-admin.php';

// Importa la conexión a la base de datos
require_once __DIR__ . '/../conexion.php';

// Indica que la respuesta será JSON
header('Content-Type: application/json; charset=utf-8');

// Guarda el ID del administrador logueado
$idAdmin = (int)$_SESSION['id_usuario'];

// Función para devolver errores en formato JSON
function responderError(string $mensaje, int $codigo = 400, ?string $error = null): void
{
    http_response_code($codigo);

    echo json_encode([
        'ok' => false,
        'mensaje' => $mensaje,
        'error' => $error
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    // PETICIÓN POST: crear y enviar una nueva notificación
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Recoge los datos enviados desde el formulario
        $destinatario = trim($_POST['destinatario'] ?? '');
        $tipo = trim($_POST['tipoAviso'] ?? '');
        $titulo = trim($_POST['tituloAviso'] ?? '');
        $mensaje = trim($_POST['mensajeAviso'] ?? '');
        $usuarioEspecifico = trim($_POST['usuarioEspecifico'] ?? '');

        // Destinatarios permitidos
        $destinatariosValidos = ['Todos', 'Essential Morning', 'Essential', 'Premium', 'Executive', 'Usuario'];

        // Tipos de aviso permitidos
        $tiposValidos = ['General', 'Reserva', 'Suscripcion', 'Recordatorio'];

        // Valida destinatario
        if (!in_array($destinatario, $destinatariosValidos, true)) {
            responderError('El destinatario no es válido.');
        }

        // Valida tipo de aviso
        if (!in_array($tipo, $tiposValidos, true)) {
            responderError('El tipo de aviso no es válido.');
        }

        // Valida campos obligatorios
        if ($titulo === '' || $mensaje === '') {
            responderError('Debes completar el título y el mensaje.');
        }

        // Inicia transacción para insertar notificación y destinatarios juntos
        $conn->beginTransaction();

        // Inserta la notificación general
        $sqlInsertNotificacion = "INSERT INTO notificacion (titulo, mensaje, tipo, destinatario_tipo)
                                  VALUES (:titulo, :mensaje, :tipo, :destinatario_tipo)";

        $stmtInsertNotificacion = $conn->prepare($sqlInsertNotificacion);
        $stmtInsertNotificacion->execute([
            ':titulo' => $titulo,
            ':mensaje' => $mensaje,
            ':tipo' => $tipo,
            ':destinatario_tipo' => $destinatario
        ]);

        // Obtiene el ID de la notificación creada
        $idNotificacion = (int)$conn->lastInsertId();

        // Caso 1: notificación para un usuario concreto
        if ($destinatario === 'Usuario') {

            // Valida que se haya indicado alias o correo
            if ($usuarioEspecifico === '') {
                $conn->rollBack();
                responderError('Debes indicar el alias o correo del usuario.');
            }

            // Busca el usuario por alias o correo
            $sqlUsuario = "SELECT id_usuario
                           FROM usuario
                           WHERE alias = :dato OR correo = :dato
                           LIMIT 1";

            $stmtUsuario = $conn->prepare($sqlUsuario);
            $stmtUsuario->execute([
                ':dato' => $usuarioEspecifico
            ]);
            $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

            // Si no se encuentra, cancela la operación
            if (!$usuario) {
                $conn->rollBack();
                responderError('No se ha encontrado el usuario indicado.');
            }

            // Relaciona la notificación con ese usuario
            $sqlRelacion = "INSERT INTO usuario_notificacion (id_usuario, id_notificacion, leida, fecha_lectura)
                            VALUES (:id_usuario, :id_notificacion, 0, NULL)";

            $stmtRelacion = $conn->prepare($sqlRelacion);
            $stmtRelacion->execute([
                ':id_usuario' => $usuario['id_usuario'],
                ':id_notificacion' => $idNotificacion
            ]);
        } else {
            // Caso 2: notificación para todos o para usuarios de una membresía

            if ($destinatario === 'Todos') {
                // Selecciona todos los usuarios cliente
                $sqlUsuarios = "SELECT id_usuario
                                FROM usuario
                                WHERE id_perfil = 2";
                $stmtUsuarios = $conn->query($sqlUsuarios);
            } else {
                // Selecciona usuarios con una membresía activa concreta
                $sqlUsuarios = "SELECT DISTINCT u.id_usuario
                                FROM usuario u
                                INNER JOIN suscripcion s
                                    ON u.id_usuario = s.id_usuario
                                INNER JOIN membresia m
                                    ON s.id_membresia = m.id_membresia
                                WHERE u.id_perfil = 2
                                  AND s.estado = 'Activa'
                                  AND m.nombre = :membresia";

                $stmtUsuarios = $conn->prepare($sqlUsuarios);
                $stmtUsuarios->execute([
                    ':membresia' => $destinatario
                ]);
            }

            // Obtiene los usuarios destinatarios
            $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

            // Prepara la inserción en la tabla intermedia
            $sqlRelacion = "INSERT INTO usuario_notificacion (id_usuario, id_notificacion, leida, fecha_lectura)
                            VALUES (:id_usuario, :id_notificacion, 0, NULL)";
            $stmtRelacion = $conn->prepare($sqlRelacion);

            // Inserta una relación por cada usuario destinatario
            foreach ($usuarios as $usuario) {
                $stmtRelacion->execute([
                    ':id_usuario' => $usuario['id_usuario'],
                    ':id_notificacion' => $idNotificacion
                ]);
            }
        }

        // Confirma todos los cambios
        $conn->commit();

        // Respuesta correcta
        echo json_encode([
            'ok' => true,
            'mensaje' => 'Notificación enviada correctamente.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // PETICIÓN GET: carga datos del administrador, últimas notificaciones y resumen

    // Consulta los datos del administrador logueado
    $sqlAdmin = "SELECT nombre, apellidos, foto_perfil
                 FROM usuario
                 WHERE id_usuario = :id_usuario
                 LIMIT 1";

    $stmtAdmin = $conn->prepare($sqlAdmin);
    $stmtAdmin->execute([
        ':id_usuario' => $idAdmin
    ]);
    $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

    // Si no se encuentra el administrador, devuelve error
    if (!$admin) {
        responderError('No se encontró el administrador logueado.', 404);
    }

    // Consulta las últimas 10 notificaciones enviadas
    $sqlNotificaciones = "SELECT
                            n.id_notificacion,
                            n.titulo,
                            n.mensaje,
                            n.tipo,
                            n.destinatario_tipo,
                            n.fecha_envio,
                            COUNT(un.id_usuario_notificacion) AS total_destinatarios
                          FROM notificacion n
                          LEFT JOIN usuario_notificacion un
                            ON n.id_notificacion = un.id_notificacion
                          GROUP BY
                            n.id_notificacion,
                            n.titulo,
                            n.mensaje,
                            n.tipo,
                            n.destinatario_tipo,
                            n.fecha_envio
                          ORDER BY n.fecha_envio DESC, n.id_notificacion DESC
                          LIMIT 10";

    $stmtNotificaciones = $conn->query($sqlNotificaciones);
    $notificaciones = $stmtNotificaciones->fetchAll(PDO::FETCH_ASSOC);

    // Formatea las notificaciones para enviarlas al frontend
    $listaNotificaciones = [];
    foreach ($notificaciones as $item) {
        $listaNotificaciones[] = [
            'titulo' => $item['titulo'] ?? '',
            'mensaje' => $item['mensaje'] ?? '',
            'tipo' => $item['tipo'] ?? '',
            'destinatario' => $item['destinatario_tipo'] ?? '',
            'fecha_envio' => !empty($item['fecha_envio']) ? date('d/m/Y H:i', strtotime($item['fecha_envio'])) : 'No disponible',
            'total_destinatarios' => (int)($item['total_destinatarios'] ?? 0)
        ];
    }

    // Cuenta avisos enviados hoy
    $sqlHoy = "SELECT COUNT(*)
               FROM notificacion
               WHERE DATE(fecha_envio) = CURDATE()";
    $avisosHoy = (int)$conn->query($sqlHoy)->fetchColumn();

    // Cuenta avisos de tipo general
    $sqlGenerales = "SELECT COUNT(*)
                     FROM notificacion
                     WHERE tipo = 'General'";
    $avisosGenerales = (int)$conn->query($sqlGenerales)->fetchColumn();

    // Cuenta avisos de tipo recordatorio
    $sqlRecordatorios = "SELECT COUNT(*)
                         FROM notificacion
                         WHERE tipo = 'Recordatorio'";
    $recordatorios = (int)$conn->query($sqlRecordatorios)->fetchColumn();

    // Cuenta notificaciones pendientes de leer
    $sqlPendientes = "SELECT COUNT(*)
                      FROM usuario_notificacion
                      WHERE leida = 0";
    $pendientes = (int)$conn->query($sqlPendientes)->fetchColumn();

    // Prepara datos del administrador
    $fotoAdmin = !empty($admin['foto_perfil']) ? $admin['foto_perfil'] : '../img/athena_logo.png';
    $nombreAdmin = trim(($admin['nombre'] ?? '') . ' ' . ($admin['apellidos'] ?? ''));

    // Devuelve todos los datos al frontend
    echo json_encode([
        'ok' => true,
        'admin' => [
            'foto_perfil' => $fotoAdmin,
            'nombre_completo' => $nombreAdmin !== '' ? $nombreAdmin : 'Administrador ATHENA',
            'perfil' => 'Perfil ADMIN'
        ],
        'notificaciones' => $listaNotificaciones,
        'resumen' => [
            'avisos_hoy' => $avisosHoy,
            'avisos_generales' => $avisosGenerales,
            'recordatorios' => $recordatorios,
            'pendientes' => $pendientes
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    // Si hay una transacción abierta, se deshacen los cambios
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Devuelve el error en JSON
    responderError('Error al gestionar las notificaciones.', 500, $e->getMessage());
}
