<?php
// Establece la conexión con la base de datos MySQL usando PDO

// --- Datos de conexión a la base de datos ---
$servername = "localhost";   // Servidor (XAMPP → localhost)
$username   = "root";        // Usuario por defecto de MySQL
$password   = "";            // Contraseña (vacía en XAMPP)
$dbname     = "athena";      // Nombre de la base de datos

try {
    // --- Crear conexión usando PDO ---
    $conn = new PDO(
        "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    // --- Configurar modo de errores ---
    // Hace que PDO lance excepciones si ocurre un error
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // --- Manejo de errores de conexión ---
    // Si falla la conexión, se detiene la ejecución y muestra el error
    die("Error de conexión: " . $e->getMessage());
}
