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
        // Obtener direcciones del usuario
        $sql = "SELECT * FROM direcciones WHERE usuario_id = ? ORDER BY es_principal DESC, fecha_creacion DESC";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        $direcciones = [];
        while ($row = $resultado->fetch_assoc()) {
            $direcciones[] = $row;
        }
        
        echo json_encode(['direcciones' => $direcciones]);
        break;

    case 'POST':
        // Agregar nueva dirección
        if ($data['es_principal']) {
            // Si la nueva dirección será principal, quitar el estado principal de las demás
            $stmt = $conexion->prepare("UPDATE direcciones SET es_principal = 0 WHERE usuario_id = ?");
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
        }

        $sql = "INSERT INTO direcciones (usuario_id, calle, numero_ext, numero_int, colonia, ciudad, 
                                       estado, codigo_postal, telefono, instrucciones_entrega, es_principal) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("isssssssssi", 
            $usuario_id,
            $data['calle'],
            $data['numero_ext'],
            $data['numero_int'],
            $data['colonia'],
            $data['ciudad'],
            $data['estado'],
            $data['codigo_postal'],
            $data['telefono'],
            $data['instrucciones_entrega'],
            $data['es_principal']
        );

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $conexion->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar la dirección']);
        }
        break;

    case 'PUT':
        // Actualizar dirección existente
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de dirección no especificado']);
            exit;
        }

        $id = $_GET['id'];
        
        // Verificar que la dirección pertenezca al usuario
        $stmt = $conexion->prepare("SELECT id FROM direcciones WHERE id = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $id, $usuario_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }

        if ($data['es_principal']) {
            // Si la dirección será principal, quitar el estado principal de las demás
            $stmt = $conexion->prepare("UPDATE direcciones SET es_principal = 0 WHERE usuario_id = ?");
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
        }

        $sql = "UPDATE direcciones SET 
                calle = ?, numero_ext = ?, numero_int = ?, colonia = ?, 
                ciudad = ?, estado = ?, codigo_postal = ?, telefono = ?, 
                instrucciones_entrega = ?, es_principal = ? 
                WHERE id = ? AND usuario_id = ?";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssssssssiis", 
            $data['calle'],
            $data['numero_ext'],
            $data['numero_int'],
            $data['colonia'],
            $data['ciudad'],
            $data['estado'],
            $data['codigo_postal'],
            $data['telefono'],
            $data['instrucciones_entrega'],
            $data['es_principal'],
            $id,
            $usuario_id
        );

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar la dirección']);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de dirección no especificado']);
            exit;
        }

        $id = $_GET['id'];
        
        // Verificar que la dirección pertenezca al usuario
        $stmt = $conexion->prepare("DELETE FROM direcciones WHERE id = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $id, $usuario_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar la dirección']);
        }
        break;
}
