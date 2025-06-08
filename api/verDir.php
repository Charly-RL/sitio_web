<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!estaAutenticado()) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit;
}

$conexion = conectarDB();

$usuario_id = $_POST['usuario_id'];

$sql="SELECT * FROM direcciones WHERE usuario_id = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$result = $stmt->execute();

if ($result) {
    $resultado = $stmt->get_result();
    $direcciones = [];
    
    while ($row = $resultado->fetch_assoc()) {
        $direcciones[] = $row;
    }
    
    echo json_encode(['direccion' => $direcciones]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener las direcciones']);
}

$conexion->close();
?>