<?php
session_start();
require_once 'config/admin_auth.php';
require_once 'config/database.php';
require_once 'config/planos.php';
require_once 'config/plano_status.php';

AdminAuth::requireLogin();

$admin_info = AdminAuth::getAdminInfo();

// Carregar configurações de planos
$configuracoes = PlanosConfig::carregar();
$planos_config = $configuracoes['planos'] ?? [];

// Estatísticas gerais
$dentistas = Database::get('dentistas');
$agendamentos = Database::get('agendamentos');
$clientes = [];

// Contar clientes únicos
$emails_unicos = [];
foreach ($agendamentos as $agendamento) {
    $chave = $agendamento['paciente_email'] . '_' . $agendamento['paciente_telefone'];
    if (!in_array($chave, $emails_unicos)) {
        $emails_unicos[] = $chave;
    }
}
$total_clientes = count($emails_unicos);

// Estatísticas por plano - usar configuração centralizada
$planos_stats = [];
foreach ($planos_config as $plano_key => $plano) {
    $planos_stats[$plano_key] = 0;
}

// Adicionar contador para planos especiais
$planos_stats['teste'] = 0;
$planos_stats['expirado'] = 0;

foreach ($dentistas as $dentista) {
    $plano = $dentista['plano'] ?? 'teste';
    if (!isset($planos_stats[$plano])) {
        $planos_stats[$plano] = 0;
    }
    $planos_stats[$plano]++;
}

// Receita total recebida - apenas planos com preço acima de R$ 1,00
$receita_total = 0;
foreach ($planos_stats as $plano_key => $quantidade) {
    $preco = $planos_config[$plano_key]['preco'] ?? 0;
    if ($preco > 1.00) {
        // Valor total recebido (não mensalizado)
        $receita_total += $quantidade * $preco;
    }
}

// Agendamentos por mês (últimos 6 meses)
$agendamentos_por_mes = [];
for ($i = 5; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i months"));
    $agendamentos_por_mes[$mes] = 0;
}

foreach ($agendamentos as $agendamento) {
    $mes_agendamento = date('Y-m', strtotime($agendamento['data']));
    if (isset($agendamentos_por_mes[$mes_agendamento])) {
        $agendamentos_por_mes[$mes_agendamento]++;
    }
}

// Análise de usuários no período de teste usando a nova classe PlanoStatus
$usuarios_teste = [];
$usuarios_teste_ativos = [];
$usuarios_teste_expirados = [];
$usuarios_teste_por_dia = [];

foreach ($dentistas as $dentista) {
    $status_plano = PlanoStatus::verificarStatus($dentista);
    
    // Considerar usuários em período de teste ou com plano teste
    if ($status_plano['status_geral'] === 'teste' || $status_plano['status_geral'] === 'expirado') {
        $dentista_info = [
            'id' => $dentista['id'],
            'nome' => $dentista['nome'],
            'clinica' => $dentista['clinica'],
            'email' => $dentista['email'],
            'telefone' => $dentista['telefone'],
            'teste_inicio' => $dentista['teste_inicio'] ?? date('Y-m-d', strtotime($dentista['created_at'])),
            'teste_fim' => $dentista['teste_fim'] ?? date('Y-m-d', strtotime($dentista['created_at'] . ' +7 days')),
            'dias_restantes' => $status_plano['dias_restantes'],
            'dias_expirados' => $status_plano['dias_expirados'],
            'status' => $status_plano['status_geral'],
            'plano' => $status_plano['plano_atual']
        ];
        
        if ($status_plano['status_geral'] === 'teste') {
            $usuarios_teste_ativos[] = $dentista_info;
        } else {
            $usuarios_teste_expirados[] = $dentista_info;
        }
        
        $usuarios_teste[] = $dentista_info;
        
        // Agrupar por dia de cadastro para gráfico
        $dia_cadastro = date('Y-m-d', strtotime($dentista['created_at']));
        if (!isset($usuarios_teste_por_dia[$dia_cadastro])) {
            $usuarios_teste_por_dia[$dia_cadastro] = 0;
        }
        $usuarios_teste_por_dia[$dia_cadastro]++;
    }
}

// Ordenar por data de início do teste
usort($usuarios_teste_ativos, function($a, $b) {
    return strtotime($a['teste_inicio']) - strtotime($b['teste_inicio']);
});

usort($usuarios_teste_expirados, function($a, $b) {
    return strtotime($b['teste_inicio']) - strtotime($a['teste_inicio']);
});
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrativo - Odontfy</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/LOGO.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }

        .animate-slide-in {
            animation: slideIn 0.6s ease-out forwards;
        }

        /* Hover effects para cards */
        .stat-card {
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Estilos personalizados para gráficos */
        .chart-container {
            position: relative;
            height: 300px;
        }

        /* Botões com efeito */
        .action-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .action-card:hover {
            transform: translateX(5px);
            border-left-color: #1570EF;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* Badge animado */
        @keyframes pulse-subtle {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .badge-pulse {
            animation: pulse-subtle 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gray-50 font-inter antialiased">
    <!-- Header Moderno -->
    <header class="bg-white shadow-md border-b-4 border-blue-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Painel Administrativo</h1>
                        <p class="text-sm text-gray-500">Gerenciamento do Sistema</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="hidden md:flex items-center space-x-3 px-4 py-2 bg-gray-50 rounded-lg">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user-shield text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($admin_info['username']); ?></p>
                            <p class="text-xs text-gray-500">Administrador</p>
                        </div>
                    </div>
                    <a href="admin_logout.php" class="flex items-center space-x-2 px-4 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-200 shadow-md hover:shadow-lg">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="hidden sm:inline font-medium">Sair</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Título da Seção -->
        <div class="mb-8 animate-fade-in">
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Visão Geral</h2>
            <p class="text-gray-600">Estatísticas e métricas do sistema</p>
        </div>

        <!-- Estatísticas Principais -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6 mb-8">
            <!-- Card Dentistas -->
            <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100 animate-fade-in">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-user-md text-blue-600 text-2xl"></i>
                    </div>
                    <span class="bg-blue-50 text-blue-700 text-xs font-semibold px-3 py-1 rounded-full">Total</span>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 mb-1"><?php echo count($dentistas); ?></h3>
                <p class="text-sm font-medium text-gray-500">Dentistas Cadastrados</p>
            </div>

            <!-- Card Clientes -->
            <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100 animate-fade-in" style="animation-delay: 0.1s">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-users text-green-600 text-2xl"></i>
                    </div>
                    <span class="bg-green-50 text-green-700 text-xs font-semibold px-3 py-1 rounded-full">Únicos</span>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 mb-1"><?php echo $total_clientes; ?></h3>
                <p class="text-sm font-medium text-gray-500">Clientes Ativos</p>
            </div>

            <!-- Card Agendamentos -->
            <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100 animate-fade-in" style="animation-delay: 0.2s">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-calendar-check text-purple-600 text-2xl"></i>
                    </div>
                    <span class="bg-purple-50 text-purple-700 text-xs font-semibold px-3 py-1 rounded-full">Total</span>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 mb-1"><?php echo count($agendamentos); ?></h3>
                <p class="text-sm font-medium text-gray-500">Agendamentos</p>
            </div>

            <!-- Card Receita -->
            <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100 animate-fade-in" style="animation-delay: 0.3s">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-yellow-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-yellow-600 text-2xl"></i>
                    </div>
                    <span class="bg-yellow-50 text-yellow-700 text-xs font-semibold px-3 py-1 rounded-full">MRR</span>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 mb-1">R$ <?php echo number_format($receita_total, 0, ',', '.'); ?></h3>
                <p class="text-sm font-medium text-gray-500">Receita Total</p>
            </div>

            <!-- Card Planos Expirados -->
            <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border border-red-100 animate-fade-in" style="animation-delay: 0.4s">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-red-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                    </div>
                    <?php if ($planos_stats['expirado'] > 0): ?>
                        <span class="bg-red-50 text-red-700 text-xs font-semibold px-3 py-1 rounded-full badge-pulse">Ação</span>
                    <?php else: ?>
                        <span class="bg-gray-50 text-gray-600 text-xs font-semibold px-3 py-1 rounded-full">OK</span>
                    <?php endif; ?>
                </div>
                <h3 class="text-3xl font-bold <?php echo $planos_stats['expirado'] > 0 ? 'text-red-600' : 'text-gray-900'; ?> mb-1">
                    <?php echo $planos_stats['expirado']; ?>
                </h3>
                <p class="text-sm font-medium text-gray-500">Planos Expirados</p>
            </div>
        </div>

        <!-- Estatísticas de Período de Teste -->
        <div class="mb-8 animate-fade-in">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Período de Teste</h2>
            <p class="text-gray-600">Acompanhamento de usuários em avaliação</p>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
            <!-- Card Teste Ativo -->
            <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-l-4 border-orange-500 animate-fade-in">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-clock text-orange-600 text-xl"></i>
                    </div>
                    <span class="badge-pulse bg-orange-100 text-orange-700 text-xs font-bold px-3 py-1 rounded-full">ATIVO</span>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 mb-1"><?php echo count($usuarios_teste_ativos); ?></h3>
                <p class="text-sm font-medium text-gray-600">Em Período de Teste</p>
            </div>

            <!-- Card Teste Expirado -->
            <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-l-4 border-red-500 animate-fade-in" style="animation-delay: 0.1s">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                    <span class="bg-red-100 text-red-700 text-xs font-bold px-3 py-1 rounded-full">EXPIRADO</span>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 mb-1"><?php echo count($usuarios_teste_expirados); ?></h3>
                <p class="text-sm font-medium text-gray-600">Testes Expirados</p>
            </div>

            <!-- Card Total Testes -->
            <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-l-4 border-indigo-500 animate-fade-in" style="animation-delay: 0.2s">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-indigo-600 text-xl"></i>
                    </div>
                    <span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-3 py-1 rounded-full">TOTAL</span>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 mb-1"><?php echo count($usuarios_teste); ?></h3>
                <p class="text-sm font-medium text-gray-600">Total de Testes</p>
            </div>
        </div>

        <!-- Consultórios com Planos Expirados -->
        <?php if ($planos_stats['expirado'] > 0): ?>
        <div class="mb-8 animate-fade-in">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">⚠️ Consultórios com Planos Expirados</h2>
                    <p class="text-gray-600">Consultórios que precisam renovar a assinatura</p>
                </div>
                <a href="admin_planos.php" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all shadow-lg hover:shadow-xl">
                    <i class="fas fa-tools mr-2"></i>Gerenciar Planos
                </a>
            </div>

            <div class="bg-white rounded-2xl shadow-xl overflow-hidden border-2 border-red-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-red-500 to-pink-500">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Clínica</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Dentista</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Email</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Telefone</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Plano Anterior</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Data Expiração</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php 
                            foreach ($dentistas as $dentista): 
                                if ($dentista['plano'] !== 'expirado') continue;
                                $data_expiracao = $dentista['plano_fim'] ?? $dentista['teste_fim'] ?? 'N/A';
                                $plano_anterior = 'Desconhecido';
                                if ($dentista['plano_fim']) {
                                    $plano_anterior = 'Plano Pago';
                                } elseif ($dentista['teste_fim']) {
                                    $plano_anterior = 'Teste Gratuito';
                                }
                            ?>
                            <tr class="hover:bg-red-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-3">
                                            <i class="fas fa-clinic-medical text-red-600"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($dentista['clinica']); ?></div>
                                            <div class="text-xs text-gray-500">ID: <?php echo substr($dentista['id'], 0, 8); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($dentista['nome']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-600"><?php echo htmlspecialchars($dentista['email']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-600"><?php echo htmlspecialchars($dentista['telefone'] ?? 'N/A'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 text-xs font-semibold bg-gray-100 text-gray-700 rounded-full">
                                        <?php echo $plano_anterior; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($data_expiracao !== 'N/A'): ?>
                                    <div class="text-sm font-medium text-red-600">
                                        <i class="fas fa-calendar-times mr-1"></i>
                                        <?php echo date('d/m/Y', strtotime($data_expiracao)); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php 
                                        $dias = ceil((time() - strtotime($data_expiracao)) / (24 * 60 * 60));
                                        echo "Há $dias dia" . ($dias > 1 ? 's' : '');
                                        ?>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-sm text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="admin_planos.php?dentista=<?php echo $dentista['id']; ?>" 
                                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-all shadow hover:shadow-lg">
                                        <i class="fas fa-edit mr-2"></i>Reativar
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Gráficos e Análises -->
        <div class="mb-8 animate-fade-in">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Análises e Gráficos</h2>
            <p class="text-gray-600">Visualização de dados e tendências</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Gráfico de Agendamentos -->
            <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100 animate-slide-in">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Agendamentos</h3>
                        <p class="text-sm text-gray-500 mt-1">Últimos 6 meses</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="agendamentosChart"></canvas>
                </div>
            </div>

            <!-- Distribuição de Planos -->
            <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100 animate-slide-in" style="animation-delay: 0.1s">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Distribuição de Planos</h3>
                        <p class="text-sm text-gray-500 mt-1">Assinaturas ativas</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-pie-chart text-purple-600 text-xl"></i>
                    </div>
                </div>
                <div class="space-y-4">
                    <?php 
                    $cores = [
                        'yellow' => ['bg' => 'bg-yellow-500', 'text' => 'text-yellow-700', 'light' => 'bg-yellow-50'],
                        'blue' => ['bg' => 'bg-blue-500', 'text' => 'text-blue-700', 'light' => 'bg-blue-50'],
                        'green' => ['bg' => 'bg-green-500', 'text' => 'text-green-700', 'light' => 'bg-green-50'],
                        'purple' => ['bg' => 'bg-purple-500', 'text' => 'text-purple-700', 'light' => 'bg-purple-50'],
                        'red' => ['bg' => 'bg-red-500', 'text' => 'text-red-700', 'light' => 'bg-red-50'],
                        'indigo' => ['bg' => 'bg-indigo-500', 'text' => 'text-indigo-700', 'light' => 'bg-indigo-50']
                    ];
                    $cor_keys = array_keys($cores);
                    $cor_index = 0;
                    $total_planos = array_sum($planos_stats);
                    
                    foreach ($planos_config as $plano_key => $plano): 
                        $cor_key = $cor_keys[$cor_index % count($cor_keys)];
                        $cor = $cores[$cor_key];
                        $cor_index++;
                        $quantidade = $planos_stats[$plano_key];
                        $percentual = $total_planos > 0 ? ($quantidade / $total_planos) * 100 : 0;
                    ?>
                        <div class="flex items-center justify-between p-4 rounded-xl <?php echo $cor['light']; ?> hover:shadow-md transition-all duration-200">
                            <div class="flex items-center space-x-4 flex-1">
                                <div class="w-3 h-3 <?php echo $cor['bg']; ?> rounded-full"></div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($plano['nome']); ?></p>
                                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                                        <div class="<?php echo $cor['bg']; ?> h-2 rounded-full transition-all duration-300" style="width: <?php echo $percentual; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="ml-4 text-right">
                                <p class="text-2xl font-bold <?php echo $cor['text']; ?>"><?php echo $quantidade; ?></p>
                                <p class="text-xs text-gray-500"><?php echo number_format($percentual, 1); ?>%</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Usuários em Período de Teste -->
        <div class="mb-8 animate-fade-in">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Gerenciamento de Usuários</h2>
            <p class="text-gray-600">Usuários em período de avaliação</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 sm:p-8 mb-8 animate-fade-in">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users-cog text-orange-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Usuários em Teste</h3>
                        <p class="text-sm text-gray-500">Acompanhamento detalhado</p>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <button onclick="toggleSection('teste-ativos')" class="px-4 py-2.5 text-sm font-medium bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-all duration-200 shadow-md">
                        <i class="fas fa-check-circle mr-2"></i>Ativos (<?php echo count($usuarios_teste_ativos); ?>)
                    </button>
                    <button onclick="toggleSection('teste-expirados')" class="px-4 py-2.5 text-sm font-medium bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all duration-200">
                        <i class="fas fa-times-circle mr-2"></i>Expirados (<?php echo count($usuarios_teste_expirados); ?>)
                    </button>
                </div>
            </div>

            <!-- Usuários Ativos no Teste -->
            <div id="teste-ativos" class="mb-6">
                <h4 class="text-md font-medium text-gray-800 mb-4">
                    <i class="fas fa-check-circle mr-2 text-green-600"></i>Usuários Ativos no Período de Teste
                </h4>
                <?php if (empty($usuarios_teste_ativos)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-clock text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Nenhum usuário ativo no período de teste</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dentista/Clínica</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contato</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plano</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Início do Teste</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fim do Teste</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dias Restantes</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($usuarios_teste_ativos as $usuario): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($usuario['nome']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($usuario['clinica']); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($usuario['email']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($usuario['telefone']); ?></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800">
                                                <?php 
                                                $planos_nomes = [
                                                    'teste' => 'Teste',
                                                    'mensal' => 'Mensal',
                                                    'anual' => 'Anual',
                                                    'semestral' => 'Semestral'
                                                ];
                                                echo $planos_nomes[$usuario['plano']] ?? ucfirst($usuario['plano']);
                                                ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('d/m/Y', strtotime($usuario['teste_inicio'])); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('d/m/Y', strtotime($usuario['teste_fim'])); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $usuario['dias_restantes'] <= 2 ? 'bg-red-100 text-red-800' : ($usuario['dias_restantes'] <= 5 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                                                <?php echo $usuario['dias_restantes']; ?> dias
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="admin_dentistas.php" class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fas fa-edit mr-1"></i>Gerenciar
                                            </a>
                                            <a href="mailto:<?php echo htmlspecialchars($usuario['email']); ?>" class="text-green-600 hover:text-green-900">
                                                <i class="fas fa-envelope mr-1"></i>Contatar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Usuários com Teste Expirado -->
            <div id="teste-expirados" class="hidden">
                <h4 class="text-md font-medium text-gray-800 mb-4">
                    <i class="fas fa-exclamation-triangle mr-2 text-red-600"></i>Usuários com Período de Teste Expirado
                </h4>
                <?php if (empty($usuarios_teste_expirados)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-check-circle text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Nenhum usuário com teste expirado</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dentista/Clínica</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contato</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plano</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Início do Teste</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fim do Teste</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dias Expirados</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($usuarios_teste_expirados as $usuario): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($usuario['nome']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($usuario['clinica']); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($usuario['email']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($usuario['telefone']); ?></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                                <?php 
                                                $planos_nomes = [
                                                    'teste' => 'Teste',
                                                    'mensal' => 'Mensal',
                                                    'anual' => 'Anual',
                                                    'semestral' => 'Semestral'
                                                ];
                                                echo $planos_nomes[$usuario['plano']] ?? ucfirst($usuario['plano']);
                                                ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('d/m/Y', strtotime($usuario['teste_inicio'])); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('d/m/Y', strtotime($usuario['teste_fim'])); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                                <?php echo $usuario['dias_expirados']; ?> dias
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="admin_dentistas.php" class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fas fa-edit mr-1"></i>Gerenciar
                                            </a>
                                            <a href="mailto:<?php echo htmlspecialchars($usuario['email']); ?>" class="text-green-600 hover:text-green-900">
                                                <i class="fas fa-envelope mr-1"></i>Contatar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ações Administrativas -->
        <div class="mb-8 animate-fade-in">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Ações Rápidas</h2>
            <p class="text-gray-600">Acesso rápido às principais funcionalidades</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <a href="admin_dentistas.php" class="action-card bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100">
                <div class="flex items-start space-x-4">
                    <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-user-md text-blue-600 text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Gerenciar Dentistas</h3>
                        <p class="text-sm text-gray-600 mb-3">Visualizar e editar consultórios cadastrados</p>
                        <div class="flex items-center text-blue-600 text-sm font-medium">
                            <span>Acessar</span>
                            <i class="fas fa-arrow-right ml-2"></i>
                        </div>
                    </div>
                </div>
            </a>

            <a href="admin_planos.php" class="action-card bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100">
                <div class="flex items-start space-x-4">
                    <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-credit-card text-green-600 text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Gerenciar Planos</h3>
                        <p class="text-sm text-gray-600 mb-3">Configurar preços e planos de assinatura</p>
                        <div class="flex items-center text-green-600 text-sm font-medium">
                            <span>Acessar</span>
                            <i class="fas fa-arrow-right ml-2"></i>
                        </div>
                    </div>
                </div>
            </a>

            <a href="admin_agendamentos.php" class="action-card bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100">
                <div class="flex items-start space-x-4">
                    <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-calendar-alt text-purple-600 text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Agendamentos</h3>
                        <p class="text-sm text-gray-600 mb-3">Visualizar todos os agendamentos do sistema</p>
                        <div class="flex items-center text-purple-600 text-sm font-medium">
                            <span>Acessar</span>
                            <i class="fas fa-arrow-right ml-2"></i>
                        </div>
                    </div>
                </div>
            </a>

            <a href="admin_clientes.php" class="action-card bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100">
                <div class="flex items-start space-x-4">
                    <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-users text-orange-600 text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Clientes</h3>
                        <p class="text-sm text-gray-600 mb-3">Visualizar todos os clientes cadastrados</p>
                        <div class="flex items-center text-orange-600 text-sm font-medium">
                            <span>Acessar</span>
                            <i class="fas fa-arrow-right ml-2"></i>
                        </div>
                    </div>
                </div>
            </a>

            <a href="admin_configuracoes.php" class="action-card bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100">
                <div class="flex items-start space-x-4">
                    <div class="w-14 h-14 bg-gray-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-cog text-gray-600 text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Configurações</h3>
                        <p class="text-sm text-gray-600 mb-3">Configurações gerais do sistema</p>
                        <div class="flex items-center text-gray-600 text-sm font-medium">
                            <span>Acessar</span>
                            <i class="fas fa-arrow-right ml-2"></i>
                        </div>
                    </div>
                </div>
            </a>

            <a href="admin_relatorios.php" class="action-card bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100">
                <div class="flex items-start space-x-4">
                    <div class="w-14 h-14 bg-indigo-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-chart-bar text-indigo-600 text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Relatórios</h3>
                        <p class="text-sm text-gray-600 mb-3">Relatórios detalhados do sistema</p>
                        <div class="flex items-center text-indigo-600 text-sm font-medium">
                            <span>Acessar</span>
                            <i class="fas fa-arrow-right ml-2"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center">
                <p class="text-gray-600 text-sm">© 2025 Odontfy. Todos os direitos reservados.</p>
                <p class="text-gray-500 text-sm mt-2 sm:mt-0">Dashboard Administrativo v2.0</p>
            </div>
        </div>
    </footer>

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

        // Função para alternar entre seções de usuários em teste
        function toggleSection(sectionId) {
            // Ocultar todas as seções
            document.getElementById('teste-ativos').classList.add('hidden');
            document.getElementById('teste-expirados').classList.add('hidden');
            
            // Mostrar a seção selecionada
            document.getElementById(sectionId).classList.remove('hidden');
            
            // Atualizar estilos dos botões
            const buttons = document.querySelectorAll('[onclick^="toggleSection"]');
            buttons.forEach(btn => {
                btn.classList.remove('bg-orange-500', 'bg-red-500', 'text-white', 'shadow-md');
                btn.classList.add('bg-gray-100', 'text-gray-700');
            });
            
            // Destacar botão ativo
            const activeButton = document.querySelector(`[onclick="toggleSection('${sectionId}')"]`);
            if (activeButton) {
                activeButton.classList.remove('bg-gray-100', 'text-gray-700');
                if (sectionId === 'teste-ativos') {
                    activeButton.classList.add('bg-orange-500', 'text-white', 'shadow-md');
                } else {
                    activeButton.classList.add('bg-red-500', 'text-white', 'shadow-md');
                }
            }
        }

        // Gráfico de agendamentos moderno
        const ctx = document.getElementById('agendamentosChart').getContext('2d');
        const agendamentosData = <?php echo json_encode(array_values($agendamentos_por_mes)); ?>;
        const meses = <?php echo json_encode(array_keys($agendamentos_por_mes)); ?>;
        
        // Criar gradiente para o gráfico
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: meses.map(mes => {
                    const [ano, mesNum] = mes.split('-');
                    const mesesNomes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
                    return mesesNomes[parseInt(mesNum) - 1];
                }),
                datasets: [{
                    label: 'Agendamentos',
                    data: agendamentosData,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgb(59, 130, 246)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointHoverBackgroundColor: 'rgb(59, 130, 246)',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleColor: '#fff',
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyColor: '#fff',
                        bodyFont: {
                            size: 13
                        },
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return 'Agendamentos: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#6B7280',
                            font: {
                                size: 12
                            },
                            padding: 10
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            color: '#6B7280',
                            font: {
                                size: 12
                            },
                            padding: 10
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    </script>
</body>
</html>
