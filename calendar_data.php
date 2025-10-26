<?php
session_start();
require_once 'auth.php';
require_once 'config/database.php';
require_once 'config/upload.php';

Auth::requireLogin();

$dentista = Auth::getCurrentUser();

// Definir timezone para evitar problemas de data
date_default_timezone_set('America/Sao_Paulo');

// Processar parâmetros de data da URL
$view = $_GET['view'] ?? 'day';
$date_param = $_GET['date'] ?? date('Y-m-d');

// Validar e processar a data
$current_date = null;
if ($date_param && strtotime($date_param)) {
    $current_date = new DateTime($date_param);
} else {
    $current_date = new DateTime();
}

$agendamentos = Database::get('agendamentos');
$agendamentos_dentista = array_filter($agendamentos, function($agendamento) use ($dentista) {
    return $agendamento['dentista_id'] === $dentista['id'];
});

// Carregar dados dos clientes para obter fotos
$clientes = Database::get('clientes');

$hoje = $current_date->format('Y-m-d');

// Função para filtrar agendamentos por data
function getAgendamentosByDate($agendamentos, $data) {
    return array_filter($agendamentos, function($agendamento) use ($data) {
        if (empty($agendamento['data']) || trim($agendamento['data']) === '') {
            return false;
        }
        $data_agendamento = strtotime($agendamento['data']);
        if ($data_agendamento === false) {
            return false;
        }
        return date('Y-m-d', $data_agendamento) === $data;
    });
}

// Função para obter URL da imagem
function getImageUrl($foto, $tipo, $nome) {
    if (empty($foto)) {
        return "https://ui-avatars.com/api/?name=" . urlencode($nome) . "&background=3b82f6&color=ffffff&size=48";
    }
    return "uploads/{$tipo}s/{$foto}";
}

// Função para obter cores do status
function getStatusColor($status) {
    $status_colors = [
        'pendente' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'confirmado' => 'bg-green-100 text-green-800 border-green-200',
        'cancelado' => 'bg-red-100 text-red-800 border-red-200',
        'concluido' => 'bg-blue-100 text-blue-800 border-blue-200'
    ];
    return $status_colors[$status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
}

// Retornar dados em JSON
header('Content-Type: application/json');

$response = [
    'view' => $view,
    'current_date' => $current_date->format('Y-m-d'),
    'current_period' => '',
    'calendar_html' => ''
];

// Gerar título do período
switch($view) {
    case 'day':
        $response['current_period'] = $current_date->format('d \d\e F \d\e Y');
        break;
    case 'week':
        $startOfWeek = clone $current_date;
        $startOfWeek->modify('monday this week');
        $endOfWeek = clone $startOfWeek;
        $endOfWeek->modify('+6 days');
        $response['current_period'] = $startOfWeek->format('d/m') . ' - ' . $endOfWeek->format('d/m/Y');
        break;
    case 'month':
        $response['current_period'] = $current_date->format('F \d\e Y');
        break;
    default:
        $response['current_period'] = $current_date->format('d \d\e F \d\e Y');
}

// Gerar HTML do calendário baseado na visualização
ob_start();

if ($view === 'day') {
    $agendamentos_hoje = getAgendamentosByDate($agendamentos_dentista, $hoje);
    usort($agendamentos_hoje, function($a, $b) {
        return strtotime($a['data']) - strtotime($b['data']);
    });
    
    if (empty($agendamentos_hoje)): ?>
        <div class="text-center py-8">
            <i class="fas fa-calendar-day text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">Nenhum agendamento para hoje</p>
        </div>
    <?php else: ?>
        <?php foreach ($agendamentos_hoje as $agendamento): ?>
            <?php 
            $cliente_key = $agendamento['paciente_email'] . '_' . $agendamento['paciente_telefone'];
            $cliente_data = $clientes[$cliente_key] ?? null;
            $cliente_foto = $cliente_data['foto'] ?? '';
            $status_color = getStatusColor($agendamento['status']);
            ?>
            <div class="flex items-center p-4 bg-white border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-gray-200">
                        <img src="<?php echo getImageUrl($cliente_foto, 'cliente', $agendamento['paciente_nome']); ?>" 
                             alt="Foto de <?php echo htmlspecialchars($agendamento['paciente_nome']); ?>" 
                             class="w-full h-full object-cover">
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($agendamento['paciente_nome']); ?></p>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($agendamento['servico']); ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-semibold text-gray-900">
                                <?php echo date('H:i', strtotime($agendamento['data'])); ?>
                            </p>
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full border <?php echo $status_color; ?>">
                                <?php echo ucfirst($agendamento['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif;
    
} elseif ($view === 'week') {
    $dias_semana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    $startOfWeek = clone $current_date;
    $startOfWeek->modify('monday this week');
    $inicio_semana = $startOfWeek->format('Y-m-d');
    ?>
    <div class="grid grid-cols-7 gap-2 mb-4">
        <?php for ($i = 0; $i < 7; $i++): ?>
            <?php 
            $data_dia = date('Y-m-d', strtotime($inicio_semana . " +{$i} days"));
            $dia_nome = $dias_semana[$i];
            $dia_numero = date('d', strtotime($data_dia));
            $is_today = $data_dia === $hoje;
            ?>
            <div class="text-center">
                <div class="text-xs font-medium text-gray-500 mb-1"><?php echo $dia_nome; ?></div>
                <div class="text-lg font-semibold <?php echo $is_today ? 'text-blue-600' : 'text-gray-900'; ?>">
                    <?php echo $dia_numero; ?>
                </div>
            </div>
        <?php endfor; ?>
    </div>
    <div class="space-y-2">
        <?php for ($i = 0; $i < 7; $i++): ?>
            <?php 
            $data_dia = date('Y-m-d', strtotime($inicio_semana . " +{$i} days"));
            $agendamentos_dia = getAgendamentosByDate($agendamentos_dentista, $data_dia);
            usort($agendamentos_dia, function($a, $b) {
                return strtotime($a['data']) - strtotime($b['data']);
            });
            ?>
            <div class="border border-gray-200 rounded-lg p-3">
                <div class="flex items-center justify-between mb-2">
                    <h5 class="font-medium text-gray-900">
                        <?php echo date('d/m', strtotime($data_dia)); ?>
                        <?php if ($data_dia === $hoje): ?>
                            <span class="text-blue-600 text-sm">(Hoje)</span>
                        <?php endif; ?>
                    </h5>
                    <span class="text-sm text-gray-500"><?php echo count($agendamentos_dia); ?> agendamentos</span>
                </div>
                <?php if (empty($agendamentos_dia)): ?>
                    <p class="text-sm text-gray-400">Nenhum agendamento</p>
                <?php else: ?>
                    <div class="space-y-1">
                        <?php foreach (array_slice($agendamentos_dia, 0, 3) as $agendamento): ?>
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium text-gray-900"><?php echo date('H:i', strtotime($agendamento['data'])); ?></span>
                                <span class="text-gray-600 truncate ml-2"><?php echo htmlspecialchars($agendamento['paciente_nome']); ?></span>
                                <span class="text-gray-500 text-xs"><?php echo htmlspecialchars($agendamento['servico']); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($agendamentos_dia) > 3): ?>
                            <p class="text-xs text-gray-400">+<?php echo count($agendamentos_dia) - 3; ?> mais</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>
    <?php
    
} elseif ($view === 'month') {
    $dias_semana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    $primeiro_dia_mes = $current_date->format('Y-m-01');
    $ultimo_dia_mes = $current_date->format('Y-m-t');
    $primeiro_dia_semana = $current_date->format('w');
    $dias_no_mes = $current_date->format('t');
    ?>
    <div class="grid grid-cols-7 gap-1 mb-4">
        <?php foreach ($dias_semana as $dia): ?>
            <div class="text-center text-sm font-medium text-gray-500 py-2"><?php echo $dia; ?></div>
        <?php endforeach; ?>
    </div>
    <div class="grid grid-cols-7 gap-1">
        <?php
        // Dias do mês anterior
        for ($i = 0; $i < $primeiro_dia_semana; $i++):
            $dia_anterior = date('d', strtotime($primeiro_dia_mes . " -" . ($primeiro_dia_semana - $i) . " days"));
        ?>
            <div class="h-20 p-1 bg-gray-50 border border-gray-200 rounded text-gray-400 text-sm">
                <div class="font-medium"><?php echo $dia_anterior; ?></div>
            </div>
        <?php endfor; ?>
        
        <?php for ($dia = 1; $dia <= $dias_no_mes; $dia++): ?>
            <?php 
            $data_atual = date('Y-m-d', strtotime($primeiro_dia_mes . " +" . ($dia - 1) . " days"));
            $agendamentos_dia = getAgendamentosByDate($agendamentos_dentista, $data_atual);
            $is_today = $data_atual === $hoje;
            $is_weekend = in_array(date('w', strtotime($data_atual)), [0, 6]);
            ?>
            <div class="h-20 p-1 border border-gray-200 rounded <?php echo $is_today ? 'bg-blue-50 border-blue-300' : ($is_weekend ? 'bg-gray-50' : 'bg-white'); ?> hover:bg-gray-50 cursor-pointer">
                <div class="flex items-center justify-between mb-1">
                    <div class="font-medium text-sm <?php echo $is_today ? 'text-blue-600' : 'text-gray-900'; ?>">
                        <?php echo $dia; ?>
                    </div>
                    <?php if (!empty($agendamentos_dia)): ?>
                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                    <?php endif; ?>
                </div>
                <div class="space-y-1">
                    <?php foreach (array_slice($agendamentos_dia, 0, 2) as $agendamento): ?>
                        <div class="text-xs bg-blue-100 text-blue-800 rounded px-1 truncate">
                            <?php echo date('H:i', strtotime($agendamento['data'])); ?> 
                            <?php echo htmlspecialchars(substr($agendamento['paciente_nome'], 0, 8)); ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($agendamentos_dia) > 2): ?>
                        <div class="text-xs text-gray-500">+<?php echo count($agendamentos_dia) - 2; ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endfor; ?>
    </div>
    <?php
}

$response['calendar_html'] = ob_get_clean();

echo json_encode($response);
?>
