<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
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
$data = json_decode(file_get_contents('php://input'), true);

switch ($metodo) {
    case 'GET':
        // Obtener productos en el carrito del usuario
        $sql = "SELECT c.*, p.nombre, p.precio, p.stock, p.imagen 
                FROM carrito c 
                JOIN productos p ON c.producto_id = p.id 
                WHERE c.usuario_id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        $carrito = [];
        while ($row = $resultado->fetch_assoc()) {
            $carrito[] = $row;
        }
        
        echo json_encode(['carrito' => $carrito]);
        break;

    case 'POST':
        // Agregar producto al carrito
        if (!isset($data['producto_id']) || !isset($data['cantidad'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos incompletos']);
            exit;
        }

        // Verificar stock disponible
        $stmt = $conexion->prepare("SELECT stock FROM productos WHERE id = ?");
        $stmt->bind_param("i", $data['producto_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $producto = $result->fetch_assoc();

        if (!$producto) {
            http_response_code(404);
            echo json_encode(['error' => 'Producto no encontrado']);
            exit;
        }

        if ($producto['stock'] < $data['cantidad']) {
            http_response_code(400);
            echo json_encode(['error' => 'Stock insuficiente']);
            exit;
        }

        // Intentar insertar o actualizar el carrito
        $sql = "INSERT INTO carrito (usuario_id, producto_id, cantidad) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad)";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iii", $usuario_id, $data['producto_id'], $data['cantidad']);
        
        if ($stmt->execute()) {
            echo json_encode(['mensaje' => 'Producto agregado al carrito']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al agregar al carrito']);
        }
        break;

    case 'PUT':
        // Actualizar cantidad de un producto en el carrito
        if (!isset($data['producto_id']) || !isset($data['cantidad'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos incompletos']);
            exit;
        }

        if ($data['cantidad'] <= 0) {
            // Si la cantidad es 0 o negativa, eliminar del carrito
            $sql = "DELETE FROM carrito WHERE usuario_id = ? AND producto_id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ii", $usuario_id, $data['producto_id']);
        } else {
            // Verificar stock disponible
            $stmt = $conexion->prepare("SELECT stock FROM productos WHERE id = ?");
            $stmt->bind_param("i", $data['producto_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $producto = $result->fetch_assoc();

            if ($producto['stock'] < $data['cantidad']) {
                http_response_code(400);
                echo json_encode(['error' => 'Stock insuficiente']);
                exit;
            }

            // Actualizar cantidad
            $sql = "UPDATE carrito SET cantidad = ? WHERE usuario_id = ? AND producto_id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("iii", $data['cantidad'], $usuario_id, $data['producto_id']);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['mensaje' => 'Carrito actualizado']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar el carrito']);
        }
        break;

    case 'DELETE':
        // Eliminar un producto del carrito o todo el carrito
        if (isset($data['producto_id'])) {
            // Eliminar un producto específico
            $sql = "DELETE FROM carrito WHERE usuario_id = ? AND producto_id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ii", $usuario_id, $data['producto_id']);
        } else {
            // Eliminar todo el carrito del usuario
            $sql = "DELETE FROM carrito WHERE usuario_id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $usuario_id);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['mensaje' => 'Producto(s) eliminado(s) del carrito']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar del carrito']);
        }
        break;
        // Crear nuevo pedido
        if (!isset($data['productos']) || empty($data['productos'])) {
            echo json_encode(['error' => 'No hay productos en el carrito']);
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
                // Obtener precio actual del producto
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
            echo json_encode(['success' => 'Pedido creado correctamente', 'pedido_id' => $pedido_id]);

        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $conexion->rollback();
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
        echo json_encode(['error' => 'Método no permitido']);
        break;
}

$conexion->close();