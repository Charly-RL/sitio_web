<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Función para registrar un nuevo usuario
function registrarUsuario($nombre, $email, $password) {
    $conexion = conectarDB();
    
    // Validar que el email no exista
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        return ['error' => 'El email ya está registrado'];
    }
    
    // Insertar usuario con contraseña en texto plano
    $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombre, $email, $password);
    
    if ($stmt->execute()) {
        return ['success' => 'Usuario registrado correctamente'];
    } else {
        return ['error' => 'Error al registrar el usuario'];
    }
}

// Función para iniciar sesión
function login($email, $password) {
    $conexion = conectarDB();
    
    $stmt = $conexion->prepare("SELECT id, nombre, password, tipo FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        
        if ($password === $usuario['password']) {
            // Crear sesión
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_tipo'] = $usuario['tipo'];
            
            return ['success' => 'Inicio de sesión exitoso'];
        }
    }
    
    return ['error' => 'Email o contraseña incorrectos'];
}

// Función para verificar si el usuario está autenticado
function estaAutenticado() {
    return isset($_SESSION['usuario_id']);
}

// Función para verificar si el usuario es administrador
function esAdmin() {
    return isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin';
}

// Función para cerrar sesión
function logout() {
    session_unset();
    session_destroy();
    return ['success' => 'Sesión cerrada correctamente'];
}