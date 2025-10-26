<?php
session_start();
require_once 'auth.php';
require_once 'config/database.php';
require_once 'config/planos.php';
require_once 'config/upload.php';
require_once 'config/plano_status.php';

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

// Verificar status do plano e aplicar restrições
$status_plano = PlanoStatus::verificarStatus($dentista);

// Aplicar restrições se necessário
if (!$status_plano['pode_usar_sistema']) {
    PlanoStatus::aplicarRestricoes($dentista);
}

$agendamentos = Database::get('agendamentos');
$agendamentos_dentista = array_filter($agendamentos, function($agendamento) use ($dentista) {
    return $agendamento['dentista_id'] === $dentista['id'];
});

// Carregar dados dos clientes para obter fotos
$clientes = Database::get('clientes');

// Estatísticas
$total_agendamentos = count($agendamentos_dentista);
$hoje = $current_date->format('Y-m-d');
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
$agendamentos_hoje = count($agendamentos_hoje);

// Próximos agendamentos
$proximos_agendamentos = array_filter($agendamentos_dentista, function($agendamento) {
    return strtotime($agendamento['data']) >= time();
});
usort($proximos_agendamentos, function($a, $b) {
    return strtotime($a['data']) - strtotime($b['data']);
});
$total_proximos = count($proximos_agendamentos);
$proximos_agendamentos = array_slice($proximos_agendamentos, 0, 5);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Odontfy</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/LOGO.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .sidebar-transition {
            transition: width 0.3s ease, margin-left 0.3s ease;
        }
        .menu-item {
            position: relative;
            overflow: hidden;
        }
        .menu-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: white;
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }
        .menu-item:hover::before,
        .menu-item.active::before {
            transform: scaleY(1);
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* Fonte Inter em todo o site, EXCETO nos ícones */
        body, h1, h2, h3, h4, h5, h6, p, a, button, input, textarea, select, label, span, div {
            font-family: 'Inter', sans-serif !important;
        }
        
        /* Garantir que os ícones do FontAwesome funcionem */
        .fa, .fas, .far, .fal, .fab, [class^="fa-"], [class*=" fa-"] {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Pro", "Font Awesome 6 Brands" !important;
        }
        
        i.fa, i.fas, i.far, i.fal, i.fab {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Pro", "Font Awesome 6 Brands" !important;
        }
    </style>
</head>
<body class="bg-gray-50 font-inter">
    <!-- Overlay para Mobile -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed top-0 left-0 z-50 h-screen w-64 bg-gradient-to-b from-blue-600 to-blue-800 shadow-2xl transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
        <div class="flex flex-col h-full">
            <!-- Logo -->
            <div class="flex items-center justify-between p-6 border-b border-blue-500">
                <a href="dashboard.php" class="flex items-center space-x-3">
                    <i class="fas fa-tooth text-white text-3xl"></i>
                    <span class="text-2xl font-bold text-white">Odontfy</span>
                </a>
                <button onclick="toggleSidebar()" class="lg:hidden text-white hover:text-blue-200">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <!-- User Info -->
            <div class="p-6 border-b border-blue-500">
                <div class="flex items-center space-x-3">
                    <img src="<?php echo Upload::getImageUrl($dentista['foto'] ?? '', 'dentista', $dentista['nome']); ?>" 
                         alt="Foto" 
                         class="w-12 h-12 rounded-full border-2 border-white object-cover">
                    <div class="flex-1 min-w-0">
                        <p class="text-white font-semibold truncate"><?php echo htmlspecialchars($dentista['nome']); ?></p>
                        <p class="text-blue-200 text-sm truncate"><?php echo htmlspecialchars($dentista['email']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Menu Items -->
            <nav class="flex-1 overflow-y-auto p-4 space-y-1">
                <a href="dashboard.php" class="menu-item active flex items-center px-4 py-3 text-white bg-blue-700 rounded-lg hover:bg-blue-500 transition-all group">
                    <i class="fas fa-home w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Dashboard</span>
                </a>
                
                <a href="agendamentos.php" class="menu-item flex items-center px-4 py-3 text-blue-100 hover:bg-blue-700 hover:text-white rounded-lg transition-all group">
                    <i class="fas fa-calendar-alt w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Agendamentos</span>
                </a>
                
                <a href="clientes.php" class="menu-item flex items-center px-4 py-3 text-blue-100 hover:bg-blue-700 hover:text-white rounded-lg transition-all group">
                    <i class="fas fa-users w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Clientes</span>
                </a>
                
                <a href="dentistas_consultorio.php" class="menu-item flex items-center px-4 py-3 text-blue-100 hover:bg-blue-700 hover:text-white rounded-lg transition-all group">
                    <i class="fas fa-user-md w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Dentistas</span>
                </a>
                
                <a href="servicos.php" class="menu-item flex items-center px-4 py-3 text-blue-100 hover:bg-blue-700 hover:text-white rounded-lg transition-all group">
                    <i class="fas fa-tooth w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Serviços</span>
                </a>
                
                <a href="financeiro.php" class="menu-item flex items-center px-4 py-3 text-blue-100 hover:bg-blue-700 hover:text-white rounded-lg transition-all group">
                    <i class="fas fa-dollar-sign w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Financeiro</span>
                </a>
                
                <a href="configuracoes.php" class="menu-item flex items-center px-4 py-3 text-blue-100 hover:bg-blue-700 hover:text-white rounded-lg transition-all group">
                    <i class="fas fa-cog w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Configurações</span>
                </a>
                
                <a href="avaliacoes.php" class="menu-item flex items-center px-4 py-3 text-blue-100 hover:bg-blue-700 hover:text-white rounded-lg transition-all group">
                    <i class="fas fa-star w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Avaliações</span>
                    <span class="ml-2 text-xs bg-yellow-500 text-white px-2 py-0.5 rounded-full font-semibold">Novo</span>
                </a>
                
                <a href="perfil_dentista.php" class="menu-item flex items-center px-4 py-3 text-blue-100 hover:bg-blue-700 hover:text-white rounded-lg transition-all group">
                    <i class="fas fa-user-circle w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Meu Perfil</span>
                </a>

                <div class="border-t border-blue-500 my-4"></div>

                <a href="agendar.php?username=<?php echo $dentista['username']; ?>" target="_blank" class="menu-item flex items-center px-4 py-3 text-blue-100 hover:bg-blue-700 hover:text-white rounded-lg transition-all group">
                    <i class="fas fa-external-link-alt w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Página Pública</span>
                </a>
                
                <a href="planos.php" class="menu-item flex items-center px-4 py-3 text-blue-100 hover:bg-blue-700 hover:text-white rounded-lg transition-all group">
                    <i class="fas fa-crown w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Planos</span>
                </a>
            </nav>

            <!-- Logout -->
            <div class="p-4 border-t border-blue-500">
                <a href="logout.php" class="flex items-center px-4 py-3 text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors">
                    <i class="fas fa-sign-out-alt w-5 text-center mr-3"></i>
                    <span>Sair</span>
                </a>
            </div>
        </div>
    </aside>
    
    <!-- Main Content -->
    <div class="lg:ml-64">
    <!-- Header -->
        <nav class="bg-white shadow-lg sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                        <!-- Mobile Menu Button -->
                        <button onclick="toggleSidebar()" class="lg:hidden mr-4 text-gray-600 hover:text-gray-900">
                            <i class="fas fa-bars text-2xl"></i>
                        </button>
                        <h1 class="text-xl font-bold text-gray-900">Dashboard</h1>
                </div>
                <div class="flex items-center space-x-2 sm:space-x-4">
                        <!-- Indicador de Notificações -->
                        <div id="notification-badge" class="relative hidden">
                            <button onclick="openNotificationsPanel()" class="p-2 rounded-full bg-blue-100 hover:bg-blue-200 transition-colors relative">
                                <i class="fas fa-bell text-blue-600 text-lg"></i>
                                <span id="notification-count" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center animate-pulse">0</span>
                            </button>
                        </div>
                        
                </div>
            </div>
        </div>
    </nav>

        <!-- Page Content -->
        <div class="p-4 sm:p-6 lg:p-8">
        <!-- Status do Plano -->
        <div class="mb-8">
            <?php 
            $mensagem_status = PlanoStatus::getMensagemStatus($status_plano);
            $cor_classes = [
                'success' => 'bg-green-50 border-green-200',
                'warning' => 'bg-yellow-50 border-yellow-200',
                'error' => 'bg-red-50 border-red-200',
                'info' => 'bg-blue-50 border-blue-200'
            ];
            $texto_classes = [
                'success' => 'text-green-800',
                'warning' => 'text-yellow-800',
                'error' => 'text-red-800',
                'info' => 'text-blue-800'
            ];
            $cor_classe = $cor_classes[$mensagem_status['tipo']] ?? $cor_classes['info'];
            $texto_classe = $texto_classes[$mensagem_status['tipo']] ?? $texto_classes['info'];
            ?>
            <div class="<?php echo $cor_classe; ?> rounded-lg p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start sm:items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-<?php echo $mensagem_status['icone']; ?> text-xl sm:text-2xl <?php echo str_replace('800', '400', $texto_classe); ?>"></i>
                        </div>
                        <div class="ml-3 sm:ml-4 flex-1">
                            <h3 class="text-base sm:text-lg font-medium <?php echo $texto_classe; ?>"><?php echo $mensagem_status['titulo']; ?></h3>
                            <p class="text-sm sm:text-base <?php echo str_replace('800', '600', $texto_classe); ?> mt-1"><?php echo $mensagem_status['mensagem']; ?></p>
                            <?php if ($mensagem_status['dias']): ?>
                                <p class="text-xs sm:text-sm <?php echo str_replace('800', '500', $texto_classe); ?> mt-1">
                                    <?php echo $mensagem_status['dias']; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($status_plano['status_geral'] !== 'ativo'): ?>
                        <div class="mt-4 sm:mt-0 sm:ml-4">
                            <a href="planos.php" class="block sm:inline-block bg-white hover:bg-gray-50 <?php echo $texto_classe; ?> px-3 sm:px-4 py-2 rounded-md text-xs sm:text-sm font-medium border text-center">
                                <i class="fas fa-credit-card mr-1 sm:mr-2"></i>Ver Planos
                            </a>
                        </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

        <!-- Estatísticas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-8">
            <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
                    <div class="flex items-center">
                    <div class="p-2 sm:p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-calendar-check text-xl sm:text-2xl"></i>
                        </div>
                    <div class="ml-3 sm:ml-4">
                        <p class="text-xs sm:text-sm font-medium text-gray-500">Total de Agendamentos</p>
                        <p class="text-xl sm:text-2xl font-semibold text-gray-900"><?php echo $total_agendamentos; ?></p>
                        </div>
                    </div>
                </div>

            <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
                        <div class="flex items-center">
                    <div class="p-2 sm:p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-calendar-day text-xl sm:text-2xl"></i>
                    </div>
                    <div class="ml-3 sm:ml-4">
                        <p class="text-xs sm:text-sm font-medium text-gray-500">Agendamentos Hoje</p>
                        <p class="text-xl sm:text-2xl font-semibold text-gray-900"><?php echo $agendamentos_hoje; ?></p>
                            </div>
                            </div>
                        </div>

            <div class="bg-white p-4 sm:p-6 rounded-lg shadow sm:col-span-2 lg:col-span-1">
                <div class="flex items-center">
                    <div class="p-2 sm:p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-link text-xl sm:text-2xl"></i>
                    </div>
                    <div class="ml-3 sm:ml-4 flex-1 min-w-0">
                        <p class="text-xs sm:text-sm font-medium text-gray-500">Seu Link</p>
                        <p class="text-xs sm:text-sm font-semibold text-gray-900 truncate">odontfy.com/<?php echo $dentista['username']; ?></p>
                    </div>
                        </div>
                    </div>
        </div>


        <!-- Agenda/Calendário -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="text-lg font-medium text-gray-900 mb-2 sm:mb-0">
                        <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>Agenda em Tempo Real
                    </h3>
                    <div class="flex space-x-2">
                        <button onclick="changeView('day')" id="btn-day" class="px-3 py-1 text-sm font-medium rounded-md <?php echo $view === 'day' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                            Dia
                        </button>
                        <button onclick="changeView('week')" id="btn-week" class="px-3 py-1 text-sm font-medium rounded-md <?php echo $view === 'week' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                            Semana
                        </button>
                        <button onclick="changeView('month')" id="btn-month" class="px-3 py-1 text-sm font-medium rounded-md <?php echo $view === 'month' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                            Mês
                        </button>
                    </div>
                </div>
            </div>
            <div class="p-4 sm:p-6">
                <?php
                // Definir array de dias da semana para uso em todas as visualizações
                $dias_semana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
                ?>
                <!-- Navegação do Calendário -->
                <div class="flex items-center justify-between mb-6">
                    <button onclick="previousPeriod()" class="p-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-600">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <h4 id="current-period" class="text-lg font-semibold text-gray-900">
                        <?php 
                        switch($view) {
                            case 'day':
                                echo $current_date->format('d \d\e F \d\e Y');
                                break;
                            case 'week':
                                $startOfWeek = clone $current_date;
                                $startOfWeek->modify('monday this week');
                                $endOfWeek = clone $startOfWeek;
                                $endOfWeek->modify('+6 days');
                                echo $startOfWeek->format('d/m') . ' - ' . $endOfWeek->format('d/m/Y');
                                break;
                            case 'month':
                                echo $current_date->format('F \d\e Y');
                                break;
                            default:
                                echo $current_date->format('d \d\e F \d\e Y');
                        }
                        ?>
                    </h4>
                    <button onclick="nextPeriod()" class="p-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-600">
                        <i class="fas fa-chevron-right"></i>
                    </button>
            </div>

                <!-- Visualização do Dia -->
                <div id="day-view" class="calendar-view <?php echo $view === 'day' ? '' : 'hidden'; ?>">
                    <div class="space-y-3">
                        <?php
                        $agendamentos_hoje_detalhados = array_filter($agendamentos_dentista, function($agendamento) use ($hoje) {
                            // Verificar se a data é válida
                            if (empty($agendamento['data']) || trim($agendamento['data']) === '') {
                                return false;
                            }
                            $data_agendamento = strtotime($agendamento['data']);
                            if ($data_agendamento === false) {
                                return false;
                            }
                            return date('Y-m-d', $data_agendamento) === $hoje;
                        });
                        usort($agendamentos_hoje_detalhados, function($a, $b) {
                            return strtotime($a['data']) - strtotime($b['data']);
                        });
                        ?>
                        <?php if (empty($agendamentos_hoje_detalhados)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-calendar-day text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500">Nenhum agendamento para hoje</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($agendamentos_hoje_detalhados as $agendamento): ?>
                                <?php 
                                $cliente_key = $agendamento['paciente_email'] . '_' . $agendamento['paciente_telefone'];
                                $cliente_data = $clientes[$cliente_key] ?? null;
                                $cliente_foto = $cliente_data['foto'] ?? '';
                                $status_colors = [
                                    'pendente' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    'confirmado' => 'bg-green-100 text-green-800 border-green-200',
                                    'cancelado' => 'bg-red-100 text-red-800 border-red-200',
                                    'concluido' => 'bg-blue-100 text-blue-800 border-blue-200'
                                ];
                                $status_color = $status_colors[$agendamento['status']] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                                ?>
                                <div class="flex items-center p-4 bg-white border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
                                    <div class="flex-shrink-0 mr-4">
                                        <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-gray-200">
                                            <img src="<?php echo Upload::getImageUrl($cliente_foto, 'cliente', $agendamento['paciente_nome']); ?>" 
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
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Visualização da Semana -->
                <div id="week-view" class="calendar-view <?php echo $view === 'week' ? '' : 'hidden'; ?>">
                    <div class="grid grid-cols-7 gap-2 mb-4">
                        <?php
                        // Encontrar o domingo da semana atual
                        $startOfWeek = clone $current_date;
                        $dia_semana_atual = (int)$startOfWeek->format('w'); // 0=domingo, 1=segunda, etc
                        if ($dia_semana_atual > 0) {
                            $startOfWeek->modify('-' . $dia_semana_atual . ' days'); // Voltar para o domingo
                        }
                        $inicio_semana = $startOfWeek->format('Y-m-d');
                        ?>
                        <?php for ($i = 0; $i < 7; $i++): ?>
                            <?php 
                            $data_dia = date('Y-m-d', strtotime($inicio_semana . " +{$i} days"));
                            $dia_nome = $dias_semana[$i]; // Agora corresponde corretamente: 0=Dom, 1=Seg, etc
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
                            $agendamentos_dia = array_filter($agendamentos_dentista, function($agendamento) use ($data_dia) {
                                // Verificar se a data é válida
                                if (empty($agendamento['data']) || trim($agendamento['data']) === '') {
                                    return false;
                                }
                                $data_agendamento = strtotime($agendamento['data']);
                                if ($data_agendamento === false) {
                                    return false;
                                }
                                return date('Y-m-d', $data_agendamento) === $data_dia;
                            });
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
                </div>

                <!-- Visualização do Mês -->
                <div id="month-view" class="calendar-view <?php echo $view === 'month' ? '' : 'hidden'; ?>">
                    <div class="grid grid-cols-7 gap-1 mb-4">
                        <?php foreach ($dias_semana as $dia): ?>
                            <div class="text-center text-sm font-medium text-gray-500 py-2"><?php echo $dia; ?></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="grid grid-cols-7 gap-1">
                        <?php
                        $primeiro_dia_mes = $current_date->format('Y-m-01');
                        $ultimo_dia_mes = $current_date->format('Y-m-t');
                        // Obter o dia da semana do PRIMEIRO DIA do mês (0=domingo, 6=sábado)
                        $primeiro_dia_semana = date('w', strtotime($primeiro_dia_mes));
                        $dias_no_mes = $current_date->format('t');
                        
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
                            $agendamentos_dia = array_filter($agendamentos_dentista, function($agendamento) use ($data_atual) {
                                // Verificar se a data é válida
                                if (empty($agendamento['data']) || trim($agendamento['data']) === '') {
                                    return false;
                                }
                                $data_agendamento = strtotime($agendamento['data']);
                                if ($data_agendamento === false) {
                                    return false;
                                }
                                return date('Y-m-d', $data_agendamento) === $data_atual;
                            });
                            $is_today = $data_atual === $hoje;
                            $is_weekend = in_array(date('w', strtotime($data_atual)), [0, 6]);
                            $tem_agendamentos = !empty($agendamentos_dia);
                            ?>
                            <div class="h-20 p-1 border border-gray-200 rounded <?php echo $is_today ? 'bg-blue-50 border-blue-300' : ($is_weekend ? 'bg-gray-50' : 'bg-white'); ?> hover:bg-gray-100 <?php echo $tem_agendamentos ? 'cursor-pointer' : ''; ?> transition-colors"
                                 <?php if ($tem_agendamentos): ?>
                                 onclick="openDayPanel('<?php echo $data_atual; ?>')"
                                 <?php endif; ?>>
                                <div class="flex items-center justify-between mb-1">
                                    <div class="font-medium text-sm <?php echo $is_today ? 'text-blue-600' : 'text-gray-900'; ?>">
                                        <?php echo $dia; ?>
                                    </div>
                                    <?php if ($tem_agendamentos): ?>
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
                                        <div class="text-xs text-blue-600 font-semibold">+<?php echo count($agendamentos_dia) - 2; ?> mais</div>
                                    <?php endif; ?>
                                </div>
                    </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Painel Lateral de Agendamentos do Dia -->
        <div id="day-panel-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden transition-opacity" onclick="closeDayPanel()"></div>
        <div id="day-panel" class="fixed right-0 top-0 h-full w-full md:w-96 bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300">
            <div class="flex flex-col h-full">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-600 to-cyan-600">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-white" id="day-panel-title">Agendamentos do Dia</h3>
                            <p class="text-sm text-blue-100" id="day-panel-date"></p>
                        </div>
                        <button onclick="closeDayPanel()" class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Conteúdo -->
                <div class="flex-1 overflow-y-auto p-6" id="day-panel-content">
                    <div class="flex items-center justify-center py-12">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <span class="ml-3 text-gray-600">Carregando...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Detalhes do Agendamento -->
        <div id="agendamento-modal-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden transition-opacity" onclick="closeAgendamentoModal()"></div>
        <div id="agendamento-modal" class="fixed right-0 top-0 h-full w-full md:w-[600px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300">
            <div class="flex flex-col h-full">
                <!-- Header -->
                <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-purple-600 to-pink-600">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-white">Detalhes do Agendamento</h3>
                            <p class="text-sm text-purple-100 mt-1">Visualizar e editar informações</p>
                        </div>
                        <button onclick="closeAgendamentoModal()" class="text-white hover:bg-white hover:bg-opacity-20 rounded-xl p-2 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Conteúdo -->
                <div class="flex-1 overflow-y-auto" id="agendamento-modal-content">
                    <div class="flex items-center justify-center py-12">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                        <span class="ml-3 text-gray-600">Carregando...</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Próximos Agendamentos -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-cyan-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-calendar-check text-white text-lg"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Próximos Agendamentos</h3>
                    </div>
                </div>
                <div class="p-6">
                    <?php if (empty($proximos_agendamentos)): ?>
                        <div class="text-center py-12">
                            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-calendar-times text-4xl text-gray-300"></i>
                            </div>
                            <p class="text-gray-500 font-medium">Nenhum agendamento próximo</p>
                            <p class="text-sm text-gray-400 mt-1">Novos agendamentos aparecerão aqui</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($proximos_agendamentos as $agendamento): ?>
                                <?php 
                                // Buscar dados do cliente para obter a foto
                                $cliente_key = $agendamento['paciente_email'] . '_' . $agendamento['paciente_telefone'];
                                $cliente_data = $clientes[$cliente_key] ?? null;
                                $cliente_foto = $cliente_data['foto'] ?? '';
                                
                                // Cores por status
                                $status_colors = [
                                    'pendente' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-200', 'badge' => 'bg-yellow-100 text-yellow-800 border-yellow-200', 'icon' => 'fa-clock', 'icon_bg' => 'bg-yellow-100', 'icon_color' => 'text-yellow-600'],
                                    'confirmado' => ['bg' => 'bg-green-50', 'border' => 'border-green-200', 'badge' => 'bg-green-100 text-green-800 border-green-200', 'icon' => 'fa-check-circle', 'icon_bg' => 'bg-green-100', 'icon_color' => 'text-green-600'],
                                    'cancelado' => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'badge' => 'bg-red-100 text-red-800 border-red-200', 'icon' => 'fa-times-circle', 'icon_bg' => 'bg-red-100', 'icon_color' => 'text-red-600'],
                                    'concluido' => ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'badge' => 'bg-blue-100 text-blue-800 border-blue-200', 'icon' => 'fa-check-double', 'icon_bg' => 'bg-blue-100', 'icon_color' => 'text-blue-600']
                                ];
                                $status = $status_colors[$agendamento['status']] ?? $status_colors['pendente'];
                                ?>
                                <div onclick="openAgendamentoModal('<?php echo $agendamento['id']; ?>')" 
                                     class="flex items-center gap-4 p-4 <?php echo $status['bg']; ?> border <?php echo $status['border']; ?> rounded-xl hover:shadow-lg cursor-pointer transition-all duration-200 group hover:-translate-y-1">
                                    <div class="flex-shrink-0 relative">
                                        <img src="<?php echo Upload::getImageUrl($cliente_foto, 'cliente', $agendamento['paciente_nome']); ?>" 
                                             alt="Foto de <?php echo htmlspecialchars($agendamento['paciente_nome']); ?>" 
                                             class="w-14 h-14 rounded-xl object-cover border-2 border-white shadow-md group-hover:scale-110 transition-transform">
                                        <div class="absolute -bottom-1 -right-1 w-6 h-6 <?php echo $status['icon_bg']; ?> rounded-lg flex items-center justify-center border-2 border-white">
                                            <i class="fas <?php echo $status['icon']; ?> <?php echo $status['icon_color']; ?> text-xs"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-gray-900 truncate"><?php echo htmlspecialchars($agendamento['paciente_nome']); ?></p>
                                        <p class="text-sm text-gray-600 flex items-center gap-1 mt-0.5">
                                            <i class="fas fa-tooth text-gray-400"></i>
                                            <?php echo htmlspecialchars($agendamento['servico']); ?>
                                        </p>
                                        <p class="text-xs text-gray-500 flex items-center gap-1 mt-1">
                                            <i class="far fa-calendar text-gray-400"></i>
                                            <?php echo date('d/m/Y', strtotime($agendamento['data'])); ?>
                                            <span class="mx-1">•</span>
                                            <i class="far fa-clock text-gray-400"></i>
                                            <?php echo date('H:i', strtotime($agendamento['data'])); ?>
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0 flex items-center gap-2">
                                        <span class="px-3 py-1.5 text-xs font-bold rounded-lg border <?php echo $status['badge']; ?> flex items-center gap-1.5">
                                            <i class="fas <?php echo $status['icon']; ?>"></i>
                                            <?php echo ucfirst($agendamento['status']); ?>
                                        </span>
                                        <i class="fas fa-chevron-right text-gray-400 group-hover:text-gray-600 transition-colors"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Botão Ver Todos -->
                        <?php if ($total_proximos > 10): ?>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <a href="agendamentos.php" class="flex items-center justify-center gap-2 w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white rounded-xl font-semibold transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5">
                                    <i class="fas fa-th-list"></i>
                                    Ver Todos os <?php echo $total_proximos; ?> Agendamentos
                                    <i class="fas fa-arrow-right text-sm"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <a href="agendamentos.php" class="flex items-center justify-center gap-2 w-full py-3 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition-all">
                                    <i class="fas fa-th-list"></i>
                                    Ver Todos os Agendamentos
                                    <i class="fas fa-arrow-right text-sm"></i>
                                </a>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ações Rápidas -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-pink-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-bolt text-white text-lg"></i>
                </div>
                        <h3 class="text-xl font-bold text-gray-900">Ações Rápidas</h3>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <a href="configuracoes.php" class="group bg-gradient-to-br from-blue-50 to-cyan-50 border-2 border-blue-100 hover:border-blue-300 rounded-xl p-4 transition-all duration-200 hover:shadow-lg hover:-translate-y-1">
                            <div class="flex items-start gap-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-600 to-cyan-600 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-cog text-white text-xl"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-gray-900 text-sm mb-1">Configurar Horários</p>
                                    <p class="text-xs text-gray-600">Defina seus horários de atendimento</p>
                                </div>
                            </div>
                        </a>

                        <a href="servicos.php" class="group bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-100 hover:border-green-300 rounded-xl p-4 transition-all duration-200 hover:shadow-lg hover:-translate-y-1">
                            <div class="flex items-start gap-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-600 to-emerald-600 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-list text-white text-xl"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-gray-900 text-sm mb-1">Gerenciar Serviços</p>
                                    <p class="text-xs text-gray-600">Adicione ou edite seus serviços</p>
                                </div>
                            </div>
                        </a>

                        <a href="agendamentos.php" class="group bg-gradient-to-br from-purple-50 to-pink-50 border-2 border-purple-100 hover:border-purple-300 rounded-xl p-4 transition-all duration-200 hover:shadow-lg hover:-translate-y-1">
                            <div class="flex items-start gap-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-purple-600 to-pink-600 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-calendar-alt text-white text-xl"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-gray-900 text-sm mb-1">Todos Agendamentos</p>
                                    <p class="text-xs text-gray-600">Gerencie suas consultas</p>
                                </div>
                            </div>
                        </a>

                        <a href="clientes.php" class="group bg-gradient-to-br from-indigo-50 to-blue-50 border-2 border-indigo-100 hover:border-indigo-300 rounded-xl p-4 transition-all duration-200 hover:shadow-lg hover:-translate-y-1">
                            <div class="flex items-start gap-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-indigo-600 to-blue-600 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-users text-white text-xl"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-gray-900 text-sm mb-1">Meus Clientes</p>
                                    <p class="text-xs text-gray-600">Visualize e gerencie</p>
                                </div>
                            </div>
                        </a>

                        <a href="perfil_dentista.php" class="group bg-gradient-to-br from-pink-50 to-rose-50 border-2 border-pink-100 hover:border-pink-300 rounded-xl p-4 transition-all duration-200 hover:shadow-lg hover:-translate-y-1">
                            <div class="flex items-start gap-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-pink-600 to-rose-600 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-user-md text-white text-xl"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-gray-900 text-sm mb-1">Meu Perfil</p>
                                    <p class="text-xs text-gray-600">Gerencie suas informações</p>
                                </div>
                            </div>
                        </a>

                        <a href="dentistas_consultorio.php" class="group bg-gradient-to-br from-violet-50 to-purple-50 border-2 border-violet-100 hover:border-violet-300 rounded-xl p-4 transition-all duration-200 hover:shadow-lg hover:-translate-y-1">
                            <div class="flex items-start gap-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-violet-600 to-purple-600 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-user-friends text-white text-xl"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-gray-900 text-sm mb-1">Dentistas</p>
                                    <p class="text-xs text-gray-600">Gerencie os profissionais</p>
                                </div>
                            </div>
                        </a>

                        <a href="financeiro.php" class="group bg-gradient-to-br from-emerald-50 to-teal-50 border-2 border-emerald-100 hover:border-emerald-300 rounded-xl p-4 transition-all duration-200 hover:shadow-lg hover:-translate-y-1">
                            <div class="flex items-start gap-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-emerald-600 to-teal-600 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-chart-line text-white text-xl"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-gray-900 text-sm mb-1">Controle Financeiro</p>
                                    <p class="text-xs text-gray-600">Gerencie entradas e saídas</p>
                                </div>
                            </div>
                        </a>

                        <a href="agendar.php?username=<?php echo $dentista['username']; ?>" target="_blank" class="group bg-gradient-to-br from-orange-50 to-amber-50 border-2 border-orange-100 hover:border-orange-300 rounded-xl p-4 transition-all duration-200 hover:shadow-lg hover:-translate-y-1">
                            <div class="flex items-start gap-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-orange-600 to-amber-600 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-external-link-alt text-white text-xl"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-gray-900 text-sm mb-1">Página Pública</p>
                                    <p class="text-xs text-gray-600">Link: <?php echo $dentista['username']; ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Link Compartilhável -->
        <div class="mt-6 sm:mt-8 bg-white rounded-lg shadow p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-3 sm:mb-4">Seu Link de Agendamento</h3>
            <div class="flex flex-col sm:flex-row sm:items-center space-y-3 sm:space-y-0 sm:space-x-4">
                <input type="text" readonly value="odontfy.com/<?php echo $dentista['username']; ?>" 
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm">
                <button onclick="copyLink()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-copy mr-1 sm:mr-2"></i>Copiar
                </button>
            </div>
            <p class="text-xs sm:text-sm text-gray-500 mt-2">
                Compartilhe este link com seus pacientes para que eles possam agendar consultas online.
            </p>
        </div>
        </div>
    </div>

    <!-- Notificação Toast -->
    <div id="notification-toast" class="fixed top-4 right-4 z-50 transform translate-x-full transition-transform duration-300 ease-in-out">
        <div class="bg-white rounded-lg shadow-2xl border-l-4 border-green-500 p-4 max-w-md">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                        <i class="fas fa-calendar-check text-green-600 text-xl"></i>
                    </div>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-bold text-gray-900">
                        <i class="fas fa-bell mr-2 text-green-600"></i>Novo Agendamento!
                    </h3>
                    <p id="notification-message" class="text-sm text-gray-600 mt-1"></p>
                    <div id="notification-details" class="mt-2 text-xs text-gray-500 space-y-1"></div>
                </div>
                <button onclick="closeNotification()" class="ml-4 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Toast de Feedback -->
    <div id="feedback-toast" class="fixed top-4 right-4 z-50 transform translate-x-full transition-all duration-300 ease-in-out">
        <div id="feedback-toast-content" class="bg-white rounded-2xl shadow-2xl border-l-4 p-4 max-w-md">
            <div class="flex items-start gap-3">
                <div id="feedback-icon" class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center">
                    <i class="text-xl"></i>
                </div>
                <div class="flex-1">
                    <h3 id="feedback-title" class="text-lg font-bold text-gray-900"></h3>
                    <p id="feedback-message" class="text-sm text-gray-600 mt-1"></p>
                </div>
                <button onclick="closeFeedbackToast()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação -->
    <div id="confirm-modal-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden transition-opacity duration-300" onclick="closeConfirmModal()"></div>
    <div id="confirm-modal" class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 z-[70] hidden">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md mx-4 overflow-hidden animate-fade-in">
            <div id="confirm-header" class="px-6 py-5 bg-gradient-to-r">
                <div class="flex items-center gap-3">
                    <div id="confirm-icon-container" class="w-14 h-14 rounded-2xl flex items-center justify-center">
                        <i id="confirm-icon" class="text-2xl text-white"></i>
                    </div>
                    <h3 id="confirm-title" class="text-xl font-bold text-white"></h3>
                </div>
            </div>
            <div class="p-6">
                <p id="confirm-message" class="text-gray-700 text-base mb-6"></p>
                <div class="flex gap-3">
                    <button onclick="closeConfirmModal()" class="flex-1 py-3 px-4 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-semibold transition-all">
                        Cancelar
                    </button>
                    <button id="confirm-button" class="flex-1 py-3 px-4 text-white rounded-xl font-semibold transition-all shadow-lg hover:-translate-y-0.5">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Painel de Notificações -->
    <div id="notifications-panel" class="fixed inset-y-0 right-0 w-96 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 z-50">
        <div class="h-full flex flex-col">
            <!-- Header do Painel -->
            <div class="bg-gradient-to-r from-blue-600 to-cyan-600 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-bell text-white text-2xl mr-3"></i>
                        <div>
                            <h2 class="text-xl font-bold text-white">Notificações</h2>
                            <p class="text-blue-100 text-sm" id="notifications-subtitle">Nenhuma notificação nova</p>
                        </div>
                    </div>
                    <button onclick="closeNotificationsPanel()" class="text-white hover:text-blue-100 transition-colors">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
            </div>

            <!-- Lista de Notificações -->
            <div class="flex-1 overflow-y-auto p-4" id="notifications-list">
                <div class="text-center py-12 text-gray-400">
                    <i class="fas fa-bell-slash text-6xl mb-4"></i>
                    <p class="text-lg font-medium">Nenhuma notificação</p>
                    <p class="text-sm mt-2">Você está em dia!</p>
                </div>
            </div>

            <!-- Footer do Painel -->
            <div class="border-t border-gray-200 p-4">
                <button onclick="clearAllNotifications()" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 rounded-lg font-medium transition-colors">
                    <i class="fas fa-check-double mr-2"></i>Marcar todas como lidas
                </button>
            </div>
        </div>
    </div>

    <!-- Overlay do Painel -->
    <div id="notifications-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden" onclick="closeNotificationsPanel()"></div>

    <script>
        let currentView = '<?php echo $view; ?>';
        let currentDate = new Date('<?php echo $current_date->format('Y-m-d'); ?>');
        let lastCheckTime = null; // Será definido na primeira verificação
        let isFirstCheck = true; // Flag para primeira verificação
        let notificationSound = null;
        let notificationsList = []; // Array para armazenar notificações

        function copyLink() {
            const input = document.querySelector('input[readonly]');
            input.select();
            document.execCommand('copy');
            
            // Feedback visual
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check mr-2"></i>Copiado!';
            button.classList.add('bg-green-600', 'hover:bg-green-700');
            button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove('bg-green-600', 'hover:bg-green-700');
                button.classList.add('bg-blue-600', 'hover:bg-blue-700');
            }, 2000);
        }

        function changeView(view) {
            currentView = view;
            
            // Recarregar página com a nova view
            const dateStr = currentDate.toISOString().split('T')[0];
            const params = new URLSearchParams(window.location.search);
            params.set('view', view);
            params.set('date', dateStr);
            window.location.href = 'dashboard.php?' + params.toString();
        }

        function previousPeriod() {
            switch(currentView) {
                case 'day':
                    currentDate.setDate(currentDate.getDate() - 1);
                    break;
                case 'week':
                    currentDate.setDate(currentDate.getDate() - 7);
                    break;
                case 'month':
                    currentDate.setMonth(currentDate.getMonth() - 1);
                    break;
            }
            reloadPageWithDate();
        }

        function nextPeriod() {
            switch(currentView) {
                case 'day':
                    currentDate.setDate(currentDate.getDate() + 1);
                    break;
                case 'week':
                    currentDate.setDate(currentDate.getDate() + 7);
                    break;
                case 'month':
                    currentDate.setMonth(currentDate.getMonth() + 1);
                    break;
            }
            reloadPageWithDate();
        }

        function reloadPageWithDate() {
            const dateStr = currentDate.toISOString().split('T')[0];
            const params = new URLSearchParams(window.location.search);
            params.set('view', currentView);
            params.set('date', dateStr);
            window.location.href = 'dashboard.php?' + params.toString();
        }

        // Funções do Painel de Agendamentos do Dia
        function openDayPanel(date) {
            const panel = document.getElementById('day-panel');
            const overlay = document.getElementById('day-panel-overlay');
            
            // Mostrar overlay e painel
            overlay.classList.remove('hidden');
            setTimeout(() => {
                panel.classList.remove('translate-x-full');
            }, 10);
            
            // Formatar data para exibição
            const dateObj = new Date(date + 'T12:00:00');
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const formattedDate = dateObj.toLocaleDateString('pt-BR', options);
            
            document.getElementById('day-panel-date').textContent = formattedDate;
            
            // Carregar agendamentos do dia
            loadDayAppointments(date);
        }

        function closeDayPanel() {
            const panel = document.getElementById('day-panel');
            const overlay = document.getElementById('day-panel-overlay');
            
            panel.classList.add('translate-x-full');
            setTimeout(() => {
                overlay.classList.add('hidden');
            }, 300);
        }

        function loadDayAppointments(date) {
            const content = document.getElementById('day-panel-content');
            
            // Mostrar loading
            content.innerHTML = `
                <div class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="ml-3 text-gray-600">Carregando...</span>
                </div>
            `;

            // Buscar agendamentos do dia via PHP (filtrar do array já carregado)
            fetch(`api/get_day_appointments.php?date=${date}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.agendamentos && data.agendamentos.length > 0) {
                        let html = '<div class="space-y-4">';
                        
                        data.agendamentos.forEach(agendamento => {
                            const statusColors = {
                                'pendente': 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                'confirmado': 'bg-green-100 text-green-800 border-green-200',
                                'cancelado': 'bg-red-100 text-red-800 border-red-200',
                                'concluido': 'bg-blue-100 text-blue-800 border-blue-200'
                            };
                            const statusColor = statusColors[agendamento.status] || 'bg-gray-100 text-gray-800 border-gray-200';
                            
                            const hora = agendamento.data.split(' ')[1].substring(0, 5);
                            
                            html += `
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-user text-blue-600 text-lg"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-gray-900">${agendamento.paciente_nome}</h4>
                                                <p class="text-sm text-gray-500">
                                                    <i class="far fa-clock mr-1"></i>${hora}
                                                </p>
                                            </div>
                                        </div>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium border ${statusColor}">
                                            ${agendamento.status.charAt(0).toUpperCase() + agendamento.status.slice(1)}
                                        </span>
                                    </div>
                                    
                                    <div class="space-y-2 text-sm">
                                        <div class="flex items-center text-gray-600">
                                            <i class="fas fa-tooth w-5 text-gray-400"></i>
                                            <span class="ml-2">${agendamento.servico}</span>
                                        </div>
                                        <div class="flex items-center text-gray-600">
                                            <i class="fas fa-envelope w-5 text-gray-400"></i>
                                            <span class="ml-2">${agendamento.paciente_email}</span>
                                        </div>
                                        <div class="flex items-center text-gray-600">
                                            <i class="fas fa-phone w-5 text-gray-400"></i>
                                            <span class="ml-2">${agendamento.paciente_telefone}</span>
                                        </div>
                                        ${agendamento.observacoes ? `
                                        <div class="flex items-start text-gray-600 mt-2 pt-2 border-t border-gray-100">
                                            <i class="fas fa-comment-alt w-5 text-gray-400 mt-0.5"></i>
                                            <span class="ml-2">${agendamento.observacoes}</span>
                                        </div>
                                        ` : ''}
                                    </div>
                                </div>
                            `;
                        });
                        
                        html += '</div>';
                        content.innerHTML = html;
                    } else {
                        content.innerHTML = `
                            <div class="text-center py-12">
                                <i class="fas fa-calendar-times text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500">Nenhum agendamento para este dia</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar agendamentos:', error);
                    content.innerHTML = `
                        <div class="text-center py-12">
                            <i class="fas fa-exclamation-circle text-4xl text-red-300 mb-4"></i>
                            <p class="text-red-500">Erro ao carregar agendamentos</p>
                        </div>
                    `;
                });
        }

        // Funções do Modal de Agendamento
        function openAgendamentoModal(agendamentoId) {
            const modal = document.getElementById('agendamento-modal');
            const overlay = document.getElementById('agendamento-modal-overlay');
            
            // Mostrar overlay e modal
            overlay.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('translate-x-full');
            }, 10);
            
            // Carregar dados do agendamento
            loadAgendamentoDetails(agendamentoId);
        }

        function closeAgendamentoModal() {
            const modal = document.getElementById('agendamento-modal');
            const overlay = document.getElementById('agendamento-modal-overlay');
            
            modal.classList.add('translate-x-full');
            setTimeout(() => {
                overlay.classList.add('hidden');
            }, 300);
        }

        function loadAgendamentoDetails(agendamentoId) {
            const content = document.getElementById('agendamento-modal-content');
            
            // Mostrar loading
            content.innerHTML = `
                <div class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                    <span class="ml-3 text-gray-600">Carregando...</span>
                </div>
            `;
            
            // Buscar dados do agendamento
            fetch(`api/get_agendamento.php?id=${agendamentoId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.agendamento) {
                        const ag = data.agendamento;
                        const statusColors = {
                            'pendente': {bg: 'bg-yellow-50', border: 'border-yellow-200', text: 'text-yellow-800', badge: 'bg-yellow-100'},
                            'confirmado': {bg: 'bg-green-50', border: 'border-green-200', text: 'text-green-800', badge: 'bg-green-100'},
                            'cancelado': {bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-800', badge: 'bg-red-100'},
                            'concluido': {bg: 'bg-blue-50', border: 'border-blue-200', text: 'text-blue-800', badge: 'bg-blue-100'}
                        };
                        const status = statusColors[ag.status] || statusColors['pendente'];
                        
                        content.innerHTML = `
                            <div class="p-6 space-y-6">
                                <!-- Info do Paciente -->
                                <div class="${status.bg} border-2 ${status.border} rounded-2xl p-6">
                                    <div class="flex items-center gap-4 mb-4">
                                        <div class="w-20 h-20 bg-gradient-to-br from-purple-600 to-pink-600 rounded-2xl flex items-center justify-center">
                                            <i class="fas fa-user text-white text-3xl"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-2xl font-bold text-gray-900">${ag.paciente_nome}</h3>
                                            <span class="inline-flex items-center gap-2 px-4 py-1.5 ${status.badge} ${status.text} rounded-full text-sm font-bold mt-2">
                                                <i class="fas fa-circle text-xs"></i>
                                                ${ag.status.charAt(0).toUpperCase() + ag.status.slice(1)}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Informações do Agendamento -->
                                <div class="space-y-4">
                                    <h4 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                        <i class="fas fa-info-circle text-purple-600"></i>
                                        Informações do Agendamento
                                    </h4>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl p-4 border-2 border-blue-100">
                                            <div class="text-sm text-gray-600 mb-1">Data</div>
                                            <div class="font-bold text-gray-900 text-lg flex items-center gap-2">
                                                <i class="far fa-calendar text-blue-600"></i>
                                                ${new Date(ag.data).toLocaleDateString('pt-BR')}
                                            </div>
                                        </div>
                                        
                                        <div class="bg-gradient-to-br from-cyan-50 to-blue-50 rounded-xl p-4 border-2 border-cyan-100">
                                            <div class="text-sm text-gray-600 mb-1">Horário</div>
                                            <div class="font-bold text-gray-900 text-lg flex items-center gap-2">
                                                <i class="far fa-clock text-cyan-600"></i>
                                                ${ag.data.split(' ')[1].substring(0, 5)}
                                            </div>
                                        </div>
                                        
                                        <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-4 border-2 border-purple-100 md:col-span-2">
                                            <div class="text-sm text-gray-600 mb-1">Serviço</div>
                                            <div class="font-bold text-gray-900 text-lg flex items-center gap-2">
                                                <i class="fas fa-tooth text-purple-600"></i>
                                                ${ag.servico}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Contato -->
                                <div class="space-y-4">
                                    <h4 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                        <i class="fas fa-address-book text-purple-600"></i>
                                        Contato
                                    </h4>
                                    
                                    <div class="space-y-3">
                                        <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                                            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                                                <i class="fas fa-envelope text-blue-600"></i>
                                            </div>
                                            <div class="flex-1">
                                                <div class="text-sm text-gray-600">Email</div>
                                                <div class="font-semibold text-gray-900">${ag.paciente_email}</div>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                                            <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                                                <i class="fas fa-phone text-green-600"></i>
                                            </div>
                                            <div class="flex-1">
                                                <div class="text-sm text-gray-600">Telefone</div>
                                                <div class="font-semibold text-gray-900">${ag.paciente_telefone}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                ${ag.observacoes ? `
                                <div class="space-y-4">
                                    <h4 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                        <i class="fas fa-comment-alt text-purple-600"></i>
                                        Observações
                                    </h4>
                                    <div class="bg-gray-50 rounded-xl p-4 border-2 border-gray-200">
                                        <p class="text-gray-700">${ag.observacoes}</p>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <!-- Ações -->
                                <div class="pt-4 border-t-2 border-gray-200 space-y-3">
                                    ${ag.status === 'pendente' ? `
                                        <button onclick="confirmarAgendamento('${ag.id}')" class="w-full py-4 px-6 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-bold shadow-lg transition-all hover:-translate-y-1 flex items-center justify-center gap-2">
                                            <i class="fas fa-check-circle"></i>
                                            Confirmar Agendamento
                                        </button>
                                        <button onclick="cancelarAgendamento('${ag.id}')" class="w-full py-4 px-6 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white rounded-xl font-bold shadow-lg transition-all hover:-translate-y-1 flex items-center justify-center gap-2">
                                            <i class="fas fa-times-circle"></i>
                                            Cancelar Agendamento
                                        </button>
                                    ` : ''}
                                    ${ag.status === 'confirmado' ? `
                                        <button onclick="concluirAgendamento('${ag.id}')" class="w-full py-4 px-6 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white rounded-xl font-bold shadow-lg transition-all hover:-translate-y-1 flex items-center justify-center gap-2">
                                            <i class="fas fa-check-double"></i>
                                            Marcar como Concluído
                                        </button>
                                    ` : ''}
                                    <button onclick="closeAgendamentoModal()" class="w-full py-4 px-6 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-bold transition-all flex items-center justify-center gap-2">
                                        <i class="fas fa-times"></i>
                                        Fechar
                                    </button>
                                </div>
                            </div>
                        `;
                    } else {
                        content.innerHTML = `
                            <div class="text-center py-12 px-6">
                                <i class="fas fa-exclamation-circle text-5xl text-red-300 mb-4"></i>
                                <p class="text-red-500 font-medium">Erro ao carregar agendamento</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar agendamento:', error);
                    content.innerHTML = `
                        <div class="text-center py-12 px-6">
                            <i class="fas fa-exclamation-circle text-5xl text-red-300 mb-4"></i>
                            <p class="text-red-500 font-medium">Erro ao carregar agendamento</p>
                        </div>
                    `;
                });
        }

        // Funções do Modal de Confirmação Customizado
        function showConfirmModal(title, message, type, onConfirm) {
            const modal = document.getElementById('confirm-modal');
            const overlay = document.getElementById('confirm-modal-overlay');
            const header = document.getElementById('confirm-header');
            const icon = document.getElementById('confirm-icon');
            const iconContainer = document.getElementById('confirm-icon-container');
            const titleEl = document.getElementById('confirm-title');
            const messageEl = document.getElementById('confirm-message');
            const confirmBtn = document.getElementById('confirm-button');
            
            // Configurar cores e ícones por tipo
            const configs = {
                success: {
                    gradient: 'from-green-600 to-emerald-600',
                    iconBg: 'bg-white bg-opacity-20',
                    icon: 'fa-check-circle',
                    btnColor: 'bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700'
                },
                danger: {
                    gradient: 'from-red-600 to-pink-600',
                    iconBg: 'bg-white bg-opacity-20',
                    icon: 'fa-exclamation-triangle',
                    btnColor: 'bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700'
                },
                info: {
                    gradient: 'from-blue-600 to-cyan-600',
                    iconBg: 'bg-white bg-opacity-20',
                    icon: 'fa-info-circle',
                    btnColor: 'bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700'
                }
            };
            
            const config = configs[type] || configs.info;
            
            // Aplicar estilos
            header.className = `px-6 py-5 bg-gradient-to-r ${config.gradient}`;
            iconContainer.className = `w-14 h-14 rounded-2xl flex items-center justify-center ${config.iconBg}`;
            icon.className = `fas ${config.icon} text-2xl text-white`;
            confirmBtn.className = `flex-1 py-3 px-4 text-white rounded-xl font-semibold transition-all shadow-lg hover:-translate-y-0.5 ${config.btnColor}`;
            
            // Definir conteúdo
            titleEl.textContent = title;
            messageEl.textContent = message;
            
            // Definir ação de confirmação
            confirmBtn.onclick = () => {
                closeConfirmModal();
                onConfirm();
            };
            
            // Mostrar modal
            overlay.classList.remove('hidden');
            modal.classList.remove('hidden');
        }

        function closeConfirmModal() {
            const modal = document.getElementById('confirm-modal');
            const overlay = document.getElementById('confirm-modal-overlay');
            modal.classList.add('hidden');
            overlay.classList.add('hidden');
        }

        // Função para mostrar toast de feedback
        function showFeedbackToast(title, message, type = 'success') {
            const toast = document.getElementById('feedback-toast');
            const content = document.getElementById('feedback-toast-content');
            const iconEl = document.getElementById('feedback-icon');
            const titleEl = document.getElementById('feedback-title');
            const messageEl = document.getElementById('feedback-message');
            
            const configs = {
                success: {
                    border: 'border-green-500',
                    iconBg: 'bg-green-100',
                    iconColor: 'text-green-600',
                    icon: 'fa-check-circle'
                },
                error: {
                    border: 'border-red-500',
                    iconBg: 'bg-red-100',
                    iconColor: 'text-red-600',
                    icon: 'fa-times-circle'
                },
                info: {
                    border: 'border-blue-500',
                    iconBg: 'bg-blue-100',
                    iconColor: 'text-blue-600',
                    icon: 'fa-info-circle'
                }
            };
            
            const config = configs[type] || configs.success;
            
            content.className = `bg-white rounded-2xl shadow-2xl border-l-4 ${config.border} p-4 max-w-md`;
            iconEl.className = `flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center ${config.iconBg}`;
            iconEl.querySelector('i').className = `fas ${config.icon} ${config.iconColor} text-xl`;
            titleEl.textContent = title;
            messageEl.textContent = message;
            
            // Mostrar toast
            toast.classList.remove('translate-x-full');
            toast.classList.add('translate-x-0');
            
            // Auto-fechar após 5 segundos
            setTimeout(() => {
                closeFeedbackToast();
            }, 5000);
        }

        function closeFeedbackToast() {
            const toast = document.getElementById('feedback-toast');
            toast.classList.remove('translate-x-0');
            toast.classList.add('translate-x-full');
        }

        function confirmarAgendamento(id) {
            showConfirmModal(
                'Confirmar Agendamento',
                'Tem certeza que deseja confirmar este agendamento? O paciente será notificado.',
                'success',
                () => {
                    fetch('api/update_status_agendamento.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id: id, status: 'confirmado'})
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showFeedbackToast('Sucesso!', 'Agendamento confirmado com sucesso.', 'success');
                            setTimeout(() => {
                                closeAgendamentoModal();
                                location.reload();
                            }, 1500);
                        } else {
                            showFeedbackToast('Erro!', 'Não foi possível confirmar o agendamento.', 'error');
                        }
                    })
                    .catch(error => {
                        showFeedbackToast('Erro!', 'Erro ao processar solicitação.', 'error');
                    });
                }
            );
        }

        function cancelarAgendamento(id) {
            showConfirmModal(
                'Cancelar Agendamento',
                'Tem certeza que deseja cancelar este agendamento? Esta ação não pode ser desfeita.',
                'danger',
                () => {
                    fetch('api/update_status_agendamento.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id: id, status: 'cancelado'})
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showFeedbackToast('Cancelado!', 'Agendamento cancelado com sucesso.', 'success');
                            setTimeout(() => {
                                closeAgendamentoModal();
                                location.reload();
                            }, 1500);
                        } else {
                            showFeedbackToast('Erro!', 'Não foi possível cancelar o agendamento.', 'error');
                        }
                    })
                    .catch(error => {
                        showFeedbackToast('Erro!', 'Erro ao processar solicitação.', 'error');
                    });
                }
            );
        }

        function concluirAgendamento(id) {
            showConfirmModal(
                'Concluir Agendamento',
                'Deseja marcar este agendamento como concluído?',
                'info',
                () => {
                    fetch('api/update_status_agendamento.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id: id, status: 'concluido'})
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showFeedbackToast('Concluído!', 'Agendamento marcado como concluído.', 'success');
                            setTimeout(() => {
                                closeAgendamentoModal();
                                location.reload();
                            }, 1500);
                        } else {
                            showFeedbackToast('Erro!', 'Não foi possível concluir o agendamento.', 'error');
                        }
                    })
                    .catch(error => {
                        showFeedbackToast('Erro!', 'Erro ao processar solicitação.', 'error');
                    });
                }
            );
        }

        // Criar som de notificação usando Web Audio API
        function createNotificationSound() {
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                
                return function playSound() {
                    // Criar três notas agradáveis (Dó, Mi, Sol)
                    const frequencies = [523.25, 659.25, 783.99]; // C5, E5, G5
                    const duration = 0.15;
                    
                    frequencies.forEach((freq, index) => {
                        const oscillator = audioContext.createOscillator();
                        const gainNode = audioContext.createGain();
                        
                        oscillator.connect(gainNode);
                        gainNode.connect(audioContext.destination);
                        
                        oscillator.frequency.value = freq;
                        oscillator.type = 'sine';
                        
                        // Envelope suave
                        gainNode.gain.setValueAtTime(0, audioContext.currentTime + index * duration);
                        gainNode.gain.linearRampToValueAtTime(0.3, audioContext.currentTime + index * duration + 0.01);
                        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + (index + 1) * duration);
                        
                        oscillator.start(audioContext.currentTime + index * duration);
                        oscillator.stop(audioContext.currentTime + (index + 1) * duration);
                    });
                };
            } catch (e) {
                console.warn('Web Audio API não suportada:', e);
                return function() {
                    // Fallback: usar elemento de áudio simples
                    const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fPTgjMGHm7A7+OZURE=');
                    audio.play();
                };
            }
        }
        
        // Adicionar notificação ao array
        function addNotification(booking) {
            const notification = {
                id: booking.id || Date.now(),
                paciente_nome: booking.paciente_nome,
                servico: booking.servico,
                data: booking.data,
                telefone: booking.paciente_telefone,
                email: booking.paciente_email,
                timestamp: new Date(),
                read: false
            };
            
            notificationsList.unshift(notification); // Adiciona no início
            updateNotificationsList();
            updateNotificationsBadge();
        }
        
        // Atualizar lista de notificações no painel
        function updateNotificationsList() {
            const listContainer = document.getElementById('notifications-list');
            const subtitle = document.getElementById('notifications-subtitle');
            
            if (notificationsList.length === 0) {
                listContainer.innerHTML = `
                    <div class="text-center py-12 text-gray-400">
                        <i class="fas fa-bell-slash text-6xl mb-4"></i>
                        <p class="text-lg font-medium">Nenhuma notificação</p>
                        <p class="text-sm mt-2">Você está em dia!</p>
                </div>
            `;
                subtitle.textContent = 'Nenhuma notificação nova';
            } else {
                const unreadCount = notificationsList.filter(n => !n.read).length;
                subtitle.textContent = `${unreadCount} ${unreadCount === 1 ? 'nova' : 'novas'}`;
                
                listContainer.innerHTML = notificationsList.map(notif => {
                    const dataFormatada = new Date(notif.data).toLocaleString('pt-BR', {
                        day: '2-digit',
                        month: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    const timeAgo = getTimeAgo(notif.timestamp);
                    
                    return `
                        <div class="mb-3 p-4 rounded-lg border-2 ${notif.read ? 'bg-gray-50 border-gray-200' : 'bg-blue-50 border-blue-200'} hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-calendar-check text-green-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">${notif.paciente_nome}</p>
                                        <p class="text-xs text-gray-500">${timeAgo}</p>
                                    </div>
                                </div>
                                ${!notif.read ? '<div class="w-2 h-2 bg-blue-500 rounded-full"></div>' : ''}
                            </div>
                            <div class="ml-13 space-y-1 text-sm text-gray-600">
                                <p><i class="fas fa-tooth mr-2 text-gray-400"></i>${notif.servico}</p>
                                <p><i class="fas fa-calendar mr-2 text-gray-400"></i>${dataFormatada}</p>
                                <p><i class="fas fa-phone mr-2 text-gray-400"></i>${notif.telefone}</p>
                            </div>
                        </div>
                    `;
                }).join('');
            }
        }
        
        // Calcular tempo decorrido
        function getTimeAgo(timestamp) {
            const now = new Date();
            const diff = Math.floor((now - timestamp) / 1000); // em segundos
            
            if (diff < 60) return 'Agora mesmo';
            if (diff < 3600) return `${Math.floor(diff / 60)} min atrás`;
            if (diff < 86400) return `${Math.floor(diff / 3600)}h atrás`;
            return `${Math.floor(diff / 86400)}d atrás`;
        }
        
        // Atualizar badge
        function updateNotificationsBadge() {
            const badge = document.getElementById('notification-badge');
            const count = document.getElementById('notification-count');
            const unreadCount = notificationsList.filter(n => !n.read).length;
            
            if (unreadCount > 0) {
                badge.classList.remove('hidden');
                count.textContent = unreadCount;
            } else {
                badge.classList.add('hidden');
            }
        }
        
        // Abrir painel de notificações
        function openNotificationsPanel() {
            const panel = document.getElementById('notifications-panel');
            const overlay = document.getElementById('notifications-overlay');
            
            panel.classList.remove('translate-x-full');
            panel.classList.add('translate-x-0');
            overlay.classList.remove('hidden');
            
            // Marcar todas como lidas
            notificationsList.forEach(n => n.read = true);
            updateNotificationsList();
            updateNotificationsBadge();
        }
        
        // Fechar painel de notificações
        function closeNotificationsPanel() {
            const panel = document.getElementById('notifications-panel');
            const overlay = document.getElementById('notifications-overlay');
            
            panel.classList.remove('translate-x-0');
            panel.classList.add('translate-x-full');
            overlay.classList.add('hidden');
        }
        
        // Limpar todas as notificações
        function clearAllNotifications() {
            notificationsList = [];
            updateNotificationsList();
            updateNotificationsBadge();
            closeNotificationsPanel();
        }
        
        // Mostrar notificação
        function showNotification(newBookings) {
            const toast = document.getElementById('notification-toast');
            const message = document.getElementById('notification-message');
            const details = document.getElementById('notification-details');
            
            // Adicionar ao array de notificações
            newBookings.forEach(booking => addNotification(booking));
            
            // Atualizar conteúdo do toast
            if (newBookings.length === 1) {
                const booking = newBookings[0];
                message.textContent = `${booking.paciente_nome} agendou uma consulta!`;
                
                const dataFormatada = new Date(booking.data).toLocaleString('pt-BR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                details.innerHTML = `
                    <div><i class="fas fa-tooth mr-1"></i><strong>Serviço:</strong> ${booking.servico}</div>
                    <div><i class="fas fa-calendar mr-1"></i><strong>Data:</strong> ${dataFormatada}</div>
                    <div><i class="fas fa-phone mr-1"></i><strong>Telefone:</strong> ${booking.paciente_telefone}</div>
                `;
            } else {
                message.textContent = `${newBookings.length} novos agendamentos!`;
                details.innerHTML = `<div><i class="fas fa-info-circle mr-1"></i>Confira os detalhes na sua agenda.</div>`;
            }
            
            // Mostrar toast
            toast.classList.remove('translate-x-full');
            toast.classList.add('translate-x-0');
            
            // Tocar som
            if (notificationSound) {
                notificationSound();
            }
            
            // Auto-fechar toast após 30 segundos
            setTimeout(() => {
                closeNotification();
        }, 30000);
        }
        
        // Fechar notificação
        function closeNotification() {
            const toast = document.getElementById('notification-toast');
            toast.classList.remove('translate-x-0');
            toast.classList.add('translate-x-full');
        }
        
        // Atualizar estatísticas na tela
        function updateStats(stats) {
            // Atualizar total de agendamentos
            const allLabels = document.querySelectorAll('.text-gray-500');
            allLabels.forEach(label => {
                if (label.textContent.includes('Total de Agendamentos')) {
                    const valueElement = label.nextElementSibling;
                    if (valueElement && valueElement.classList.contains('text-2xl')) {
                        // Animar a mudança de número
                        valueElement.classList.add('transition-all', 'duration-300');
                        valueElement.style.transform = 'scale(1.2)';
                        valueElement.textContent = stats.total;
                        setTimeout(() => {
                            valueElement.style.transform = 'scale(1)';
                        }, 300);
                    }
                } else if (label.textContent.includes('Agendamentos Hoje')) {
                    const valueElement = label.nextElementSibling;
                    if (valueElement && valueElement.classList.contains('text-2xl')) {
                        // Animar a mudança de número
                        valueElement.classList.add('transition-all', 'duration-300');
                        valueElement.style.transform = 'scale(1.2)';
                        valueElement.textContent = stats.hoje;
                        setTimeout(() => {
                            valueElement.style.transform = 'scale(1)';
                        }, 300);
                    }
                }
            });
        }
        
        // Verificar novos agendamentos
        function checkNewBookings() {
            // Se for a primeira verificação, usar um timestamp antigo para não pegar agendamentos antigos
            const checkTime = lastCheckTime || '2000-01-01 00:00:00';
            
            fetch(`api/check_new_bookings.php?last_check=${encodeURIComponent(checkTime)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Se for a primeira verificação, apenas atualiza o timestamp
                        if (isFirstCheck) {
                            lastCheckTime = data.current_time;
                            isFirstCheck = false;
                            return; // NÃO MOSTRAR NOTIFICAÇÕES NA PRIMEIRA VEZ
                        }
                        
                        // Atualizar timestamp para próximas verificações
                        lastCheckTime = data.current_time;
                        
                        // Se houver novos agendamentos (e não for a primeira verificação)
                        if (data.has_new && data.new_count > 0) {
                            showNotification(data.new_bookings);
                            
                            // Atualizar estatísticas
                            updateStats(data.stats);
                            
                            // Recarregar calendário (recarregar página)
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        }
                    }
                })
                .catch(error => {
                    console.error('Erro ao verificar novos agendamentos:', error);
                });
        }
        
        // Verificar novos agendamentos a cada 5 segundos
        setInterval(() => {
            checkNewBookings();
        }, 5000);
        
        // Inicializar quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            
            // Criar som de notificação
            notificationSound = createNotificationSound();
            
            // Primeira verificação após 5 segundos
            setTimeout(() => {
                checkNewBookings();
            }, 5000);
        });
        
        // Toggle Sidebar para Mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
        
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</body>
</html>
