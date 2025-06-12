<?php
$host = 'localhost';
$dbname = 'cinemana'; // ¡AQUÍ ESTÁ EL CAMBIO! Ahora es 'cinemana'
$username = 'root'; // Usuario por defecto de XAMPP
$password = '';     // Contraseña por defecto de XAMPP (vacío)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Conexión exitosa a la base de datos."; // Solo para depuración
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>