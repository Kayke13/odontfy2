<?php
session_start();
require_once 'auth.php';
require_once 'config/database.php';
require_once 'config/planos.php';
require_once 'config/plano_status.php';

Auth::requireLogin();

$dentista = Auth::getCurrentUser();

// Verificar status do plano e aplicar restrições
$status_plano = PlanoStatus::verificarStatus($dentista);

// Aplicar restrições se necessário
if (!$status_plano['pode_usar_sistema']) {
    PlanoStatus::aplicarRestricoes($dentista);
}

// Definir variáveis de status do plano
$assinatura_ativa = $status_plano['assinatura_ativa'] ?? false;
$em_teste = $status_plano['em_teste'] ?? false;
$assinatura_expirada = $status_plano['assinatura_expirada'] ?? false;
$teste_expirado = $status_plano['teste_expirado'] ?? false;
$plano_atual = $dentista['plano'] ?? 'teste';
$plano_fim = $dentista['plano_fim'] ?? null;
$teste_fim = $dentista['teste_fim'] ?? null;

$agendamentos = Database::get('agendamentos');
$agendamentos_dentista = array_filter($agendamentos, function($agendamento) use ($dentista) {
    return $agendamento['dentista_id'] === $dentista['id'];
});

// Ordenar por data
usort($agendamentos_dentista, function($a, $b) {
    return strtotime($b['data']) - strtotime($a['data']);
});

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $agendamento_id = $_POST['agendamento_id'];
    
    if ($action === 'cancelar') {
        Database::update('agendamentos', $agendamento_id, ['status' => 'cancelado']);
        header('Location: agendamentos.php');
        exit;
    } elseif ($action === 'confirmar') {
        Database::update('agendamentos', $agendamento_id, ['status' => 'confirmado']);
        header('Location: agendamentos.php');
        exit;
    }
}

// Calcular estatísticas
$total_agendamentos = count($agendamentos_dentista);
$pendentes = count(array_filter($agendamentos_dentista, function($a) { return $a['status'] === 'pendente'; }));
$confirmados = count(array_filter($agendamentos_dentista, function($a) { return $a['status'] === 'confirmado'; }));
$cancelados = count(array_filter($agendamentos_dentista, function($a) { return $a['status'] === 'cancelado'; }));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamentos - Odontfy</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/LOGO.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        body, h1, h2, h3, h4, h5, h6, p, a, button, input, textarea, select, label, span, div {
            font-family: 'Inter', sans-serif !important;
        }
        
        .fa, .fas, .far, .fal, .fab, [class^="fa-"], [class*=" fa-"] {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Pro", "Font Awesome 6 Brands" !important;
        }
        
        i.fa, i.fas, i.far, i.fal, i.fab {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Pro", "Font Awesome 6 Brands" !important;
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

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.5s ease-out;
        }

        .appointment-card {
            transition: all 0.3s ease;
        }

        .appointment-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(59, 130, 246, 0.1), 0 10px 10px -5px rgba(59, 130, 246, 0.04);
        }

        .stat-card {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3B82F6, #60A5FA);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(59, 130, 246, 0.1), 0 10px 10px -5px rgba(59, 130, 246, 0.04);
        }

        .filter-button {
            transition: all 0.3s ease;
        }

        .filter-button.active {
            background: linear-gradient(135deg, #3B82F6, #60A5FA);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 font-inter min-h-screen">
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>
    
    <aside id="sidebar" class="fixed top-0 left-0 z-50 h-screen w-64 bg-gradient-to-b from-blue-600 to-blue-800 shadow-2xl transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
        <div class="flex flex-col h-full">
            <div class="flex items-center justify-between p-6 border-b border-blue-500">
                <a href="dashboard.php" class="flex items-center space-x-3">
                    <i class="fas fa-tooth text-white text-3xl"></i>
                    <span class="text-2xl font-bold text-white">Odontfy</span>
                </a>
                <button onclick="toggleSidebar()" class="lg:hidden text-white hover:text-blue-200">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <div class="p-6 border-b border-blue-500">
                <div class="flex items-center space-x-3">
                    <?php require_once 'config/upload.php'; ?>
                    <img src="<?php echo Upload::getImageUrl($dentista['foto'] ?? '', 'dentista', $dentista['nome']); ?>" 
                         alt="Foto" 
                         class="w-12 h-12 rounded-full border-2 border-white object-cover">
                    <div class="flex-1 min-w-0">
                        <p class="text-white font-semibold truncate"><?php echo htmlspecialchars($dentista['nome']); ?></p>
                        <p class="text-blue-200 text-sm truncate"><?php echo htmlspecialchars($dentista['email']); ?></p>
                    </div>
                </div>
            </div>
            <nav class="flex-1 overflow-y-auto p-4 space-y-1">
                <a href="dashboard.php" class="menu-item flex items-center px-4 py-3 text-blue-100 hover:bg-blue-700 hover:text-white rounded-lg transition-all group">
                    <i class="fas fa-home w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Dashboard</span>
                </a>
                <a href="agendamentos.php" class="menu-item active flex items-center px-4 py-3 text-white bg-blue-700 rounded-lg hover:bg-blue-500 transition-all group">
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
            <div class="p-4 border-t border-blue-500">
                <a href="logout.php" class="flex items-center px-4 py-3 text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors">
                    <i class="fas fa-sign-out-alt w-5 text-center mr-3"></i>
                    <span>Sair</span>
                </a>
            </div>
        </div>
    </aside>
    
    <div class="lg:ml-64">
        <!-- Header -->
        <header class="bg-white shadow-lg sticky top-0 z-30 border-b-2 border-blue-100">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center space-x-4">
                        <button onclick="toggleSidebar()" class="lg:hidden text-gray-600 hover:text-blue-600 transition-colors">
                            <i class="fas fa-bars text-2xl"></i>
                        </button>
                        <div>
                            <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">
                                Agendamentos
                            </h1>
                            <p class="text-sm text-gray-500">Gerencie seus compromissos</p>
                        </div>
                    </div>
                    <div class="hidden sm:flex items-center bg-blue-50 px-4 py-2 rounded-lg">
                        <i class="fas fa-calendar-check text-blue-600 mr-2"></i>
                        <span class="text-sm font-semibold text-blue-900"><?php echo $total_agendamentos; ?> total</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Status da Assinatura -->
            <div class="mb-8 fade-in-up">
                <?php if ($assinatura_ativa && !$assinatura_expirada): ?>
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 rounded-xl p-6 shadow-md">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3 class="text-lg font-bold text-green-800">Assinatura Ativa</h3>
                                <p class="text-green-600">
                                    <?php 
                                    $plano_nome = PlanosConfig::getNome($plano_atual);
                                    echo $plano_nome . ' ativo';
                                    ?>
                                </p>
                                <?php if ($plano_fim): ?>
                                    <p class="text-sm text-green-500 mt-1">
                                        <i class="far fa-calendar mr-1"></i>Vence em: <?php echo date('d/m/Y', strtotime($plano_fim)); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php elseif ($em_teste): ?>
                    <div class="bg-gradient-to-r from-yellow-50 to-amber-50 border-l-4 border-yellow-500 rounded-xl p-6 shadow-md">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3 class="text-lg font-bold text-yellow-800">Período de Teste</h3>
                                <p class="text-yellow-600">Você está no período de teste de 7 dias</p>
                                <p class="text-sm text-yellow-500 mt-1">
                                    <i class="far fa-calendar mr-1"></i>Teste termina em: <?php echo date('d/m/Y H:i', strtotime($teste_fim)); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-500 rounded-xl p-6 shadow-md">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-bold text-red-800">Assinatura Inativa</h3>
                                    <p class="text-red-600">
                                        <?php if ($assinatura_expirada): ?>
                                            Sua assinatura expirou em <?php echo date('d/m/Y', strtotime($plano_fim)); ?>
                                        <?php elseif ($teste_expirado): ?>
                                            Seu período de teste expirou
                                        <?php else: ?>
                                            Sua assinatura não está ativa
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <a href="planos.php" class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-lg transition-all transform hover:scale-105">
                                    <i class="fas fa-crown mr-2"></i>Assinar Plano
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="stat-card bg-white p-6 rounded-2xl shadow-lg fade-in-up">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total</p>
                            <p class="text-3xl font-bold text-blue-600"><?php echo $total_agendamentos; ?></p>
                        </div>
                        <div class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-calendar text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-white p-6 rounded-2xl shadow-lg fade-in-up" style="animation-delay: 0.1s;">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Pendentes</p>
                            <p class="text-3xl font-bold text-yellow-600"><?php echo $pendentes; ?></p>
                        </div>
                        <div class="w-14 h-14 bg-yellow-100 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-white p-6 rounded-2xl shadow-lg fade-in-up" style="animation-delay: 0.2s;">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Confirmados</p>
                            <p class="text-3xl font-bold text-green-600"><?php echo $confirmados; ?></p>
                        </div>
                        <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-check text-green-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-white p-6 rounded-2xl shadow-lg fade-in-up" style="animation-delay: 0.3s;">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Cancelados</p>
                            <p class="text-3xl font-bold text-red-600"><?php echo $cancelados; ?></p>
                        </div>
                        <div class="w-14 h-14 bg-red-100 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-times text-red-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-8 fade-in-up" style="animation-delay: 0.4s;">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-filter text-blue-600"></i>
                        <h3 class="text-lg font-bold text-gray-900">Filtrar por:</h3>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button onclick="filterAppointments('todos')" class="filter-button active px-4 py-2 rounded-xl border-2 border-blue-200 font-semibold text-sm">
                            Todos
                        </button>
                        <button onclick="filterAppointments('pendente')" class="filter-button px-4 py-2 rounded-xl border-2 border-blue-200 text-gray-700 font-semibold text-sm">
                            Pendentes
                        </button>
                        <button onclick="filterAppointments('confirmado')" class="filter-button px-4 py-2 rounded-xl border-2 border-blue-200 text-gray-700 font-semibold text-sm">
                            Confirmados
                        </button>
                        <button onclick="filterAppointments('cancelado')" class="filter-button px-4 py-2 rounded-xl border-2 border-blue-200 text-gray-700 font-semibold text-sm">
                            Cancelados
                        </button>
                    </div>
                </div>
            </div>

            <!-- Appointments List -->
            <?php if (empty($agendamentos_dentista)): ?>
                <div class="bg-white rounded-2xl shadow-lg p-12 text-center fade-in-up" style="animation-delay: 0.5s;">
                    <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-calendar-times text-blue-600 text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Nenhum agendamento ainda</h3>
                    <p class="text-gray-500 mb-6">Quando seus pacientes agendarem consultas, elas aparecerão aqui.</p>
                    <a href="agendar.php?username=<?php echo $dentista['username']; ?>" target="_blank" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg transition-all transform hover:scale-105">
                        <i class="fas fa-external-link-alt mr-2"></i>Ver Página Pública
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-4 fade-in-up" style="animation-delay: 0.5s;">
                    <?php foreach ($agendamentos_dentista as $index => $agendamento): ?>
                        <div class="appointment-card bg-white rounded-2xl shadow-lg overflow-hidden appointment-item" data-status="<?php echo $agendamento['status']; ?>" style="animation-delay: <?php echo (0.5 + $index * 0.05); ?>s;">
                            <div class="p-6">
                                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                                    <!-- Patient Info -->
                                    <div class="flex items-center space-x-4 flex-1">
                                        <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center">
                                            <i class="fas fa-user text-blue-600 text-2xl"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-lg font-bold text-gray-900 truncate"><?php echo htmlspecialchars($agendamento['paciente_nome']); ?></h4>
                                            <p class="text-sm text-gray-600 flex items-center">
                                                <i class="fas fa-tooth text-blue-600 mr-2"></i>
                                                <?php echo htmlspecialchars($agendamento['servico']); ?>
                                            </p>
                                            <p class="text-xs text-gray-500 flex items-center mt-1">
                                                <i class="fas fa-user-md text-gray-400 mr-2"></i>
                                                <?php 
                                                if (isset($agendamento['dentista_escolhido']) && $agendamento['dentista_escolhido'] !== 'principal') {
                                                    $dentistas_file = 'data/dentistas_consultorio.json';
                                                    if (file_exists($dentistas_file)) {
                                                        $dentistas_consultorio = json_decode(file_get_contents($dentistas_file), true) ?: [];
                                                        foreach ($dentistas_consultorio as $d) {
                                                            if ($d['id'] === $agendamento['dentista_escolhido']) {
                                                                echo htmlspecialchars($d['nome']);
                                                                break;
                                                            }
                                                        }
                                                    }
                                                } else {
                                                    echo htmlspecialchars($dentista['nome']);
                                                }
                                                ?>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Date & Time -->
                                    <div class="flex items-center space-x-3 px-4 py-3 bg-blue-50 rounded-xl">
                                        <i class="far fa-calendar text-blue-600 text-xl"></i>
                                        <div>
                                            <p class="text-sm font-bold text-gray-900"><?php echo date('d/m/Y', strtotime($agendamento['data'])); ?></p>
                                            <p class="text-xs text-gray-600"><?php echo date('H:i', strtotime($agendamento['data'])); ?></p>
                                        </div>
                                    </div>

                                    <!-- Contact -->
                                    <div class="space-y-1">
                                        <p class="text-sm text-gray-900 flex items-center">
                                            <i class="fas fa-phone text-blue-600 mr-2"></i>
                                            <?php echo htmlspecialchars($agendamento['paciente_telefone']); ?>
                                        </p>
                                        <p class="text-xs text-gray-600 flex items-center">
                                            <i class="fas fa-envelope text-blue-600 mr-2"></i>
                                            <?php echo htmlspecialchars($agendamento['paciente_email']); ?>
                                        </p>
                                    </div>

                                    <!-- Status & Actions -->
                                    <div class="flex flex-col items-end space-y-3">
                                        <?php
                                        $status_classes = [
                                            'pendente' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                                            'confirmado' => 'bg-green-100 text-green-800 border-green-300',
                                            'cancelado' => 'bg-red-100 text-red-800 border-red-300'
                                        ];
                                        $status_class = $status_classes[$agendamento['status']] ?? 'bg-gray-100 text-gray-800 border-gray-300';
                                        ?>
                                        <span class="px-4 py-2 text-sm font-bold rounded-xl border-2 <?php echo $status_class; ?>">
                                            <?php echo ucfirst($agendamento['status']); ?>
                                        </span>

                                        <div class="flex flex-wrap gap-2">
                                            <?php if ($agendamento['status'] === 'pendente'): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="confirmar">
                                                    <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                    <button type="submit" class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all shadow-md hover:shadow-lg transform hover:scale-105">
                                                        <i class="fas fa-check mr-2"></i>Confirmar
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($agendamento['status'] !== 'cancelado'): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="cancelar">
                                                    <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                    <button type="submit" class="inline-flex items-center bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all shadow-md hover:shadow-lg transform hover:scale-105" 
                                                        onclick="return confirm('⚠️ Tem certeza que deseja cancelar este agendamento?')">
                                                        <i class="fas fa-times mr-2"></i>Cancelar
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Observations -->
                                <?php if (!empty($agendamento['observacoes'])): ?>
                                    <div class="mt-4 pt-4 border-t border-gray-200">
                                        <p class="text-sm text-gray-600 flex items-start">
                                            <i class="fas fa-comment-alt text-blue-600 mr-2 mt-0.5"></i>
                                            <span><strong>Observações:</strong> <?php echo htmlspecialchars($agendamento['observacoes']); ?></span>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    }
                }
            }
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        function filterAppointments(status) {
            // Update button styles
            document.querySelectorAll('.filter-button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');

            // Filter appointments
            const appointments = document.querySelectorAll('.appointment-item');
            appointments.forEach(appointment => {
                if (status === 'todos') {
                    appointment.style.display = 'block';
                } else {
                    if (appointment.dataset.status === status) {
                        appointment.style.display = 'block';
                    } else {
                        appointment.style.display = 'none';
                    }
                }
            });
        }
    </script>
</body>
</html>
