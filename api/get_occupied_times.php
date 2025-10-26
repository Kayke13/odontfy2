<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

$dentista_id = $_GET['dentista_id'] ?? '';
$dentista_escolhido = $_GET['dentista_escolhido'] ?? '';
$data = $_GET['data'] ?? '';

if (empty($dentista_id) || empty($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parâmetros inválidos']);
    exit;
}

// Buscar todos os agendamentos
$todos_agendamentos = Database::get('agendamentos');

$horarios_ocupados = [];

foreach ($todos_agendamentos as $agendamento) {
    // Verificar se é do mesmo dentista e não está cancelado
    if ($agendamento['dentista_id'] === $dentista_id && $agendamento['status'] !== 'cancelado') {
        // Extrair apenas a data (sem hora) do agendamento
        $agendamento_data = date('Y-m-d', strtotime($agendamento['data']));
        
        // Verificar se é a mesma data
        if ($agendamento_data === $data) {
            // Verificar se é para o mesmo dentista escolhido (ou principal)
            if ($agendamento['dentista_escolhido'] === $dentista_escolhido || 
                (empty($dentista_escolhido) && $agendamento['dentista_escolhido'] === 'principal')) {
                
                // Extrair apenas o horário (HH:MM)
                $hora = date('H:i', strtotime($agendamento['data']));
                $horarios_ocupados[] = $hora;
            }
        }
    }
}

echo json_encode([
    'success' => true,
    'horarios_ocupados' => array_unique($horarios_ocupados),
    'data' => $data,
    'dentista_escolhido' => $dentista_escolhido
]);

