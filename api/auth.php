<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../includes/auth.php';

// Obtener datos de la solicitud
$data = json_decode(file_get_contents('php://input'), true);
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';

switch ($accion) {
    case 'registro':
        if (!isset($data['nombre']) || !isset($data['email']) || !isset($data['password'])) {
            echo json_encode(['error' => 'Faltan datos requeridos']);
            exit;
        }
        
        $resultado = registrarUsuario($data['nombre'], $data['email'], $data['password']);
        echo json_encode($resultado);
        break;
        
    case 'login':
        if (!isset($data['email']) || !isset($data['password'])) {
            echo json_encode(['error' => 'Faltan datos requeridos']);
            exit;
        }
        
        $resultado = login($data['email'], $data['password']);
        echo json_encode($resultado);
        break;
        
    case 'logout':
        $resultado = logout();
        echo json_encode($resultado);
        break;
        
    case 'verificar':
        echo json_encode([
            'autenticado' => estaAutenticado(),
            'es_admin' => esAdmin(),
            'es_repartidor' => esRepartidor(),
            'tipo' => isset($_SESSION['usuario_tipo']) ? $_SESSION['usuario_tipo'] : null
        ]);
        break;
        
    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}