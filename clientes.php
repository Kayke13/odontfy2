<?php
session_start();
require_once 'auth.php';
require_once 'config/database.php';
require_once 'config/upload.php';

Auth::requireLogin();

$dentista = Auth::getCurrentUser();
$clientes = [];
$success = '';
$error = '';

// Buscar todos os agendamentos do dentista
$agendamentos = Database::get('agendamentos');
$agendamentos_dentista = array_filter($agendamentos, function($agendamento) use ($dentista) {
    return $agendamento['dentista_id'] === $dentista['id'];
});

// Carregar dados dos clientes
$clientes_file = 'data/clientes.json';
$clientes_data = [];
if (file_exists($clientes_file)) {
    $clientes_data = json_decode(file_get_contents($clientes_file), true) ?: [];
}

// Agrupar por cliente (email + telefone)
$clientes_agrupados = [];
foreach ($agendamentos_dentista as $agendamento) {
    $chave_cliente = $agendamento['paciente_email'] . '_' . $agendamento['paciente_telefone'];
    
    if (!isset($clientes_agrupados[$chave_cliente])) {
        // Buscar dados do cliente (se existir perfil)
        $cliente_perfil = $clientes_data[$chave_cliente] ?? [];
        
        $clientes_agrupados[$chave_cliente] = [
            'nome' => $agendamento['paciente_nome'],
            'email' => $agendamento['paciente_email'],
            'telefone' => $agendamento['paciente_telefone'],
            'foto' => $cliente_perfil['foto'] ?? '',
            'data_nascimento' => $cliente_perfil['data_nascimento'] ?? '',
            'genero' => $cliente_perfil['genero'] ?? '',
            'observacoes_medicas' => $cliente_perfil['observacoes_medicas'] ?? '',
            'agendamentos' => [],
            'total_agendamentos' => 0,
            'ultimo_agendamento' => null,
            'proximo_agendamento' => null
        ];
    }
    
    $clientes_agrupados[$chave_cliente]['agendamentos'][] = $agendamento;
    $clientes_agrupados[$chave_cliente]['total_agendamentos']++;
    
    // Verificar último e próximo agendamento
    $data_agendamento = strtotime($agendamento['data']);
    $hoje = time();
    
    if ($data_agendamento < $hoje) {
        if (!$clientes_agrupados[$chave_cliente]['ultimo_agendamento'] || 
            $data_agendamento > strtotime($clientes_agrupados[$chave_cliente]['ultimo_agendamento']['data'])) {
            $clientes_agrupados[$chave_cliente]['ultimo_agendamento'] = $agendamento;
        }
    } else {
        if (!$clientes_agrupados[$chave_cliente]['proximo_agendamento'] || 
            $data_agendamento < strtotime($clientes_agrupados[$chave_cliente]['proximo_agendamento']['data'])) {
            $clientes_agrupados[$chave_cliente]['proximo_agendamento'] = $agendamento;
        }
    }
}

// Converter para array e ordenar por nome
$clientes = array_values($clientes_agrupados);
usort($clientes, function($a, $b) {
    return strcmp($a['nome'], $b['nome']);
});

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'alterar_status') {
        $agendamento_id = $_POST['agendamento_id'] ?? '';
        $novo_status = $_POST['novo_status'] ?? '';
        
        if ($agendamento_id && $novo_status) {
            $result = Database::update('agendamentos', $agendamento_id, ['status' => $novo_status]);
            if ($result) {
                $success = 'Status do agendamento atualizado com sucesso!';
                // Recarregar página para atualizar dados
                header('Location: clientes.php');
                exit;
            } else {
                $error = 'Erro ao atualizar status do agendamento.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Clientes - Odontfy</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/LOGO.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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

        .client-card {
            transition: all 0.3s ease;
        }

        .client-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(59, 130, 246, 0.1), 0 10px 10px -5px rgba(59, 130, 246, 0.04);
        }

        .stat-card {
            position: relative;
            overflow: hidden;
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

        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 font-inter min-h-screen">
    <!-- Overlay para Mobile -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
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
                <a href="agendamentos.php" class="menu-item flex items-center px-4 py-3 text-blue-100 hover:bg-blue-700 hover:text-white rounded-lg transition-all group">
                    <i class="fas fa-calendar-alt w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Agendamentos</span>
                </a>
                <a href="clientes.php" class="menu-item active flex items-center px-4 py-3 text-white bg-blue-700 rounded-lg hover:bg-blue-500 transition-all group">
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
                                Meus Clientes
                            </h1>
                            <p class="text-sm text-gray-500">Gerencie seus pacientes</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="hidden sm:flex items-center bg-blue-50 px-4 py-2 rounded-lg">
                            <i class="fas fa-users text-blue-600 mr-2"></i>
                            <span class="text-sm font-semibold text-blue-900"><?php echo count($clientes); ?> clientes</span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="stat-card bg-white rounded-2xl shadow-lg p-6 fade-in-up">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total de Clientes</p>
                            <p class="text-3xl font-bold text-blue-600"><?php echo count($clientes); ?></p>
                        </div>
                        <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-users text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <?php
                $clientes_ativos = array_filter($clientes, function($c) {
                    return $c['proximo_agendamento'] !== null;
                });
                ?>
                <div class="stat-card bg-white rounded-2xl shadow-lg p-6 fade-in-up" style="animation-delay: 0.1s;">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Clientes Ativos</p>
                            <p class="text-3xl font-bold text-blue-600"><?php echo count($clientes_ativos); ?></p>
                        </div>
                        <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-user-check text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <?php
                $total_agendamentos = array_sum(array_column($clientes, 'total_agendamentos'));
                ?>
                <div class="stat-card bg-white rounded-2xl shadow-lg p-6 fade-in-up" style="animation-delay: 0.2s;">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total Agendamentos</p>
                            <p class="text-3xl font-bold text-blue-600"><?php echo $total_agendamentos; ?></p>
                        </div>
                        <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-calendar-check text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-8 fade-in-up" style="animation-delay: 0.3s;">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex-1 relative">
                        <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="text" 
                               id="searchInput" 
                               placeholder="Buscar por nome, email ou telefone..." 
                               class="search-input w-full pl-11 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none transition-all">
                    </div>
                    <div class="flex gap-2">
                        <select id="sortSelect" class="px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none transition-all">
                            <option value="nome">Ordenar por Nome</option>
                            <option value="agendamentos">Mais Agendamentos</option>
                            <option value="recente">Mais Recente</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($success): ?>
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-xl mb-6 shadow-md fade-in-up">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-2xl mr-3"></i>
                        <span class="font-medium"><?php echo $success; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-xl mb-6 shadow-md fade-in-up">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
                        <span class="font-medium"><?php echo $error; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Clients List -->
            <?php if (empty($clientes)): ?>
                <div class="bg-white rounded-2xl shadow-lg p-12 text-center fade-in-up">
                    <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-users text-blue-600 text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Nenhum cliente ainda</h3>
                    <p class="text-gray-500 mb-6">Seus clientes aparecerão aqui quando fizerem agendamentos.</p>
                    <a href="agendar.php?username=<?php echo $dentista['username']; ?>" 
                       class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-colors shadow-lg">
                        <i class="fas fa-calendar-plus mr-2"></i>
                        Ver Página de Agendamento
                    </a>
                </div>
            <?php else: ?>
                <div id="clientsList" class="space-y-6">
                    <?php foreach ($clientes as $index => $cliente): ?>
                        <div class="client-card bg-white rounded-2xl shadow-lg overflow-hidden fade-in-up" 
                             style="animation-delay: <?php echo ($index * 0.05); ?>s;"
                             data-client-name="<?php echo strtolower(htmlspecialchars($cliente['nome'])); ?>"
                             data-client-email="<?php echo strtolower(htmlspecialchars($cliente['email'])); ?>"
                             data-client-phone="<?php echo htmlspecialchars($cliente['telefone']); ?>"
                             data-client-appointments="<?php echo $cliente['total_agendamentos']; ?>"
                             data-client-last="<?php echo $cliente['ultimo_agendamento'] ? strtotime($cliente['ultimo_agendamento']['data']) : 0; ?>">
                            
                            <!-- Client Header -->
                            <div class="bg-gradient-to-r from-blue-50 to-blue-100 px-6 py-5 border-b-2 border-blue-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4 flex-1">
                                        <div class="relative">
                                            <img src="<?php echo Upload::getImageUrl($cliente['foto'] ?? '', 'cliente', $cliente['nome']); ?>" 
                                                 alt="Foto do cliente" 
                                                 class="w-16 h-16 rounded-full object-cover border-4 border-white shadow-lg">
                                            <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-blue-600 border-2 border-white rounded-full"></div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-xl font-bold text-gray-900 mb-1">
                                                <?php echo htmlspecialchars($cliente['nome']); ?>
                                            </h3>
                                            <div class="flex flex-wrap gap-3 text-sm text-gray-600">
                                                <a href="mailto:<?php echo htmlspecialchars($cliente['email']); ?>" 
                                                   class="flex items-center hover:text-blue-600 transition-colors">
                                                    <i class="fas fa-envelope mr-1.5"></i>
                                                    <span class="truncate"><?php echo htmlspecialchars($cliente['email']); ?></span>
                                                </a>
                                                <a href="tel:<?php echo htmlspecialchars($cliente['telefone']); ?>" 
                                                   class="flex items-center hover:text-blue-600 transition-colors">
                                                    <i class="fas fa-phone mr-1.5"></i>
                                                    <span><?php echo htmlspecialchars($cliente['telefone']); ?></span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="hidden md:flex gap-2">
                                        <a href="tel:<?php echo htmlspecialchars($cliente['telefone']); ?>" 
                                           class="w-12 h-12 bg-blue-600 hover:bg-blue-700 text-white rounded-xl flex items-center justify-center transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                                            <i class="fas fa-phone"></i>
                                        </a>
                                        <a href="mailto:<?php echo htmlspecialchars($cliente['email']); ?>" 
                                           class="w-12 h-12 bg-blue-600 hover:bg-blue-700 text-white rounded-xl flex items-center justify-center transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Client Stats -->
                            <div class="grid grid-cols-3 divide-x divide-gray-200 bg-white">
                                <div class="px-6 py-4 text-center">
                                    <div class="text-2xl font-bold text-blue-600 mb-1">
                                        <?php echo $cliente['total_agendamentos']; ?>
                                    </div>
                                    <div class="text-xs text-gray-600 font-medium">Agendamentos</div>
                                </div>
                                <div class="px-6 py-4 text-center">
                                    <div class="text-sm font-semibold text-gray-900 mb-1">
                                        <?php echo $cliente['ultimo_agendamento'] ? date('d/m/Y', strtotime($cliente['ultimo_agendamento']['data'])) : '-'; ?>
                                    </div>
                                    <div class="text-xs text-gray-600 font-medium">Último</div>
                                </div>
                                <div class="px-6 py-4 text-center">
                                    <div class="text-sm font-semibold text-gray-900 mb-1">
                                        <?php echo $cliente['proximo_agendamento'] ? date('d/m/Y', strtotime($cliente['proximo_agendamento']['data'])) : '-'; ?>
                                    </div>
                                    <div class="text-xs text-gray-600 font-medium">Próximo</div>
                                </div>
                            </div>

                            <!-- Client Appointments -->
                            <div class="px-6 py-5 bg-gray-50">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-sm font-bold text-gray-900 flex items-center">
                                        <i class="fas fa-history text-blue-600 mr-2"></i>
                                        Histórico de Agendamentos
                                    </h4>
                                    <button onclick="toggleHistory(<?php echo $index; ?>)" 
                                            class="text-blue-600 hover:text-blue-700 text-sm font-medium flex items-center">
                                        <span class="history-toggle-text-<?php echo $index; ?>">Ver todos</span>
                                        <i class="fas fa-chevron-down ml-1 transition-transform history-toggle-icon-<?php echo $index; ?>"></i>
                                    </button>
                                </div>
                                
                                <div id="history-<?php echo $index; ?>" class="space-y-3 max-h-48 overflow-y-auto">
                                    <?php 
                                    // Ordenar agendamentos por data (mais recente primeiro)
                                    $agendamentos_ordenados = $cliente['agendamentos'];
                                    usort($agendamentos_ordenados, function($a, $b) {
                                        return strtotime($b['data']) - strtotime($a['data']);
                                    });
                                    
                                    $limit = 3;
                                    foreach (array_slice($agendamentos_ordenados, 0, $limit) as $agendamento): 
                                    ?>
                                        <div class="bg-white rounded-xl p-4 border-2 border-gray-200 hover:border-blue-300 transition-all">
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <span class="px-3 py-1 text-xs font-semibold rounded-full <?php 
                                                            echo $agendamento['status'] === 'pendente' ? 'bg-yellow-100 text-yellow-800 border border-yellow-300' :
                                                            ($agendamento['status'] === 'confirmado' ? 'bg-green-100 text-green-800 border border-green-300' :
                                                            ($agendamento['status'] === 'cancelado' ? 'bg-red-100 text-red-800 border border-red-300' : 'bg-gray-100 text-gray-800 border border-gray-300'));
                                                        ?>">
                                                            <?php echo ucfirst($agendamento['status']); ?>
                                                        </span>
                                                    </div>
                                                    <div class="text-sm font-semibold text-gray-900 mb-1">
                                                        <?php echo htmlspecialchars($agendamento['servico']); ?>
                                                    </div>
                                                    <div class="flex items-center text-xs text-gray-600">
                                                        <i class="far fa-calendar mr-1.5"></i>
                                                        <?php echo date('d/m/Y', strtotime($agendamento['data'])); ?>
                                                        <i class="far fa-clock ml-3 mr-1.5"></i>
                                                        <?php echo date('H:i', strtotime($agendamento['data'])); ?>
                                                    </div>
                                                </div>
                                                
                                                <?php if ($agendamento['status'] === 'pendente'): ?>
                                                    <div class="ml-4">
                                                        <form method="POST" class="flex gap-2">
                                                            <input type="hidden" name="action" value="alterar_status">
                                                            <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                            <button type="submit" name="novo_status" value="confirmado" 
                                                                    class="px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm">
                                                                <i class="fas fa-check mr-1"></i>Confirmar
                                                            </button>
                                                            <button type="submit" name="novo_status" value="cancelado" 
                                                                    class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm">
                                                                <i class="fas fa-times mr-1"></i>Cancelar
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if (count($cliente['agendamentos']) > $limit): ?>
                                        <div class="history-more-<?php echo $index; ?> hidden">
                                            <?php foreach (array_slice($agendamentos_ordenados, $limit) as $agendamento): ?>
                                                <div class="bg-white rounded-xl p-4 border-2 border-gray-200 hover:border-blue-300 transition-all mb-3">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex-1">
                                                            <div class="flex items-center gap-2 mb-2">
                                                                <span class="px-3 py-1 text-xs font-semibold rounded-full <?php 
                                                                    echo $agendamento['status'] === 'pendente' ? 'bg-yellow-100 text-yellow-800 border border-yellow-300' :
                                                                    ($agendamento['status'] === 'confirmado' ? 'bg-green-100 text-green-800 border border-green-300' :
                                                                    ($agendamento['status'] === 'cancelado' ? 'bg-red-100 text-red-800 border border-red-300' : 'bg-gray-100 text-gray-800 border border-gray-300'));
                                                                ?>">
                                                                    <?php echo ucfirst($agendamento['status']); ?>
                                                                </span>
                                                            </div>
                                                            <div class="text-sm font-semibold text-gray-900 mb-1">
                                                                <?php echo htmlspecialchars($agendamento['servico']); ?>
                                                            </div>
                                                            <div class="flex items-center text-xs text-gray-600">
                                                                <i class="far fa-calendar mr-1.5"></i>
                                                                <?php echo date('d/m/Y', strtotime($agendamento['data'])); ?>
                                                                <i class="far fa-clock ml-3 mr-1.5"></i>
                                                                <?php echo date('H:i', strtotime($agendamento['data'])); ?>
                                                            </div>
                                                        </div>
                                                        
                                                        <?php if ($agendamento['status'] === 'pendente'): ?>
                                                            <div class="ml-4">
                                                                <form method="POST" class="flex gap-2">
                                                                    <input type="hidden" name="action" value="alterar_status">
                                                                    <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                                    <button type="submit" name="novo_status" value="confirmado" 
                                                                            class="px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm">
                                                                        <i class="fas fa-check mr-1"></i>Confirmar
                                                                    </button>
                                                                    <button type="submit" name="novo_status" value="cancelado" 
                                                                            class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-semibold rounded-lg transition-colors shadow-sm">
                                                                        <i class="fas fa-times mr-1"></i>Cancelar
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Mobile Action Buttons -->
                            <div class="md:hidden flex gap-2 p-4 bg-gray-50 border-t border-gray-200">
                                <a href="tel:<?php echo htmlspecialchars($cliente['telefone']); ?>" 
                                   class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-xl text-center font-semibold transition-colors shadow-lg">
                                    <i class="fas fa-phone mr-2"></i>Ligar
                                </a>
                                <a href="mailto:<?php echo htmlspecialchars($cliente['email']); ?>" 
                                   class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-xl text-center font-semibold transition-colors shadow-lg">
                                    <i class="fas fa-envelope mr-2"></i>Email
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        function toggleHistory(index) {
            const moreSection = document.querySelector(`.history-more-${index}`);
            const toggleText = document.querySelector(`.history-toggle-text-${index}`);
            const toggleIcon = document.querySelector(`.history-toggle-icon-${index}`);
            
            if (moreSection) {
                moreSection.classList.toggle('hidden');
                toggleIcon.classList.toggle('rotate-180');
                toggleText.textContent = moreSection.classList.contains('hidden') ? 'Ver todos' : 'Ver menos';
            }
        }

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const sortSelect = document.getElementById('sortSelect');
        const clientsList = document.getElementById('clientsList');

        if (searchInput) {
            searchInput.addEventListener('input', filterAndSortClients);
        }

        if (sortSelect) {
            sortSelect.addEventListener('change', filterAndSortClients);
        }

        function filterAndSortClients() {
            if (!clientsList) return;

            const searchTerm = searchInput.value.toLowerCase();
            const sortBy = sortSelect.value;
            const clients = Array.from(clientsList.querySelectorAll('.client-card'));

            // Filter clients
            clients.forEach(client => {
                const name = client.dataset.clientName || '';
                const email = client.dataset.clientEmail || '';
                const phone = client.dataset.clientPhone || '';

                const matches = name.includes(searchTerm) || 
                               email.includes(searchTerm) || 
                               phone.includes(searchTerm);

                if (matches) {
                    client.style.display = '';
                    client.classList.add('fade-in-up');
                } else {
                    client.style.display = 'none';
                }
            });

            // Sort visible clients
            const visibleClients = clients.filter(c => c.style.display !== 'none');
            
            visibleClients.sort((a, b) => {
                if (sortBy === 'nome') {
                    return a.dataset.clientName.localeCompare(b.dataset.clientName);
                } else if (sortBy === 'agendamentos') {
                    return parseInt(b.dataset.clientAppointments) - parseInt(a.dataset.clientAppointments);
                } else if (sortBy === 'recente') {
                    return parseInt(b.dataset.clientLast) - parseInt(a.dataset.clientLast);
                }
                return 0;
            });

            // Reorder DOM
            visibleClients.forEach(client => {
                clientsList.appendChild(client);
            });

            // Update animation delays
            visibleClients.forEach((client, index) => {
                client.style.animationDelay = `${index * 0.05}s`;
            });
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
