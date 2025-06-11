<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET,  PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';


$conexion = conectarDB();
$usuario_id = $_SESSION['usuario_id'];
$metodo = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

switch ($metodo) {
    case 'POST':
        //$data = json_decode(file_get_contents('php://input'), true);
        
        // Verificar dirección y método de pago
        if (!isset($data['direccion_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No se especificó la dirección de envío']);
            exit;
        }

        if (!isset($data['metodo_pago'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No se especificó el método de pago']);
            exit;
        }

        // Verificar que la dirección pertenezca al usuario
        $stmt = $conexion->prepare("SELECT id FROM direcciones WHERE id = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $data['direccion_id'], $usuario_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Dirección de envío no válida']);
            exit;
        }

        $conexion->begin_transaction();

        try {
            // Obtener productos del carrito
            $stmt = $conexion->prepare("SELECT c.*, p.precio, p.stock FROM carrito c 
                                      JOIN productos p ON c.producto_id = p.id 
                                      WHERE c.usuario_id = ?");
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("El carrito está vacío");
            }

            $productos = [];
            $total = 0;

            while ($item = $result->fetch_assoc()) {
                if ($item['stock'] < $item['cantidad']) {
                    throw new Exception("Stock insuficiente para " . $item['nombre']);
                }
                $subtotal = $item['precio'] * $item['cantidad'];
                $total += $subtotal;
                $productos[] = $item;
            }

            // Crear pedido
            $stmt = $conexion->prepare("INSERT INTO pedidos (usuario_id, direccion_id, total, metodo_pago) 
                                      VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iids", $usuario_id, $data['direccion_id'], $total, $data['metodo_pago']);
            $stmt->execute();
            $pedido_id = $conexion->insert_id;

            // Insertar detalles del pedido y actualizar stock
            foreach ($productos as $producto) {
                $subtotal = $producto['precio'] * $producto['cantidad'];
                
                $stmt = $conexion->prepare("INSERT INTO detalles_pedido (pedido_id, producto_id, cantidad, 
                                          precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiidi", $pedido_id, $producto['producto_id'], 
                                $producto['cantidad'], $producto['precio'], $subtotal);
                $stmt->execute();

                // Actualizar stock
                $stmt = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                $stmt->bind_param("ii", $producto['cantidad'], $producto['producto_id']);
                $stmt->execute();
            }

            // Limpiar carrito
            $stmt = $conexion->prepare("DELETE FROM carrito WHERE usuario_id = ?");
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();

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
        // Endpoint para productos más vendidos
        if (isset($_GET['accion']) && $_GET['accion'] === 'masvendidos') {
            $where = "";
            $params = [];
            $types = "";

            if (!empty($_GET['inicio'])) {
                $where .= " AND peds.fecha_pedido >= ? ";
                $params[] = $_GET['inicio'];
                $types .= "s";
            }
            if (!empty($_GET['fin'])) {
                $where .= " AND peds.fecha_pedido <= ? ";
                $params[] = $_GET['fin'] . " 23:59:59";
                $types .= "s";
            }

            $sql = "SELECT p.id, p.nombre, SUM(dp.cantidad) as cantidad_vendida
                    FROM productos p
                    JOIN detalles_pedido dp ON p.id = dp.producto_id
                    JOIN pedidos peds ON dp.pedido_id = peds.id
                    WHERE 1=1 $where
                    GROUP BY p.id, p.nombre
                    ORDER BY cantidad_vendida DESC
                    LIMIT 10";

            $stmt = $conexion->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $productos = [];
            while ($row = $result->fetch_assoc()) {
                $productos[] = $row;
            }
            echo json_encode(['productos' => $productos]);
            break;
        }

        // Endpoint para estadísticas generales y ventas por semana/día
        if (isset($_GET['accion']) && $_GET['accion'] === 'estadisticas') {
            // Total ventas y pedidos
            $sql = "SELECT COUNT(*) as total_pedidos, IFNULL(SUM(total),0) as total_ventas FROM pedidos";
            $result = $conexion->query($sql);
            $row = $result->fetch_assoc();
            $total_pedidos = (int)$row['total_pedidos'];
            $total_ventas = (float)$row['total_ventas'];

            // Ventas por día (últimos 7 días)
            $sql = "SELECT DATE(fecha_pedido) as dia, IFNULL(SUM(total),0) as total
                    FROM pedidos
                    WHERE fecha_pedido >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                    GROUP BY dia
                    ORDER BY dia ASC";
            $result = $conexion->query($sql);
            $ventas_por_dia = [];
            while ($row = $result->fetch_assoc()) {
                $ventas_por_dia[] = [
                    'dia' => $row['dia'],
                    'total' => $row['total']
                ];
            }

            // Ventas por semana (últimas 4 semanas)
            $sql = "SELECT YEARWEEK(fecha_pedido, 1) as semana, MIN(DATE(fecha_pedido)) as inicio_semana, IFNULL(SUM(total),0) as total
                    FROM pedidos
                    WHERE fecha_pedido >= DATE_SUB(CURDATE(), INTERVAL 28 DAY)
                    GROUP BY semana
                    ORDER BY semana ASC";
            $result = $conexion->query($sql);
            $ventas_por_semana = [];
            while ($row = $result->fetch_assoc()) {
                $ventas_por_semana[] = [
                    'semana' => $row['semana'],
                    'inicio_semana' => $row['inicio_semana'],
                    'total' => $row['total']
                ];
            }

            echo json_encode([
                'success' => true,
                'total_pedidos' => $total_pedidos,
                'total_ventas' => $total_ventas,
                'ventas_por_dia' => $ventas_por_dia,
                'ventas_por_semana' => $ventas_por_semana
            ]);
            break;
        }

        // Obtener pedidos: admin ve todos, usuario solo los suyos
        if (function_exists('esAdmin') && esAdmin() || function_exists('esRepartidor') && esRepartidor()) {
            // Si es admin o repartidor, obtener todos los pedidos, incluyendo el nombre del cliente
            $sql = "SELECT p.*, u.nombre AS cliente_nombre, 
                    GROUP_CONCAT(CONCAT(dp.cantidad, 'x ', pr.nombre) SEPARATOR ', ') as productos
                    FROM pedidos p
                    JOIN detalles_pedido dp ON p.id = dp.pedido_id
                    JOIN productos pr ON dp.producto_id = pr.id
                    JOIN usuarios u ON p.usuario_id = u.id
                    GROUP BY p.id
                    ORDER BY p.fecha_pedido DESC";
            $stmt = $conexion->prepare($sql);
        } else {
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
        }
        $stmt->execute();
        $resultado = $stmt->get_result();

        $pedidos = [];
        while ($row = $resultado->fetch_assoc()) {
            $pedidos[] = $row;
        }

        echo json_encode(['pedidos' => $pedidos]);
        break;
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        // Actualizar estado de un pedido
        if (!isset($data['id']) || !isset($data['estado'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de pedido o estado no especificado']);
            exit;
        }

        $pedido_id = (int)$data['id'];
        $nuevo_estado = $data['estado'];

        // Validar estado
        $estados_validos = ['pendiente', 'enviado', 'entregado', 'procesando'];
        if (!in_array($nuevo_estado, $estados_validos)) {
            http_response_code(400);
            echo json_encode(['error' => 'Estado no válido']);
            exit;
        }

        // Verificar que el pedido pertenezca al usuario
        $stmt = $conexion->prepare("SELECT id FROM pedidos WHERE id = ?");
        $stmt->bind_param("i", $pedido_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Pedido no encontrado o no autorizado']);
            exit;
        }

        // Actualizar estado del pedido
        $stmt = $conexion->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevo_estado, $pedido_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar el pedido']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
}

$conexion->close();
