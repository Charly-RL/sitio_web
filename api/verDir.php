<?php
// API para obtener las direcciones de un usuario

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/db.php'; // Conexión a la base de datos
require_once __DIR__ . '/../includes/auth.php'; // Funciones de autenticación

// Verificar si el usuario está autenticado
if (!estaAutenticado()) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit;
}

$conexion = conectarDB();

// Obtener el ID del usuario desde POST
$usuario_id = $_POST['usuario_id'];

// Consulta para obtener las direcciones del usuario
$sql = "SELECT * FROM direcciones WHERE usuario_id = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$result = $stmt->execute();

if ($result) {
    $resultado = $stmt->get_result();
    $direcciones = [];
    // Recorrer los resultados y agregarlos al array
    while ($row = $resultado->fetch_assoc()) {
        $direcciones[] = $row;
    }
    // Responder con las direcciones encontradas
    echo json_encode(['direccion' => $direcciones]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener las direcciones']);
}

// Cerrar la conexión a la base de datos
$conexion->close();
?>