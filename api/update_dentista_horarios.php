<?php
session_start();
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

Auth::requireLogin();
$dentista_principal = Auth::getCurrentUser();

$dentista_id = $_POST['dentista_id'] ?? '';
$horarios_json = $_POST['horarios'] ?? '';

if (empty($dentista_id) || empty($horarios_json)) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

$horarios = json_decode($horarios_json, true);
if (!$horarios) {
    echo json_encode(['success' => false, 'error' => 'Formato de horários inválido']);
    exit;
}

// Carregar dentistas do consultório
$dentistas_file = dirname(__DIR__) . '/data/dentistas_consultorio.json';
if (!file_exists($dentistas_file)) {
    echo json_encode(['success' => false, 'error' => 'Arquivo de dentistas não encontrado']);
    exit;
}

$dentistas = json_decode(file_get_contents($dentistas_file), true) ?: [];

// Encontrar e atualizar o dentista
$found = false;
foreach ($dentistas as &$dentista) {
    if ($dentista['id'] === $dentista_id && $dentista['consultorio_id'] === $dentista_principal['id']) {
        $dentista['horarios'] = $horarios;
        $found = true;
        break;
    }
}

if (!$found) {
    echo json_encode(['success' => false, 'error' => 'Dentista não encontrado']);
    exit;
}

// Salvar arquivo
if (file_put_contents($dentistas_file, json_encode($dentistas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'Horários atualizados com sucesso']);
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar horários']);
}

