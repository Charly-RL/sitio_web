<?php
// Configuración de la base de datos del hosting
define('DB_HOST', 'srv529.hstgr.io');
define('DB_USER', 'u827377324_admin');
define('DB_PASS', '!1CmmHkk!');
define('DB_NAME', 'u827377324_tiendakiky');

//info de base de datos local
/*define('DB_HOST', 'localhost');
define('DB_USER', 'admin');
define('DB_PASS', 'admin');
define('DB_NAME', 'tienda_online');*/

// Crear conexión
function conectarDB() {
    $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    // Verificar conexión
    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    // Establecer charset
    $conexion->set_charset("utf8");

    return $conexion;
}