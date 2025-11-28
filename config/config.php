<?php
/**
 * Configurações Gerais da Aplicação
 */

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Definir caminho base da aplicação
define('BASE_PATH', dirname(dirname(__FILE__)));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('SRC_PATH', BASE_PATH . '/src');
define('CONFIG_PATH', BASE_PATH . '/config');
define('ASSETS_PATH', BASE_PATH . '/assets');

// URL base
define('BASE_URL', 'http://localhost:8000');

// Configurações de sessão
define('SESSION_TIMEOUT', 3600); // 1 hora

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir arquivo de banco de dados
require_once CONFIG_PATH . '/database.php';

// Função para redirecionar
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// Função para verificar se usuário está autenticado
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

// Função para obter usuário autenticado
function getAuthUser() {
    if (isAuthenticated()) {
        return getRow('SELECT id, name, email FROM users WHERE id = ?', [$_SESSION['user_id']]);
    }
    return null;
}

// Função para fazer logout
function logout() {
    session_destroy();
    redirect(BASE_URL . '/login.php');
}

// Função para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Função para fazer hash de senha
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Função para verificar senha
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Função para escapar string
function escape($string) {
    global $mysqli;
    return $mysqli->real_escape_string($string);
}

// Função para retornar JSON
function jsonResponse($data, $statusCode = 200) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Função para retornar erro JSON
function jsonError($message, $statusCode = 400) {
    jsonResponse(['error' => $message], $statusCode);
}

// Função para retornar sucesso JSON
function jsonSuccess($data = [], $statusCode = 200) {
    jsonResponse(['success' => true, 'data' => $data], $statusCode);
}
