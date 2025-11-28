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
        $status = $_GET['status'] ?? '';
        
        if (!empty($status) && in_array($status, ['pending', 'completed', 'skipped'])) {
            $reviews = getResults(
                'SELECT id, lesson_id, subject_id, lesson_number, review_type, scheduled_date, status, completed_at 
                 FROM reviews WHERE user_id = ? AND status = ? ORDER BY scheduled_date',
                [$userId, $status]
            );
        } else {
            $reviews = getResults(
                'SELECT id, lesson_id, subject_id, lesson_number, review_type, scheduled_date, status, completed_at 
                 FROM reviews WHERE user_id = ? ORDER BY scheduled_date',
                [$userId]
            );
        }
        
        jsonSuccess($reviews);
    }
    
    elseif ($method === 'PUT' && $action === 'update') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $id = (int)($data['id'] ?? 0);
        $status = $data['status'] ?? '';
        
        if ($id <= 0 || !in_array($status, ['completed', 'skipped'])) {
            jsonError('Dados inválidos');
        }
        
        // Verificar se revisão pertence ao usuário
        $review = getRow('SELECT id FROM reviews WHERE id = ? AND user_id = ?', [$id, $userId]);
        if (!$review) {
            jsonError('Revisão não encontrada', 404);
        }
        
        update(
            'UPDATE reviews SET status = ?, completed_at = NOW() WHERE id = ?',
            [$status, $id]
        );
        
        jsonSuccess(['success' => true]);
    }
    
    else {
        jsonError('Ação não encontrada', 404);
    }
    
} catch (Exception $e) {
    jsonError('Erro: ' . $e->getMessage(), 500);
}
