<?php
/**
 * Configuração do Banco de Dados
 */

// Dados de conexão
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'avp_controle_estudos');

// Criar conexão
try {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Verificar conexão
    if ($mysqli->connect_error) {
        throw new Exception("Erro na conexão: " . $mysqli->connect_error);
    }
    
    // Definir charset
    $mysqli->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}

// Função para executar query
function executeQuery($sql, $params = []) {
    global $mysqli;
    
    $stmt = $mysqli->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Erro na preparação da query: " . $mysqli->error);
    }
    
    if (!empty($params)) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Erro na execução da query: " . $stmt->error);
    }
    
    return $stmt;
}

// Função para obter resultado como array
function getResults($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    $result = $stmt->get_result();
    $rows = [];
    
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    
    $stmt->close();
    return $rows;
}

// Função para obter uma linha
function getRow($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row;
}

// Função para obter um valor
function getValue($sql, $params = []) {
    $row = getRow($sql, $params);
    if ($row) {
        return array_values($row)[0];
    }
    return null;
}

// Função para inserir
function insert($sql, $params = []) {
    global $mysqli;
    $stmt = executeQuery($sql, $params);
    $id = $mysqli->insert_id;
    $stmt->close();
    return $id;
}

// Função para atualizar
function update($sql, $params = []) {
    global $mysqli;
    $stmt = executeQuery($sql, $params);
    $affected = $mysqli->affected_rows;
    $stmt->close();
    return $affected;
}

// Função para deletar
function delete($sql, $params = []) {
    global $mysqli;
    $stmt = executeQuery($sql, $params);
    $affected = $mysqli->affected_rows;
    $stmt->close();
    return $affected;
}
