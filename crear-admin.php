<?php
// Crea el usuario administrador inicial de ATHENA

// Importar la conexión a la base de datos
require_once 'conexion.php';

try {
    // --- Comprobar si ya existe el administrador ---
    $sqlComprobar = "SELECT COUNT(*) FROM usuario WHERE correo = :correo OR alias = :alias";

    $stmtComprobar = $conn->prepare($sqlComprobar);
    $stmtComprobar->execute([
        ':correo' => 'admin@athena.com',
        ':alias' => 'adminathena'
    ]);

    // Si ya existe un usuario con ese correo o alias, se detiene el script
    if ($stmtComprobar->fetchColumn() > 0) {
        die("El administrador ya existe en la base de datos.");
    }

    // --- Obtener el perfil ADMIN ---
    $sqlPerfil = "SELECT id_perfil FROM perfil WHERE nombre_perfil = 'ADMIN' LIMIT 1";
    $stmtPerfil = $conn->query($sqlPerfil);
    $idPerfil = $stmtPerfil->fetchColumn();

    // Si no existe el perfil ADMIN, no se puede crear el administrador
    if (!$idPerfil) {
        die("No existe el perfil ADMIN en la base de datos.");
    }

    // --- Cifrar contraseña ---
    // password_hash guarda la contraseña de forma segura, no en texto plano
    $contrasenaHash = password_hash("admin12345", PASSWORD_DEFAULT);

    // --- Insertar el usuario administrador ---
    $sqlInsertar = "INSERT INTO usuario
    (alias, nombre, apellidos, fecha_nacimiento, dni, telefono, correo, contrasena, sexo, estado, foto_perfil, id_direccion, id_perfil)
    VALUES
    (:alias, :nombre, :apellidos, :fecha_nacimiento, :dni, :telefono, :correo, :contrasena, :sexo, 'Activo', :foto_perfil, NULL, :id_perfil)";

    $stmtInsertar = $conn->prepare($sqlInsertar);
    $stmtInsertar->execute([
        ':alias' => 'adminathena',
        ':nombre' => 'Administrador',
        ':apellidos' => 'ATHENA',
        ':fecha_nacimiento' => '1990-01-01',
        ':dni' => '00000000A',
        ':telefono' => '600000000',
        ':correo' => 'admin@athena.com',
        ':contrasena' => $contrasenaHash,
        ':sexo' => 'Otro',
        ':foto_perfil' => '../img/admin.jpg',
        ':id_perfil' => $idPerfil
    ]);

    // Mensaje si el administrador se crea correctamente
    echo "Administrador creado correctamente.";
} catch (PDOException $e) {
    // Si ocurre un error con la base de datos, se muestra el mensaje
    die("Error al crear el administrador: " . $e->getMessage());
}
