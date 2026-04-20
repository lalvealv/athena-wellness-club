<?php
require_once __DIR__ . '/conexion.php';

function actualizarSuscripcionesAutomaticamente(PDO $conn): void
{
    try {
        // Renovar activas con renovación automática
        $sqlRenovar = "SELECT id_suscripcion, fecha_renovacion
                       FROM suscripcion
                       WHERE estado = 'Activa'
                         AND renovacion_automatica = 1
                         AND fecha_renovacion IS NOT NULL
                         AND fecha_renovacion <= CURDATE()";

        $stmtRenovar = $conn->query($sqlRenovar);
        $suscripcionesRenovar = $stmtRenovar->fetchAll(PDO::FETCH_ASSOC);

        foreach ($suscripcionesRenovar as $suscripcion) {
            $fechaActual = $suscripcion['fecha_renovacion'];
            $nuevaFecha = date('Y-m-d', strtotime($fechaActual . ' +1 month'));

            $sqlUpdateRenovar = "UPDATE suscripcion
                                 SET fecha_renovacion = :fecha_renovacion
                                 WHERE id_suscripcion = :id_suscripcion";

            $stmtUpdateRenovar = $conn->prepare($sqlUpdateRenovar);
            $stmtUpdateRenovar->execute([
                ':fecha_renovacion' => $nuevaFecha,
                ':id_suscripcion' => $suscripcion['id_suscripcion']
            ]);
        }

        // Finalizar activas sin renovación automática
        $sqlFinalizarSinAuto = "UPDATE suscripcion
                                SET estado = 'Finalizada',
                                    fecha_fin = CURDATE()
                                WHERE estado = 'Activa'
                                  AND renovacion_automatica = 0
                                  AND fecha_renovacion IS NOT NULL
                                  AND fecha_renovacion <= CURDATE()";

        $conn->exec($sqlFinalizarSinAuto);

        // Finalizar canceladas al llegar la fecha
        $sqlFinalizarCanceladas = "UPDATE suscripcion
                                   SET estado = 'Finalizada',
                                       renovacion_automatica = 0,
                                       fecha_fin = CURDATE()
                                   WHERE estado = 'Cancelada'
                                     AND fecha_renovacion IS NOT NULL
                                     AND fecha_renovacion <= CURDATE()";

        $conn->exec($sqlFinalizarCanceladas);
    } catch (PDOException $e) {
        error_log("Error al actualizar suscripciones automáticamente: " . $e->getMessage());
    }
}
