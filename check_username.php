<?php
header('Content-Type: application/json');
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    
    // Validações básicas
    if (empty($username)) {
        echo json_encode(['available' => false, 'message' => 'Username é obrigatório']);
        exit;
    }
    
    if (strlen($username) < 3) {
        echo json_encode(['available' => false, 'message' => 'Username deve ter pelo menos 3 caracteres']);
        exit;
    }
    
    if (strlen($username) > 20) {
        echo json_encode(['available' => false, 'message' => 'Username deve ter no máximo 20 caracteres']);
        exit;
    }
    
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        echo json_encode(['available' => false, 'message' => 'Username deve conter apenas letras, números, hífens e underscores']);
        exit;
    }
    
    // Verificar se username já existe
    $existing = Database::find('dentistas', 'username', $username);
    
    if ($existing) {
        echo json_encode(['available' => false, 'message' => 'Este username já está em uso. Escolha outro.']);
    } else {
        echo json_encode(['available' => true, 'message' => 'Username disponível!']);
    }
} else {
    echo json_encode(['available' => false, 'message' => 'Requisição inválida']);
}
?>
