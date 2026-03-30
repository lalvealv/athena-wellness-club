<?php
require_once 'conexion.php';

try {
    // Comprobar si ya existe el admin
    $sqlComprobar = "SELECT COUNT(*) FROM usuario WHERE correo = :correo OR alias = :alias";
    $stmtComprobar = $conn->prepare($sqlComprobar);
    $stmtComprobar->execute([
        ':correo' => 'admin@athena.com',
        ':alias' => 'adminathena'
    ]);

    if ($stmtComprobar->fetchColumn() > 0) {
        die("El administrador ya existe en la base de datos.");
    }

    // Obtener el id del perfil ADMIN
    $sqlPerfil = "SELECT id_perfil FROM perfil WHERE nombre_perfil = 'ADMIN' LIMIT 1";
    $stmtPerfil = $conn->query($sqlPerfil);
    $idPerfil = $stmtPerfil->fetchColumn();

    if (!$idPerfil) {
        die("No existe el perfil ADMIN en la base de datos.");
    }

    // Cifrar contraseña
    $contrasenaHash = password_hash("admin12345", PASSWORD_DEFAULT);

    // Insertar admin
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

    echo "Administrador creado correctamente.";
} catch (PDOException $e) {
    die("Error al crear el administrador: " . $e->getMessage());
}
