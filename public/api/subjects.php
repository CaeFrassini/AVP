<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Verificar autenticação
if (!isAuthenticated()) {
    jsonError('Não autenticado', 401);
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    if ($method === 'GET' && $action === 'list') {
        // Listar disciplinas com progresso
        $subjects = getResults(
            'SELECT id, name, total_lessons FROM subjects WHERE user_id = ? ORDER BY name',
            [$userId]
        );
        
        foreach ($subjects as &$subject) {
            $completedLessons = getValue(
                'SELECT COUNT(*) FROM lessons WHERE user_id = ? AND subject_id = ?',
                [$userId, $subject['id']]
            );
            
            $subject['completed_lessons'] = (int)$completedLessons;
            $subject['remaining'] = max(0, $subject['total_lessons'] - $completedLessons);
            $subject['percentage'] = $subject['total_lessons'] > 0 
                ? round(($completedLessons / $subject['total_lessons']) * 100)
                : 0;
        }
        
        jsonSuccess($subjects);
    }
    
    elseif ($method === 'POST' && $action === 'create') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $name = trim($data['name'] ?? '');
        $totalLessons = (int)($data['total_lessons'] ?? 0);
        
        if (empty($name)) {
            jsonError('Nome da disciplina é obrigatório');
        }
        
        $id = insert(
            'INSERT INTO subjects (user_id, name, total_lessons) VALUES (?, ?, ?)',
            [$userId, $name, $totalLessons]
        );
        
        jsonSuccess(['id' => $id, 'name' => $name, 'total_lessons' => $totalLessons]);
    }
    
    elseif ($method === 'PUT' && $action === 'update') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $id = (int)($data['id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $totalLessons = (int)($data['total_lessons'] ?? 0);
        
        if ($id <= 0) {
            jsonError('ID inválido');
        }
        
        // Verificar se pertence ao usuário
        $subject = getRow('SELECT id FROM subjects WHERE id = ? AND user_id = ?', [$id, $userId]);
        if (!$subject) {
            jsonError('Disciplina não encontrada', 404);
        }
        
        if (!empty($name)) {
            update('UPDATE subjects SET name = ? WHERE id = ?', [$name, $id]);
        }
        
        if ($totalLessons > 0) {
            update('UPDATE subjects SET total_lessons = ? WHERE id = ?', [$totalLessons, $id]);
        }
        
        jsonSuccess(['success' => true]);
    }
    
    elseif ($method === 'DELETE' && $action === 'delete') {
        $id = (int)($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            jsonError('ID inválido');
        }
        
        // Verificar se pertence ao usuário
        $subject = getRow('SELECT id FROM subjects WHERE id = ? AND user_id = ?', [$id, $userId]);
        if (!$subject) {
            jsonError('Disciplina não encontrada', 404);
        }
        
        delete('DELETE FROM subjects WHERE id = ?', [$id]);
        
        jsonSuccess(['success' => true]);
    }
    
    else {
        jsonError('Ação não encontrada', 404);
    }
    
} catch (Exception $e) {
    jsonError('Erro: ' . $e->getMessage(), 500);
}
