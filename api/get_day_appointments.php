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

if (!isset($_GET['date'])) {
    echo json_encode(['success' => false, 'error' => 'Data não fornecida.']);
    exit;
}

$date = $_GET['date'];

// Buscar todos os agendamentos do dentista
$agendamentos = Database::get('agendamentos');
$agendamentos_dentista = array_filter($agendamentos, function($agendamento) use ($dentista) {
    return $agendamento['dentista_id'] === $dentista['id'];
});

// Filtrar agendamentos do dia específico
$agendamentos_dia = array_filter($agendamentos_dentista, function($agendamento) use ($date) {
    if (empty($agendamento['data']) || trim($agendamento['data']) === '') {
        return false;
    }
    $data_agendamento = strtotime($agendamento['data']);
    if ($data_agendamento === false) {
        return false;
    }
    return date('Y-m-d', $data_agendamento) === $date;
});

// Ordenar por horário
usort($agendamentos_dia, function($a, $b) {
    return strtotime($a['data']) - strtotime($b['data']);
});

// Converter para array indexado
$agendamentos_dia = array_values($agendamentos_dia);

echo json_encode([
    'success' => true,
    'agendamentos' => $agendamentos_dia,
    'total' => count($agendamentos_dia),
    'date' => $date
]);
?>

