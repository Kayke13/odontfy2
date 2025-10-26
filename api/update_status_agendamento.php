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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos.']);
    exit;
}

$agendamento_id = $input['id'];
$novo_status = $input['status'];

// Validar status
$status_validos = ['pendente', 'confirmado', 'cancelado', 'concluido'];
if (!in_array($novo_status, $status_validos)) {
    echo json_encode(['success' => false, 'error' => 'Status inválido.']);
    exit;
}

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

// Atualizar o status
$result = Database::update('agendamentos', $agendamento_id, [
    'status' => $novo_status
]);

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Status atualizado com sucesso.',
        'agendamento' => $result
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar status.']);
}
?>













