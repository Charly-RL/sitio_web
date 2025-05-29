<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';


// Permitir acceso a admin para GET y PUT, y solo vendedor para POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!estaAutenticado() || !esVendedor()) {
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
    if (!estaAutenticado() || !esAdmin()) {
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }
}
// GET: Listar peticiones para admin
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['admin'])) {
    $conexion = conectarDB();
    $sql = "SELECT p.id, u.nombre AS vendedor, pr.nombre AS producto, p.mensaje, p.estado, p.fecha
            FROM peticiones p
            JOIN usuarios u ON p.usuario_id = u.id
            JOIN productos pr ON p.producto_id = pr.id
            ORDER BY p.fecha DESC";
    $result = $conexion->query($sql);
    $peticiones = [];
    while ($row = $result->fetch_assoc()) {
        $peticiones[] = $row;
    }
    echo json_encode(['peticiones' => $peticiones]);
    exit;
}

// PUT: Cambiar estado de petición
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['id']) || !isset($data['estado'])) {
        echo json_encode(['error' => 'Faltan datos requeridos']);
        exit;
    }
    $id = intval($data['id']);
    $estado = $data['estado'];
    $conexion = conectarDB();
    $stmt = $conexion->prepare("UPDATE peticiones SET estado = ? WHERE id = ?");
    $stmt->bind_param('si', $estado, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Petición actualizada']);
    } else {
        echo json_encode(['error' => 'Error al actualizar la petición']);
    }
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($data['producto_id']) || !isset($data['mensaje'])) {
        echo json_encode(['error' => 'Faltan datos requeridos']);
        exit;
    }
    $producto_id = intval($data['producto_id']);
    $mensaje = trim($data['mensaje']);
    $usuario_id = $_SESSION['usuario_id'];

    $conexion = conectarDB();
    $stmt = $conexion->prepare("INSERT INTO peticiones (usuario_id, producto_id, mensaje, estado, fecha) VALUES (?, ?, ?, 'pendiente', NOW())");
    $stmt->bind_param('iis', $usuario_id, $producto_id, $mensaje);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Petición enviada correctamente']);
    } else {
        echo json_encode(['error' => 'Error al guardar la petición']);
    }
    exit;
}

echo json_encode(['error' => 'Método no permitido']);
