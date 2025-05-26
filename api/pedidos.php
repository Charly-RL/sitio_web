<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar si el usuario está autenticado
if (!estaAutenticado()) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit;
}

$conexion = conectarDB();
$usuario_id = $_SESSION['usuario_id'];
$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['productos']) || empty($data['productos'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No hay productos en el pedido']);
            exit;
        }

        // Iniciar transacción
        $conexion->begin_transaction();

        try {
            // Calcular total del pedido
            $total = 0;
            foreach ($data['productos'] as $producto) {
                $stmt = $conexion->prepare("SELECT precio, stock FROM productos WHERE id = ?");
                $stmt->bind_param("i", $producto['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $prod = $result->fetch_assoc();

                if (!$prod) {
                    throw new Exception("Producto no encontrado");
                }

                if ($prod['stock'] < $producto['cantidad']) {
                    throw new Exception("Stock insuficiente");
                }

                $total += $prod['precio'] * $producto['cantidad'];
            }

            // Crear pedido
            $stmt = $conexion->prepare("INSERT INTO pedidos (usuario_id, total) VALUES (?, ?)");
            $stmt->bind_param("id", $usuario_id, $total);
            $stmt->execute();
            $pedido_id = $stmt->insert_id;

            // Insertar detalles del pedido y actualizar stock
            foreach ($data['productos'] as $producto) {
                $stmt = $conexion->prepare("SELECT precio FROM productos WHERE id = ?");
                $stmt->bind_param("i", $producto['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $prod = $result->fetch_assoc();

                // Insertar detalle
                $subtotal = $prod['precio'] * $producto['cantidad'];
                $stmt = $conexion->prepare("INSERT INTO detalles_pedido (pedido_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiidi", $pedido_id, $producto['id'], $producto['cantidad'], $prod['precio'], $subtotal);
                $stmt->execute();

                // Actualizar stock
                $stmt = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                $stmt->bind_param("ii", $producto['cantidad'], $producto['id']);
                $stmt->execute();
            }

            // Confirmar transacción
            $conexion->commit();
            echo json_encode(['success' => true, 'pedido_id' => $pedido_id]);

        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $conexion->rollback();
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'GET':
        // Obtener pedidos del usuario
        $sql = "SELECT p.*, 
                GROUP_CONCAT(CONCAT(dp.cantidad, 'x ', pr.nombre) SEPARATOR ', ') as productos
                FROM pedidos p
                JOIN detalles_pedido dp ON p.id = dp.pedido_id
                JOIN productos pr ON dp.producto_id = pr.id
                WHERE p.usuario_id = ?
                GROUP BY p.id
                ORDER BY p.fecha_pedido DESC";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $pedidos = [];
        while ($row = $resultado->fetch_assoc()) {
            $pedidos[] = $row;
        }

        echo json_encode(['pedidos' => $pedidos]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
}

$conexion->close();
