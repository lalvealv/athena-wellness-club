<?php
require_once __DIR__ . '/../comprobar-admin.php';
require_once __DIR__ . '/../conexion.php';

header('Content-Type: application/json; charset=utf-8');

$idAdmin = (int)$_SESSION['id_usuario'];

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
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $destinatario = trim($_POST['destinatario'] ?? '');
        $tipo = trim($_POST['tipoAviso'] ?? '');
        $titulo = trim($_POST['tituloAviso'] ?? '');
        $mensaje = trim($_POST['mensajeAviso'] ?? '');
        $usuarioEspecifico = trim($_POST['usuarioEspecifico'] ?? '');

        $destinatariosValidos = ['Todos', 'Essential Morning', 'Essential', 'Premium', 'Executive', 'Usuario'];
        $tiposValidos = ['General', 'Reserva', 'Suscripcion', 'Recordatorio'];

        if (!in_array($destinatario, $destinatariosValidos, true)) {
            responderError('El destinatario no es válido.');
        }

        if (!in_array($tipo, $tiposValidos, true)) {
            responderError('El tipo de aviso no es válido.');
        }

        if ($titulo === '' || $mensaje === '') {
            responderError('Debes completar el título y el mensaje.');
        }

        $conn->beginTransaction();

        $sqlInsertNotificacion = "INSERT INTO notificacion (titulo, mensaje, tipo, destinatario_tipo)
                                  VALUES (:titulo, :mensaje, :tipo, :destinatario_tipo)";

        $stmtInsertNotificacion = $conn->prepare($sqlInsertNotificacion);
        $stmtInsertNotificacion->execute([
            ':titulo' => $titulo,
            ':mensaje' => $mensaje,
            ':tipo' => $tipo,
            ':destinatario_tipo' => $destinatario
        ]);

        $idNotificacion = (int)$conn->lastInsertId();

        if ($destinatario === 'Usuario') {
            if ($usuarioEspecifico === '') {
                $conn->rollBack();
                responderError('Debes indicar el alias o correo del usuario.');
            }

            $sqlUsuario = "SELECT id_usuario
                           FROM usuario
                           WHERE alias = :dato OR correo = :dato
                           LIMIT 1";

            $stmtUsuario = $conn->prepare($sqlUsuario);
            $stmtUsuario->execute([
                ':dato' => $usuarioEspecifico
            ]);
            $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                $conn->rollBack();
                responderError('No se ha encontrado el usuario indicado.');
            }

            $sqlRelacion = "INSERT INTO usuario_notificacion (id_usuario, id_notificacion, leida, fecha_lectura)
                            VALUES (:id_usuario, :id_notificacion, 0, NULL)";

            $stmtRelacion = $conn->prepare($sqlRelacion);
            $stmtRelacion->execute([
                ':id_usuario' => $usuario['id_usuario'],
                ':id_notificacion' => $idNotificacion
            ]);
        } else {
            if ($destinatario === 'Todos') {
                $sqlUsuarios = "SELECT id_usuario
                                FROM usuario
                                WHERE id_perfil = 2";
                $stmtUsuarios = $conn->query($sqlUsuarios);
            } else {
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

            $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

            $sqlRelacion = "INSERT INTO usuario_notificacion (id_usuario, id_notificacion, leida, fecha_lectura)
                            VALUES (:id_usuario, :id_notificacion, 0, NULL)";
            $stmtRelacion = $conn->prepare($sqlRelacion);

            foreach ($usuarios as $usuario) {
                $stmtRelacion->execute([
                    ':id_usuario' => $usuario['id_usuario'],
                    ':id_notificacion' => $idNotificacion
                ]);
            }
        }

        $conn->commit();

        echo json_encode([
            'ok' => true,
            'mensaje' => 'Notificación enviada correctamente.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

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
        responderError('No se encontró el administrador logueado.', 404);
    }

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

    $sqlHoy = "SELECT COUNT(*)
               FROM notificacion
               WHERE DATE(fecha_envio) = CURDATE()";
    $avisosHoy = (int)$conn->query($sqlHoy)->fetchColumn();

    $sqlGenerales = "SELECT COUNT(*)
                     FROM notificacion
                     WHERE tipo = 'General'";
    $avisosGenerales = (int)$conn->query($sqlGenerales)->fetchColumn();

    $sqlRecordatorios = "SELECT COUNT(*)
                         FROM notificacion
                         WHERE tipo = 'Recordatorio'";
    $recordatorios = (int)$conn->query($sqlRecordatorios)->fetchColumn();

    $sqlPendientes = "SELECT COUNT(*)
                      FROM usuario_notificacion
                      WHERE leida = 0";
    $pendientes = (int)$conn->query($sqlPendientes)->fetchColumn();

    $fotoAdmin = !empty($admin['foto_perfil']) ? $admin['foto_perfil'] : '../img/athena_logo.png';
    $nombreAdmin = trim(($admin['nombre'] ?? '') . ' ' . ($admin['apellidos'] ?? ''));

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
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    responderError('Error al gestionar las notificaciones.', 500, $e->getMessage());
}
