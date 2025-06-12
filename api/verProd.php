<?php
// API para obtener los productos de un pedido específico

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

// Obtener el ID del pedido desde POST
$id = $_POST['id'];

// Consulta para obtener los detalles del pedido y los productos asociados
$sql = "SELECT p.*, 
            GROUP_CONCAT(CONCAT(dp.cantidad, 'x ', pr.nombre) SEPARATOR ', ') as productos
        FROM pedidos p
        JOIN detalles_pedido dp ON p.id = dp.pedido_id
        JOIN productos pr ON dp.producto_id = pr.id
        WHERE p.id = ?
        GROUP BY p.id
        ORDER BY p.fecha_pedido DESC";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $id);
$result = $stmt->execute();

if ($result) {
    $resultado = $stmt->get_result();
    $pedido = [];
    // Recorrer los resultados y agregarlos al array
    while ($row = $resultado->fetch_assoc()) {
        $pedido[] = $row;
    }
    // Responder con los productos del pedido
    echo json_encode(['pedidos' => $pedido]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener los productos del pedido']);
}

// Cerrar la conexión a la base de datos
$conexion->close();
?>