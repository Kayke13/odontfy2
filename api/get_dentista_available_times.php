<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

$consultorio_id = $_GET['consultorio_id'] ?? '';
$dentista_escolhido = $_GET['dentista_escolhido'] ?? 'principal';
$data = $_GET['data'] ?? '';

if (empty($consultorio_id) || empty($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parâmetros inválidos']);
    exit;
}

// Buscar o dentista principal
$dentista_principal = Database::find('dentistas', 'id', $consultorio_id);
if (!$dentista_principal) {
    http_response_code(404);
    echo json_encode(['error' => 'Dentista não encontrado']);
    exit;
}

// Determinar qual configuração de horários usar
$horarios_config = [];
$fonte_horarios = 'consultorio'; // Para debug

if ($dentista_escolhido === 'principal') {
    // Usar horários do consultório (dentista principal)
    $horarios_config = $dentista_principal['horarios'] ?? [];
    $fonte_horarios = 'consultorio_principal';
} else {
    // Buscar horários do dentista específico
    $dentistas_file = dirname(__DIR__) . '/data/dentistas_consultorio.json';
    if (file_exists($dentistas_file)) {
        $dentistas_consultorio = json_decode(file_get_contents($dentistas_file), true) ?: [];
        $dentista_encontrado = false;
        
        foreach ($dentistas_consultorio as $dc) {
            // Verificar se é o dentista correto E se pertence ao consultório correto
            if ($dc['id'] === $dentista_escolhido && $dc['consultorio_id'] === $consultorio_id) {
                $dentista_encontrado = true;
                
                // Verificar se tem horários configurados e se não estão vazios
                if (isset($dc['horarios']) && !empty($dc['horarios'])) {
                    // Verificar se pelo menos um dia está configurado
                    $tem_horarios_validos = false;
                    foreach ($dc['horarios'] as $dia => $config) {
                        if (isset($config['ativo']) || isset($config['inicio']) || isset($config['fim'])) {
                            $tem_horarios_validos = true;
                            break;
                        }
                    }
                    
                    if ($tem_horarios_validos) {
                        $horarios_config = $dc['horarios'];
                        $fonte_horarios = 'dentista_' . $dc['nome'];
                    } else {
                        // Horários existem mas estão vazios, usa do consultório
                        $horarios_config = $dentista_principal['horarios'] ?? [];
                        $fonte_horarios = 'consultorio_fallback_horarios_vazios';
                    }
                } else {
                    // Se não tem horários configurados, usa os do consultório
                    $horarios_config = $dentista_principal['horarios'] ?? [];
                    $fonte_horarios = 'consultorio_fallback_sem_horarios';
                }
                break;
            }
        }
        
        // Se não encontrou o dentista, usa horários do principal
        if (!$dentista_encontrado) {
            $horarios_config = $dentista_principal['horarios'] ?? [];
            $fonte_horarios = 'consultorio_fallback_dentista_nao_encontrado';
        }
    } else {
        // Arquivo não existe, usa horários do principal
        $horarios_config = $dentista_principal['horarios'] ?? [];
        $fonte_horarios = 'consultorio_fallback_arquivo_nao_existe';
    }
    
    // Se ainda não tem horários, usa do principal como último recurso
    if (empty($horarios_config)) {
        $horarios_config = $dentista_principal['horarios'] ?? [];
        $fonte_horarios = 'consultorio_fallback_final';
    }
}

// Obter dia da semana da data
$dias_map = [
    'Sunday' => 'domingo',
    'Monday' => 'segunda',
    'Tuesday' => 'terca',
    'Wednesday' => 'quarta',
    'Thursday' => 'quinta',
    'Friday' => 'sexta',
    'Saturday' => 'sabado'
];

$dia_semana_en = date('l', strtotime($data));
$dia_semana = $dias_map[$dia_semana_en];

// Verificar se o dentista atende neste dia
$horario_dia = $horarios_config[$dia_semana] ?? null;

if (!$horario_dia || !($horario_dia['ativo'] ?? false)) {
    echo json_encode([
        'success' => true,
        'horarios_disponiveis' => [],
        'mensagem' => 'Dentista não atende neste dia da semana',
        'dia_semana' => $dia_semana
    ]);
    exit;
}

// Gerar horários disponíveis baseado na configuração
$hora_inicio = $horario_dia['inicio'] ?? '08:00';
$hora_fim = $horario_dia['fim'] ?? '18:00';

$horarios_possiveis = [];
$hora_atual = strtotime($hora_inicio);
$hora_final = strtotime($hora_fim);

while ($hora_atual < $hora_final) {
    $horarios_possiveis[] = date('H:i', $hora_atual);
    $hora_atual = strtotime('+30 minutes', $hora_atual);
}

// Buscar horários já ocupados
$todos_agendamentos = Database::get('agendamentos');
$horarios_ocupados = [];

foreach ($todos_agendamentos as $agendamento) {
    if ($agendamento['dentista_id'] === $consultorio_id && $agendamento['status'] !== 'cancelado') {
        $agendamento_data = date('Y-m-d', strtotime($agendamento['data']));
        
        if ($agendamento_data === $data && $agendamento['dentista_escolhido'] === $dentista_escolhido) {
            $hora = date('H:i', strtotime($agendamento['data']));
            $horarios_ocupados[] = $hora;
        }
    }
}

// Filtrar horários disponíveis (remover os ocupados)
$horarios_disponiveis = array_diff($horarios_possiveis, $horarios_ocupados);
$horarios_disponiveis = array_values($horarios_disponiveis); // Reindexar

echo json_encode([
    'success' => true,
    'horarios_disponiveis' => $horarios_disponiveis,
    'horarios_ocupados' => $horarios_ocupados,
    'horario_inicio' => $hora_inicio,
    'horario_fim' => $hora_fim,
    'dia_semana' => $dia_semana,
    'dentista_escolhido' => $dentista_escolhido,
    'consultorio_id' => $consultorio_id,
    'fonte_horarios' => $fonte_horarios,
    'debug' => [
        'horarios_config' => $horarios_config,
        'horario_dia_ativo' => $horario_dia['ativo'] ?? false,
        'total_horarios_possiveis' => count($horarios_possiveis),
        'total_ocupados' => count($horarios_ocupados),
        'fonte' => $fonte_horarios
    ]
]);

