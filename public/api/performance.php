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
        $subjectId = (int)($_GET['subject_id'] ?? 0);
        
        if ($subjectId > 0) {
            $performance = getResults(
                'SELECT id, subject_id, lesson_number, total_questions, correct_answers, registered_date 
                 FROM performance WHERE user_id = ? AND subject_id = ? ORDER BY registered_date DESC',
                [$userId, $subjectId]
            );
        } else {
            $performance = getResults(
                'SELECT id, subject_id, lesson_number, total_questions, correct_answers, registered_date 
                 FROM performance WHERE user_id = ? ORDER BY registered_date DESC',
                [$userId]
            );
        }
        
        jsonSuccess($performance);
    }
    
    elseif ($method === 'POST' && $action === 'create') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $subjectId = (int)($data['subject_id'] ?? 0);
        $lessonNumber = (int)($data['lesson_number'] ?? 0);
        $totalQuestions = (int)($data['total_questions'] ?? 0);
        $correctAnswers = (int)($data['correct_answers'] ?? 0);
        $registeredDate = $data['registered_date'] ?? '';
        
        if ($subjectId <= 0 || $lessonNumber <= 0 || $totalQuestions <= 0 || empty($registeredDate)) {
            jsonError('Dados inválidos');
        }
        
        if ($correctAnswers > $totalQuestions) {
            jsonError('Acertos não pode ser maior que o total de questões');
        }
        
        // Verificar se disciplina pertence ao usuário
        $subject = getRow('SELECT id FROM subjects WHERE id = ? AND user_id = ?', [$subjectId, $userId]);
        if (!$subject) {
            jsonError('Disciplina não encontrada', 404);
        }
        
        $id = insert(
            'INSERT INTO performance (user_id, subject_id, lesson_number, total_questions, correct_answers, registered_date) 
             VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $subjectId, $lessonNumber, $totalQuestions, $correctAnswers, $registeredDate]
        );
        
        jsonSuccess(['id' => $id]);
    }
    
    elseif ($method === 'GET' && $action === 'stats') {
        // Obter todas as disciplinas do usuário
        $subjects = getResults(
            'SELECT id, name FROM subjects WHERE user_id = ? ORDER BY name',
            [$userId]
        );
        
        $bySubject = [];
        $globalTotal = 0;
        $globalCorrect = 0;
        
        foreach ($subjects as $subject) {
            $performance = getResults(
                'SELECT total_questions, correct_answers FROM performance WHERE user_id = ? AND subject_id = ?',
                [$userId, $subject['id']]
            );
            
            $totalQuestions = 0;
            $totalCorrect = 0;
            
            foreach ($performance as $perf) {
                $totalQuestions += $perf['total_questions'];
                $totalCorrect += $perf['correct_answers'];
            }
            
            if ($totalQuestions > 0) {
                $bySubject[] = [
                    'subject_id' => $subject['id'],
                    'subject_name' => $subject['name'],
                    'total_questions' => $totalQuestions,
                    'total_correct' => $totalCorrect,
                    'percentage' => round(($totalCorrect / $totalQuestions) * 100)
                ];
                
                $globalTotal += $totalQuestions;
                $globalCorrect += $totalCorrect;
            }
        }
        
        $globalPercentage = $globalTotal > 0 ? round(($globalCorrect / $globalTotal) * 100) : 0;
        
        jsonSuccess([
            'by_subject' => $bySubject,
            'global' => [
                'total_questions' => $globalTotal,
                'total_correct' => $globalCorrect,
                'percentage' => $globalPercentage
            ]
        ]);
    }
    
    else {
        jsonError('Ação não encontrada', 404);
    }
    
} catch (Exception $e) {
    jsonError('Erro: ' . $e->getMessage(), 500);
}
