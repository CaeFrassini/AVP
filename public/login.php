<?php
require_once dirname(__DIR__) . '/config/config.php';

// Se já está autenticado, redireciona para o app
if (isAuthenticated()) {
    redirect(BASE_URL . '/index.php');
}

$error = '';
$success = '';

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email e senha são obrigatórios';
    } elseif (!isValidEmail($email)) {
        $error = 'Email inválido';
    } else {
        try {
            $user = getRow('SELECT id, password FROM users WHERE email = ?', [$email]);
            
            if ($user && verifyPassword($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                redirect(BASE_URL . '/index.php');
            } else {
                $error = 'Email ou senha incorretos';
            }
        } catch (Exception $e) {
            $error = 'Erro ao fazer login: ' . $e->getMessage();
        }
    }
}

// Processar cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Todos os campos são obrigatórios';
    } elseif (!isValidEmail($email)) {
        $error = 'Email inválido';
    } elseif (strlen($password) < 6) {
        $error = 'Senha deve ter no mínimo 6 caracteres';
    } elseif ($password !== $confirmPassword) {
        $error = 'Senhas não conferem';
    } else {
        try {
            // Verificar se email já existe
            $existingUser = getRow('SELECT id FROM users WHERE email = ?', [$email]);
            
            if ($existingUser) {
                $error = 'Este email já está cadastrado';
            } else {
                // Inserir novo usuário
                $hashedPassword = hashPassword($password);
                insert('INSERT INTO users (name, email, password) VALUES (?, ?, ?)', 
                    [$name, $email, $hashedPassword]);
                
                $success = 'Cadastro realizado com sucesso! Faça login para continuar.';
            }
        } catch (Exception $e) {
            $error = 'Erro ao cadastrar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AVP - Controle de Estudos | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .auth-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        
        .auth-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .auth-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .auth-header h1 {
            font-size: 28px;
            font-weight: bold;
            margin: 0;
        }
        
        .auth-header p {
            margin: 10px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .auth-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
            transition: transform 0.2s;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6a3f8f 100%);
            transform: translateY(-2px);
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }
        
        .toggle-form {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .toggle-form a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }
        
        .toggle-form a:hover {
            text-decoration: underline;
        }
        
        .form-section {
            display: none;
        }
        
        .form-section.active {
            display: block;
        }
        
        .input-group-text {
            background: transparent;
            border: 2px solid #e0e0e0;
            border-right: none;
        }
        
        .form-control {
            border: 2px solid #e0e0e0;
            border-left: none;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>AVP</h1>
                <p>Controle de Estudos para Concursos</p>
            </div>
            
            <div class="auth-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Erro!</strong> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Sucesso!</strong> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Formulário de Login -->
                <form id="loginForm" class="form-section active" method="POST">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label for="loginEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="loginEmail" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="loginPassword" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="loginPassword" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Entrar</button>
                    
                    <div class="toggle-form">
                        Não tem conta? <a onclick="toggleForm()">Cadastre-se aqui</a>
                    </div>
                </form>
                
                <!-- Formulário de Cadastro -->
                <form id="registerForm" class="form-section" method="POST">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="form-group">
                        <label for="registerName" class="form-label">Nome Completo</label>
                        <input type="text" class="form-control" id="registerName" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="registerEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="registerEmail" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="registerPassword" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="registerPassword" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="registerConfirmPassword" class="form-label">Confirmar Senha</label>
                        <input type="password" class="form-control" id="registerConfirmPassword" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Cadastrar</button>
                    
                    <div class="toggle-form">
                        Já tem conta? <a onclick="toggleForm()">Faça login aqui</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleForm() {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            
            loginForm.classList.toggle('active');
            registerForm.classList.toggle('active');
        }
    </script>
</body>
</html>
