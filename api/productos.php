<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');



require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Obtener conexión
$conexion = conectarDB();

// Obtener método de la solicitud
$metodo = $_SERVER['REQUEST_METHOD'];

// Obtener datos de la solicitud
$data = json_decode(file_get_contents('php://input'), true);

switch ($metodo) {
    case 'GET':
        // Obtener un producto específico o todos los productos
        if (isset($_GET['id'])) {
            $id = $conexion->real_escape_string($_GET['id']);
            $sql = "SELECT * FROM productos WHERE id = $id";
        } else {
            $sql = "SELECT * FROM productos";
        }

        $resultado = $conexion->query($sql);
        $productos = [];

        while ($row = $resultado->fetch_assoc()) {
            // Calcular estado de stock
            $stock = (int)$row['stock'];
            if ($stock <= 2) {
                $row['stock_estado'] = 'Crítico';
            } elseif ($stock <= 5) {
                $row['stock_estado'] = 'Bajo';
            } elseif ($stock <= 15) {
                $row['stock_estado'] = 'Normal';
            } else {
                $row['stock_estado'] = 'Abundante';
            }
            $productos[] = $row;
        }

        echo json_encode(['productos' => $productos]);
        break;
        
    case 'POST':
        // Verificar si el usuario es administrador
        if (!esAdmin()) {
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
        
        // Validar datos requeridos
        if (!isset($data['nombre']) || !isset($data['precio']) || !isset($data['stock'])) {
            echo json_encode(['error' => 'Faltan datos requeridos']);
            exit;
        }
        
        // Preparar la consulta
        $stmt = $conexion->prepare("INSERT INTO productos (nombre, descripcion, precio, stock, imagen) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdis", 
            $data['nombre'],
            $data['descripcion'],
            $data['precio'],
            $data['stock'],
            $data['imagen']
        );
        
        if ($stmt->execute()) {
            echo json_encode(['success' => 'Producto creado correctamente', 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['error' => 'Error al crear el producto']);
        }
        break;
        
    case 'PUT':
        // Verificar si el usuario es administrador
        if (!esAdmin()) {
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
        
        if (!isset($_GET['id'])) {
            echo json_encode(['error' => 'ID de producto no especificado']);
            exit;
        }
        
        $id = $conexion->real_escape_string($_GET['id']);
        
        // Construir la consulta de actualización
        $campos = [];
        $tipos = '';
        $valores = [];
        
        if (isset($data['nombre'])) {
            $campos[] = 'nombre = ?';
            $tipos .= 's';
            $valores[] = $data['nombre'];
        }
        if (isset($data['descripcion'])) {
            $campos[] = 'descripcion = ?';
            $tipos .= 's';
            $valores[] = $data['descripcion'];
        }
        if (isset($data['precio'])) {
            $campos[] = 'precio = ?';
            $tipos .= 'd';
            $valores[] = $data['precio'];
        }
        if (isset($data['stock'])) {
            $campos[] = 'stock = ?';
            $tipos .= 'i';
            $valores[] = $data['stock'];
        }

        if (isset($data['imagen'])) {
            $campos[] = 'imagen = ?';
            $tipos .= 's';
            $valores[] = $data['imagen'];
        }
        
        if (empty($campos)) {
            echo json_encode(['error' => 'No hay datos para actualizar']);
            exit;
        }
        
        $sql = "UPDATE productos SET " . implode(', ', $campos) . " WHERE id = ?";
        $tipos .= 'i';
        $valores[] = $id;
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param($tipos, ...$valores);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => 'Producto actualizado correctamente']);
        } else {
            echo json_encode(['error' => 'Error al actualizar el producto']);
        }
        break;
        
    case 'DELETE':
        // Verificar si el usuario es administrador
        if (!esAdmin()) {
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
        
        if (!isset($data['id'])) {
            echo json_encode(['error' => 'ID de producto no especificado']);
            exit;
        }
        
        $id = $conexion->real_escape_string($data['id']);
        
        // Verificar si el producto existe y eliminar
        $stmt = $conexion->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => 'Producto eliminado correctamente']);
            } else {
                echo json_encode(['error' => 'Producto no encontrado']);
            }
        } else {
            echo json_encode(['error' => 'Error al eliminar el producto']);
        }
        break;
    
    default:
        echo json_encode(['error' => 'Método no permitido']);
        break;
}

$conexion->close();