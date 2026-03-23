<?php
require_once 'conexion.php';

$alias = "adminathena";
$nombre = "Administrador";
$apellidos = "ATHENA";
$fecha_nacimiento = "1990-01-01";
$dni = "00000000A";
$telefono = "600000000";
$correo = "admin@athena.com";
$contrasena = password_hash("admin12345", PASSWORD_DEFAULT);
$sexo = "Otro";
$id_perfil = 1; // ADMIN

$sql = "INSERT INTO usuario
(alias, nombre, apellidos, fecha_nacimiento, dni, telefono, correo, contrasena, sexo, id_perfil)
VALUES
(:alias, :nombre, :apellidos, :fecha_nacimiento, :dni, :telefono, :correo, :contrasena, :sexo, :id_perfil)";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ':alias' => $alias,
    ':nombre' => $nombre,
    ':apellidos' => $apellidos,
    ':fecha_nacimiento' => $fecha_nacimiento,
    ':dni' => $dni,
    ':telefono' => $telefono,
    ':correo' => $correo,
    ':contrasena' => $contrasena,
    ':sexo' => $sexo,
    ':id_perfil' => $id_perfil
]);

echo "Admin creado correctamente";
