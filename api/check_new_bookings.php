<?php
session_start();
header('Content-Type: application/json');

require_once '../auth.php';
require_once '../config/database.php';

// Verificar se está logado
if (!Auth::isLoggedIn()) {
    echo json_encode(['error' => 'Não autenticado', 'success' => false]);
    exit;
}

$dentista = Auth::getCurrentUser();

if (!$dentista || !isset($dentista['id'])) {
    echo json_encode(['error' => 'Dentista não encontrado', 'success' => false]);
    exit;
}

$last_check = $_GET['last_check'] ?? null;

// Buscar todos os agendamentos do dentista
$agendamentos = Database::get('agendamentos');

$agendamentos_dentista = array_filter($agendamentos, function($agendamento) use ($dentista) {
    return $agendamento['dentista_id'] === $dentista['id'];
});

// Se houver último check, buscar apenas novos
$novos_agendamentos = [];
if ($last_check) {
    $last_check_timestamp = strtotime($last_check);
    
    foreach ($agendamentos_dentista as $agendamento) {
        $created_at = strtotime($agendamento['created_at']);
        
        if ($created_at > $last_check_timestamp) {
            $novos_agendamentos[] = $agendamento;
        }
    }
}

// Estatísticas atualizadas
$total_agendamentos = count($agendamentos_dentista);
$hoje = date('Y-m-d');
$agendamentos_hoje = array_filter($agendamentos_dentista, function($agendamento) use ($hoje) {
    if (empty($agendamento['data']) || trim($agendamento['data']) === '') {
        return false;
    }
    $data_agendamento = strtotime($agendamento['data']);
    if ($data_agendamento === false) {
        return false;
    }
    return date('Y-m-d', $data_agendamento) === $hoje;
});
$agendamentos_hoje_count = count($agendamentos_hoje);

// Próximos agendamentos
$proximos_agendamentos = array_filter($agendamentos_dentista, function($agendamento) {
    return strtotime($agendamento['data']) >= time();
});
usort($proximos_agendamentos, function($a, $b) {
    return strtotime($a['data']) - strtotime($b['data']);
});
$proximos_agendamentos = array_slice($proximos_agendamentos, 0, 5);

echo json_encode([
    'success' => true,
    'has_new' => count($novos_agendamentos) > 0,
    'new_count' => count($novos_agendamentos),
    'new_bookings' => $novos_agendamentos,
    'stats' => [
        'total' => $total_agendamentos,
        'hoje' => $agendamentos_hoje_count
    ],
    'proximos' => $proximos_agendamentos,
    'current_time' => date('Y-m-d H:i:s')
]);
?>

