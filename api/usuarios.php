<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$conexion = conectarDB();
$metodo = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

switch ($metodo) {
    case 'GET':
        // Obtener todos los usuarios o uno específico
        if (isset($_GET['id'])) {
            $id = $conexion->real_escape_string($_GET['id']);
            $sql = "SELECT id, nombre, email, tipo, fecha_registro FROM usuarios WHERE id = $id";
        } else {
            $sql = "SELECT id, nombre, email, tipo, fecha_registro FROM usuarios";
        }
        $resultado = $conexion->query($sql);
        $usuarios = [];
        while ($row = $resultado->fetch_assoc()) {
            $usuarios[] = $row;
        }
        echo json_encode(['usuarios' => $usuarios]);
        break;
    case 'PUT':
        // Solo admin puede editar
        if (!esAdmin()) {
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
        if (!isset($_GET['id'])) {
            echo json_encode(['error' => 'ID de usuario no especificado']);
            exit;
        }
        $id = $conexion->real_escape_string($_GET['id']);

        // Verificar si el usuario existe
        $stmt_check = $conexion->prepare("SELECT id FROM usuarios WHERE id = ?");
        $stmt_check->bind_param('i', $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows === 0) {
            echo json_encode(['error' => 'Usuario no encontrado']);
            break;
        }

        $campos = [];
        $tipos = '';
        $valores = [];
        if (isset($data['nombre'])) {
            $campos[] = 'nombre = ?';
            $tipos .= 's';
            $valores[] = $data['nombre'];
        }
        if (isset($data['email'])) {
            $campos[] = 'email = ?';
            $tipos .= 's';
            $valores[] = $data['email'];
        }
        if (isset($data['tipo'])) {
            // Permitir solo valores válidos
            $tipo = $data['tipo'];
            if (!in_array($tipo, ['admin', 'cliente', 'repartidor'])) {
                echo json_encode(['error' => 'Tipo de usuario no válido']);
                exit;
            }
            $campos[] = 'tipo = ?';
            $tipos .= 's';
            $valores[] = $tipo;
        }
        if (empty($campos)) {
            echo json_encode(['error' => 'No hay datos para actualizar']);
            exit;
        }
        $sql = "UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id = ?";
        $tipos .= 'i';
        $valores[] = $id;
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param($tipos, ...$valores);
        if ($stmt->execute()) {
            echo json_encode(['success' => 'Usuario actualizado correctamente']);
        } else {
            echo json_encode(['error' => 'Error al actualizar el usuario']);
        }
        break;

        
    case 'DELETE':
        // Solo admin puede eliminar
        if (!esAdmin()) {
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
        if (!isset($_GET['id'])) {
            echo json_encode(['error' => 'ID de usuario no especificado']);
            exit;
        }
        $id = $conexion->real_escape_string($_GET['id']);
        // No permitir eliminarse a sí mismo
        if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $id) {
            echo json_encode(['error' => 'No puedes eliminar tu propio usuario']);
            exit;
        }

        // Verificar si el usuario tiene pedidos asociados
        $sql_check = "SELECT COUNT(*) as total FROM pedidos WHERE usuario_id = ?";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->bind_param('i', $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $row_check = $result_check->fetch_assoc();
        if ($row_check['total'] > 0) {
            echo json_encode(['error' => 'No se puede eliminar el usuario porque tiene pedidos asociados']);
            break;
        }

        // Eliminar usuario
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => 'Usuario eliminado correctamente']);
            } else {
                echo json_encode(['error' => 'Usuario no encontrado']);
            }
        } else {
            echo json_encode(['error' => 'Error al eliminar el usuario']);
        }
        break;
    default:
        echo json_encode(['error' => 'Método no permitido']);
        break;
}
$conexion->close();
