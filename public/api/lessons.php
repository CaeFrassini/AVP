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
    if ($method === 'POST' && $action === 'create') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $subjectId = (int)($data['subject_id'] ?? 0);
        $lessonNumber = (int)($data['lesson_number'] ?? 0);
        $completedDate = $data['completed_date'] ?? '';
        
        if ($subjectId <= 0 || $lessonNumber <= 0 || empty($completedDate)) {
            jsonError('Dados inválidos');
        }
        
        // Verificar se disciplina pertence ao usuário
        $subject = getRow('SELECT id FROM subjects WHERE id = ? AND user_id = ?', [$subjectId, $userId]);
        if (!$subject) {
            jsonError('Disciplina não encontrada', 404);
        }
        
        // Inserir aula
        $lessonId = insert(
            'INSERT INTO lessons (user_id, subject_id, lesson_number, completed_date) VALUES (?, ?, ?, ?)',
            [$userId, $subjectId, $lessonNumber, $completedDate]
        );
        
        // Criar revisões automáticas (1, 7 e 30 dias)
        $reviewTypes = [1, 7, 30];
        $completedDateTime = new DateTime($completedDate);
        
        foreach ($reviewTypes as $days) {
            $scheduledDate = clone $completedDateTime;
            $scheduledDate->modify("+{$days} days");
            
            insert(
                'INSERT INTO reviews (user_id, lesson_id, subject_id, lesson_number, review_type, scheduled_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$userId, $lessonId, $subjectId, $lessonNumber, $days, $scheduledDate->format('Y-m-d H:i:s'), 'pending']
            );
        }
        
        jsonSuccess(['id' => $lessonId]);
    }
    
    elseif ($method === 'GET' && $action === 'list') {
        $subjectId = (int)($_GET['subject_id'] ?? 0);
        
        if ($subjectId <= 0) {
            jsonError('Subject ID inválido');
        }
        
        $lessons = getResults(
            'SELECT id, lesson_number, completed_date FROM lessons WHERE user_id = ? AND subject_id = ? ORDER BY lesson_number',
            [$userId, $subjectId]
        );
        
        jsonSuccess($lessons);
    }
    
    else {
        jsonError('Ação não encontrada', 404);
    }
    
} catch (Exception $e) {
    jsonError('Erro: ' . $e->getMessage(), 500);
}
