<?php
// Configuración de cabeceras para API REST
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../includes/auth.php';

// Obtener datos de la solicitud (JSON)
$data = json_decode(file_get_contents('php://input'), true);
// Determinar la acción solicitada (registro, login, logout, verificar)
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';

switch ($accion) {
    // Registro de un nuevo usuario
    case 'registro':
        // Validar datos requeridos
        if (!isset($data['nombre']) || !isset($data['email']) || !isset($data['password'])) {
            echo json_encode(['error' => 'Faltan datos requeridos']);
            exit;
        }
        // Llama a la función de registro (ver includes/auth.php)
        $resultado = registrarUsuario($data['nombre'], $data['email'], $data['password']);
        echo json_encode($resultado);
        break;

    // Inicio de sesión de usuario
    case 'login':
        // Validar datos requeridos
        if (!isset($data['email']) || !isset($data['password'])) {
            echo json_encode(['error' => 'Faltan datos requeridos']);
            exit;
        }
        // Llama a la función de login (ver includes/auth.php)
        $resultado = login($data['email'], $data['password']);
        echo json_encode($resultado);
        break;

    // Cierre de sesión (logout)
    case 'logout':
        // Llama a la función de logout (ver includes/auth.php)
        $resultado = logout();
        echo json_encode($resultado);
        break;

    // Verificar estado de autenticación y tipo de usuario
    case 'verificar':
        // Devuelve información sobre la sesión actual
        echo json_encode([
            'autenticado' => estaAutenticado(), // true si hay sesión activa
            'es_admin' => esAdmin(),            // true si es administrador
            'es_repartidor' => esRepartidor(),  // true si es repartidor
            'tipo' => isset($_SESSION['usuario_tipo']) ? $_SESSION['usuario_tipo'] : null // tipo textual
        ]);
        break;

    // Acción no válida o no soportada
    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}