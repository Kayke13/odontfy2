<?php
session_start();
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit;
}

$dentista = Auth::getCurrentUser();

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'ID do agendamento não fornecido.']);
    exit;
}

$agendamento_id = $_GET['id'];

// Buscar o agendamento
$agendamento = Database::find('agendamentos', 'id', $agendamento_id);

if (!$agendamento) {
    echo json_encode(['success' => false, 'error' => 'Agendamento não encontrado.']);
    exit;
}

// Verificar se o agendamento pertence ao dentista logado
if ($agendamento['dentista_id'] !== $dentista['id']) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}

echo json_encode([
    'success' => true,
    'agendamento' => $agendamento
]);
?>













