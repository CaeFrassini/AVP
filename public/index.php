<?php
require_once dirname(__DIR__) . '/config/config.php';

// Verificar autenticação
if (!isAuthenticated()) {
    redirect(BASE_URL . '/login.php');
}

$user = getAuthUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AVP - Controle de Estudos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 24px;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            transition: color 0.3s;
        }
        
        .nav-link:hover {
            color: white !important;
        }
        
        .container-main {
            padding: 30px 0;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 12px 12px 0 0 !important;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6a3f8f 100%);
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead {
            background-color: #f8f9fa;
        }
        
        .badge-success {
            background-color: var(--success-color);
        }
        
        .badge-warning {
            background-color: var(--warning-color);
        }
        
        .badge-danger {
            background-color: var(--danger-color);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .nav-tabs .nav-link {
            color: #666;
            border: none;
            border-bottom: 3px solid transparent;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background-color: transparent;
            border-bottom-color: var(--primary-color);
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        
        .review-item {
            padding: 15px;
            border-left: 4px solid var(--primary-color);
            background-color: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        .review-item.pending {
            border-left-color: var(--warning-color);
        }
        
        .review-item.completed {
            border-left-color: var(--success-color);
        }
        
        .review-item.skipped {
            border-left-color: #6b7280;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .loading.active {
            display: block;
        }
        
        .spinner-border {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-book"></i> AVP
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="ms-auto">
                    <span class="text-white me-3">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['name']); ?>
                    </span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container-fluid container-main">
        <div class="container">
            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="teoria-tab" data-bs-toggle="tab" data-bs-target="#teoria" type="button" role="tab">
                        <i class="fas fa-graduation-cap"></i> Teoria
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="revisoes-tab" data-bs-toggle="tab" data-bs-target="#revisoes" type="button" role="tab">
                        <i class="fas fa-calendar-check"></i> Revisões
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="desempenho-tab" data-bs-toggle="tab" data-bs-target="#desempenho" type="button" role="tab">
                        <i class="fas fa-chart-pie"></i> Desempenho
                    </button>
                </li>
            </ul>
            
            <!-- Tab Content -->
            <div class="tab-content">
                <!-- ABA TEORIA -->
                <div class="tab-pane fade show active" id="teoria" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Adicionar Disciplina</h5>
                        </div>
                        <div class="card-body">
                            <form id="addSubjectForm">
                                <div class="row">
                                    <div class="col-md-5">
                                        <label class="form-label">Nome da Disciplina</label>
                                        <input type="text" class="form-control" id="subjectName" placeholder="Ex: Português, Contabilidade..." required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Total de Aulas (opcional)</label>
                                        <input type="number" class="form-control" id="subjectTotal" placeholder="Ex: 40" min="0">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-plus"></i> Adicionar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-book-open"></i> Disciplinas Cadastradas</h5>
                        </div>
                        <div class="card-body">
                            <div id="subjectsList" class="loading active">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Carregando...</span>
                                </div>
                            </div>
                            <div id="subjectsTable"></div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-check-circle"></i> Registrar Aula Concluída</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Cada aula concluída gera automaticamente revisões para 1, 7 e 30 dias</p>
                            <form id="addLessonForm">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">Disciplina</label>
                                        <select class="form-select" id="lessonSubject" required>
                                            <option value="">Selecione...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Nº da Aula</label>
                                        <input type="number" class="form-control" id="lessonNumber" placeholder="Ex: 5" min="1" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Data que Concluiu</label>
                                        <input type="date" class="form-control" id="lessonDate" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-plus"></i> Registrar Aula
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- ABA REVISÕES -->
                <div class="tab-pane fade" id="revisoes" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-hourglass-half"></i> Revisões Pendentes</h5>
                        </div>
                        <div class="card-body">
                            <div id="pendingReviews" class="loading active">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Carregando...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-check-circle" style="color: #10b981;"></i> Revisões Concluídas</h5>
                        </div>
                        <div class="card-body">
                            <div id="completedReviews" class="loading active">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Carregando...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-times-circle" style="color: #6b7280;"></i> Revisões Puladas</h5>
                        </div>
                        <div class="card-body">
                            <div id="skippedReviews" class="loading active">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Carregando...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ABA DESEMPENHO -->
                <div class="tab-pane fade" id="desempenho" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Registrar Desempenho</h5>
                        </div>
                        <div class="card-body">
                            <form id="addPerformanceForm">
                                <div class="row">
                                    <div class="col-md-2">
                                        <label class="form-label">Matéria</label>
                                        <select class="form-select" id="perfSubject" required>
                                            <option value="">Selecione...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Aula</label>
                                        <input type="number" class="form-control" id="perfLesson" placeholder="Nº" min="1" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Total</label>
                                        <input type="number" class="form-control" id="perfTotal" placeholder="Ex: 50" min="1" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Acertos</label>
                                        <input type="number" class="form-control" id="perfCorrect" placeholder="Ex: 40" min="0" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Data</label>
                                        <input type="date" class="form-control" id="perfDate" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-plus"></i> Registrar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-table"></i> Histórico de Desempenho</h5>
                        </div>
                        <div class="card-body">
                            <div id="performanceList" class="loading active">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Carregando...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Desempenho por Matéria</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="subjectChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Desempenho Global</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="globalChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
</body>
</html>
