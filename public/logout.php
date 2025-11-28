<?php
require_once dirname(__DIR__) . '/config/config.php';

// Destruir sessão
session_destroy();

// Redirecionar para login
redirect(BASE_URL . '/login.php');
