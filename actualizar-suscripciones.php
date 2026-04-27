<?php
// Importa la conexión a la base de datos
require_once __DIR__ . '/conexion.php';

// Actualiza automáticamente el estado de las suscripciones según su fecha de renovación
function actualizarSuscripcionesAutomaticamente(PDO $conn): void
{
    try {
        // Busca suscripciones activas con renovación automática que ya han llegado a su fecha de renovación
        $sqlRenovar = "SELECT id_suscripcion, fecha_renovacion
                       FROM suscripcion
                       WHERE estado = 'Activa'
                         AND renovacion_automatica = 1
                         AND fecha_renovacion IS NOT NULL
                         AND fecha_renovacion <= CURDATE()";

        $stmtRenovar = $conn->query($sqlRenovar);
        $suscripcionesRenovar = $stmtRenovar->fetchAll(PDO::FETCH_ASSOC);

        // Recorre cada suscripción que debe renovarse
        foreach ($suscripcionesRenovar as $suscripcion) {
            // Obtiene la fecha actual de renovación
            $fechaActual = $suscripcion['fecha_renovacion'];

            // Calcula la nueva fecha sumando un mes
            $nuevaFecha = date('Y-m-d', strtotime($fechaActual . ' +1 month'));

            // Actualiza la fecha de renovación en la base de datos
            $sqlUpdateRenovar = "UPDATE suscripcion
                                 SET fecha_renovacion = :fecha_renovacion
                                 WHERE id_suscripcion = :id_suscripcion";

            $stmtUpdateRenovar = $conn->prepare($sqlUpdateRenovar);
            $stmtUpdateRenovar->execute([
                ':fecha_renovacion' => $nuevaFecha,
                ':id_suscripcion' => $suscripcion['id_suscripcion']
            ]);
        }

        // Finaliza suscripciones activas que no tienen renovación automática
        $sqlFinalizarSinAuto = "UPDATE suscripcion
                                SET estado = 'Finalizada',
                                    fecha_fin = CURDATE()
                                WHERE estado = 'Activa'
                                  AND renovacion_automatica = 0
                                  AND fecha_renovacion IS NOT NULL
                                  AND fecha_renovacion <= CURDATE()";

        $conn->exec($sqlFinalizarSinAuto);

        // Finaliza suscripciones canceladas cuando llega su fecha de renovación
        $sqlFinalizarCanceladas = "UPDATE suscripcion
                                   SET estado = 'Finalizada',
                                       renovacion_automatica = 0,
                                       fecha_fin = CURDATE()
                                   WHERE estado = 'Cancelada'
                                     AND fecha_renovacion IS NOT NULL
                                     AND fecha_renovacion <= CURDATE()";

        $conn->exec($sqlFinalizarCanceladas);
    } catch (PDOException $e) {
        // Registra el error en el log del servidor sin mostrarlo al usuario
        error_log("Error al actualizar suscripciones automáticamente: " . $e->getMessage());
    }
}
