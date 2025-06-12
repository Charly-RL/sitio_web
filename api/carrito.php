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


// --- API de Carrito y Pedidos ---
switch ($metodo) {
    // Obtener productos en el carrito del usuario
    case 'GET':
        // Si se solicita el carrito
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

    // Agregar producto al carrito o crear pedido
    case 'POST':
        // Si se envía un array de productos, es un pedido
        if (isset($data['productos']) && is_array($data['productos'])) {
            // --- Crear nuevo pedido ---
            if (empty($data['productos'])) {
                echo json_encode(['error' => 'No hay productos en el carrito']);
                exit;
            }
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
                    $subtotal = $prod['precio'] * $producto['cantidad'];
                    $stmt = $conexion->prepare("INSERT INTO detalles_pedido (pedido_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iiidi", $pedido_id, $producto['id'], $producto['cantidad'], $prod['precio'], $subtotal);
                    $stmt->execute();
                    $stmt = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                    $stmt->bind_param("ii", $producto['cantidad'], $producto['id']);
                    $stmt->execute();
                }
                $conexion->commit();
                echo json_encode(['success' => 'Pedido creado correctamente', 'pedido_id' => $pedido_id]);
            } catch (Exception $e) {
                $conexion->rollback();
                echo json_encode(['error' => $e->getMessage()]);
            }
            break;
        }
        // --- Agregar producto al carrito ---
        if (!isset($data['producto_id']) || !isset($data['cantidad'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos incompletos']);
            exit;
        }
        $conexion->begin_transaction();
        try {
            $stmt = $conexion->prepare("SELECT stock FROM productos WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $data['producto_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $producto = $result->fetch_assoc();
            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }
            if ($producto['stock'] < $data['cantidad']) {
                throw new Exception('Stock insuficiente');
            }
            $sql = "INSERT INTO carrito (usuario_id, producto_id, cantidad) "+
                   "VALUES (?, ?, ?) "+
                   "ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("iii", $usuario_id, $data['producto_id'], $data['cantidad']);
            $stmt->execute();
            $stmt = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
            $stmt->bind_param("ii", $data['cantidad'], $data['producto_id']);
            $stmt->execute();
            $conexion->commit();
            echo json_encode(['mensaje' => 'Producto agregado al carrito']);
        } catch (Exception $e) {
            $conexion->rollback();
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // Actualizar cantidad de un producto en el carrito
    case 'PUT':
        // ... (igual que antes, puedes agregar comentarios aquí si lo deseas)
        // Actualiza la cantidad de un producto en el carrito y ajusta el stock
        if (!isset($data['producto_id']) || !isset($data['cantidad'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos incompletos']);
            exit;
        }
        $conexion->begin_transaction();
        try {
            $stmt = $conexion->prepare("SELECT cantidad FROM carrito WHERE usuario_id = ? AND producto_id = ?");
            $stmt->bind_param("ii", $usuario_id, $data['producto_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $carrito_actual = $result->fetch_assoc();
            if (!$carrito_actual) {
                throw new Exception('Producto no encontrado en el carrito');
            }
            $diferencia = $data['cantidad'] - $carrito_actual['cantidad'];
            if ($data['cantidad'] <= 0) {
                // Si la cantidad es 0 o negativa, eliminar del carrito y devolver stock
                $stmt = $conexion->prepare("DELETE FROM carrito WHERE usuario_id = ? AND producto_id = ?");
                $stmt->bind_param("ii", $usuario_id, $data['producto_id']);
                $stmt->execute();
                $stmt = $conexion->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
                $stmt->bind_param("ii", $carrito_actual['cantidad'], $data['producto_id']);
                $stmt->execute();
            } else {
                // Verificar stock disponible
                $stmt = $conexion->prepare("SELECT stock FROM productos WHERE id = ? FOR UPDATE");
                $stmt->bind_param("i", $data['producto_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $producto = $result->fetch_assoc();
                if ($diferencia > 0 && $producto['stock'] < $diferencia) {
                    throw new Exception('Stock insuficiente');
                }
                // Actualizar cantidad en carrito
                $stmt = $conexion->prepare("UPDATE carrito SET cantidad = ? WHERE usuario_id = ? AND producto_id = ?");
                $stmt->bind_param("iii", $data['cantidad'], $usuario_id, $data['producto_id']);
                $stmt->execute();
                // Actualizar stock del producto
                $stmt = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                $stmt->bind_param("ii", $diferencia, $data['producto_id']);
                $stmt->execute();
            }
            $conexion->commit();
            echo json_encode(['mensaje' => 'Carrito actualizado']);
        } catch (Exception $e) {
            $conexion->rollback();
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // Eliminar producto(s) del carrito
    case 'DELETE':
        // ... (igual que antes, puedes agregar comentarios aquí si lo deseas)
        // Elimina un producto específico o todo el carrito y ajusta el stock
        $conexion->begin_transaction();
        try {
            if (isset($data['producto_id'])) {
                $stmt = $conexion->prepare("SELECT cantidad FROM carrito WHERE usuario_id = ? AND producto_id = ?");
                $stmt->bind_param("ii", $usuario_id, $data['producto_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $carrito_actual = $result->fetch_assoc();
                if ($carrito_actual) {
                    $stmt = $conexion->prepare("DELETE FROM carrito WHERE usuario_id = ? AND producto_id = ?");
                    $stmt->bind_param("ii", $usuario_id, $data['producto_id']);
                    $stmt->execute();
                    $stmt = $conexion->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
                    $stmt->bind_param("ii", $carrito_actual['cantidad'], $data['producto_id']);
                    $stmt->execute();
                }
            } else {
                $stmt = $conexion->prepare("SELECT producto_id, cantidad FROM carrito WHERE usuario_id = ?");
                $stmt->bind_param("i", $usuario_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $productos_carrito = $result->fetch_all(MYSQLI_ASSOC);
                $stmt = $conexion->prepare("DELETE FROM carrito WHERE usuario_id = ?");
                $stmt->bind_param("i", $usuario_id);
                $stmt->execute();
                foreach ($productos_carrito as $item) {
                    $stmt = $conexion->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
                    $stmt->bind_param("ii", $item['cantidad'], $item['producto_id']);
                    $stmt->execute();
                }
            }
            $conexion->commit();
            echo json_encode(['mensaje' => 'Producto(s) eliminado(s) del carrito']);
        } catch (Exception $e) {
            $conexion->rollback();
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar del carrito: ' . $e->getMessage()]);
        }
        break;

    // Obtener historial de pedidos del usuario
    case 'HISTORIAL': // No estándar, solo ejemplo de comentario
    // case 'GET_PEDIDOS':
    //     ...

    // Método no permitido
    default:
        echo json_encode(['error' => 'Método no permitido']);
        break;
}

$conexion->close();