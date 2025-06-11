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

 $sql = "SELECT p.*, 
                    GROUP_CONCAT(CONCAT(dp.cantidad, 'x ', pr.nombre) SEPARATOR ', ') as productos
                    FROM pedidos p
                    JOIN detalles_pedido dp ON p.id = dp.pedido_id
                    JOIN productos pr ON dp.producto_id = pr.id
                    WHERE p.usuario_id = ?
                    GROUP BY p.id
                    ORDER BY p.fecha_pedido DESC";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $usuario_id);
$result = $stmt->execute();

if ($result) {
    $resultado = $stmt->get_result();
    $pedido = [];
    
    while ($row = $resultado->fetch_assoc()) {
        $pedido[] = $row;
    }
    
    echo json_encode(['pedidos' => $pedido]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener los productos del pedido']);
}

$conexion->close();
?>