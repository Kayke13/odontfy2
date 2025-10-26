<?php
session_start();
require_once 'config/admin_auth.php';
require_once 'config/database.php';
require_once 'config/planos.php';

AdminAuth::requireLogin();

$admin_info = AdminAuth::getAdminInfo();

// Carregar configurações de planos
$configuracoes = PlanosConfig::carregar();
$planos_config = $configuracoes['planos'] ?? [];

// Buscar dados
$dentistas = Database::get('dentistas');
$agendamentos = Database::get('agendamentos');

// Calcular receita por período
function calcularReceitaPorPeriodo($dentistas, $planos_config, $periodo = 'total') {
    $receita = 0;
    $data_limite = null;
    
    switch ($periodo) {
        case 'hoje':
            $data_limite = strtotime('today');
            break;
        case 'semana':
            $data_limite = strtotime('-7 days');
            break;
        case 'mes':
            $data_limite = strtotime('-1 month');
            break;
        case 'ano':
            $data_limite = strtotime('-1 year');
            break;
    }
    
    foreach ($dentistas as $dentista) {
        $plano = $dentista['plano'] ?? 'teste';
        $preco = $planos_config[$plano]['preco'] ?? 0;
        
        if ($preco > 1.00) {
            $data_criacao = strtotime($dentista['created_at'] ?? 'now');
            
            // Se não há limite de data ou se o dentista foi criado após o limite
            if (!$data_limite || $data_criacao >= $data_limite) {
                $receita += $preco;
            }
        }
    }
    
    return $receita;
}

// Calcular receita por dia (últimos 30 dias)
function calcularReceitaPorDia($dentistas, $planos_config) {
    $receita_por_dia = [];
    
    for ($i = 29; $i >= 0; $i--) {
        $data = date('Y-m-d', strtotime("-$i days"));
        $receita_por_dia[$data] = 0;
    }
    
    foreach ($dentistas as $dentista) {
        $plano = $dentista['plano'] ?? 'teste';
        $preco = $planos_config[$plano]['preco'] ?? 0;
        
        if ($preco > 1.00) {
            $data_criacao = date('Y-m-d', strtotime($dentista['created_at'] ?? 'now'));
            if (isset($receita_por_dia[$data_criacao])) {
                $receita_por_dia[$data_criacao] += $preco;
            }
        }
    }
    
    return $receita_por_dia;
}

// Calcular receita por semana (últimas 12 semanas)
function calcularReceitaPorSemana($dentistas, $planos_config) {
    $receita_por_semana = [];
    
    for ($i = 11; $i >= 0; $i--) {
        $inicio_semana = date('Y-m-d', strtotime("-$i weeks monday"));
        $fim_semana = date('Y-m-d', strtotime("-$i weeks sunday"));
        $receita_por_semana[$inicio_semana] = 0;
    }
    
    foreach ($dentistas as $dentista) {
        $plano = $dentista['plano'] ?? 'teste';
        $preco = $planos_config[$plano]['preco'] ?? 0;
        
        if ($preco > 1.00) {
            $data_criacao = date('Y-m-d', strtotime($dentista['created_at'] ?? 'now'));
            
            // Encontrar a semana correspondente
            foreach ($receita_por_semana as $inicio_semana => $valor) {
                $fim_semana = date('Y-m-d', strtotime($inicio_semana . ' +6 days'));
                if ($data_criacao >= $inicio_semana && $data_criacao <= $fim_semana) {
                    $receita_por_semana[$inicio_semana] += $preco;
                    break;
                }
            }
        }
    }
    
    return $receita_por_semana;
}

// Calcular receita por mês (últimos 12 meses)
function calcularReceitaPorMes($dentistas, $planos_config) {
    $receita_por_mes = [];
    
    for ($i = 11; $i >= 0; $i--) {
        $mes = date('Y-m', strtotime("-$i months"));
        $receita_por_mes[$mes] = 0;
    }
    
    foreach ($dentistas as $dentista) {
        $plano = $dentista['plano'] ?? 'teste';
        $preco = $planos_config[$plano]['preco'] ?? 0;
        
        if ($preco > 1.00) {
            $data_criacao = date('Y-m', strtotime($dentista['created_at'] ?? 'now'));
            if (isset($receita_por_mes[$data_criacao])) {
                $receita_por_mes[$data_criacao] += $preco;
            }
        }
    }
    
    return $receita_por_mes;
}

// Calcular recorrência mensal por consultório
function calcularRecorrenciaMensal($dentistas, $planos_config) {
    $recorrencia_mensal = [];
    
    foreach ($dentistas as $dentista) {
        $plano = $dentista['plano'] ?? 'teste';
        $preco = $planos_config[$plano]['preco'] ?? 0;
        
        if ($preco > 1.00) {
            $nome_consultorio = $dentista['nome'] ?? 'Consultório sem nome';
            
            if (!isset($recorrencia_mensal[$nome_consultorio])) {
                $recorrencia_mensal[$nome_consultorio] = [
                    'nome' => $nome_consultorio,
                    'plano' => $plano,
                    'preco_mensal' => 0,
                    'preco_total' => $preco,
                    'duracao_dias' => $planos_config[$plano]['duracao_dias'] ?? 30
                ];
            }
            
            // Calcular valor mensal baseado na duração do plano
            $duracao_dias = $planos_config[$plano]['duracao_dias'] ?? 30;
            if ($duracao_dias > 0) {
                $recorrencia_mensal[$nome_consultorio]['preco_mensal'] = ($preco / $duracao_dias) * 30;
            }
        }
    }
    
    return $recorrencia_mensal;
}

// Calcular estatísticas
$receita_total = calcularReceitaPorPeriodo($dentistas, $planos_config, 'total');
$receita_hoje = calcularReceitaPorPeriodo($dentistas, $planos_config, 'hoje');
$receita_semana = calcularReceitaPorPeriodo($dentistas, $planos_config, 'semana');
$receita_mes = calcularReceitaPorPeriodo($dentistas, $planos_config, 'mes');
$receita_ano = calcularReceitaPorPeriodo($dentistas, $planos_config, 'ano');

$receita_por_dia = calcularReceitaPorDia($dentistas, $planos_config);
$receita_por_semana = calcularReceitaPorSemana($dentistas, $planos_config);
$receita_por_mes = calcularReceitaPorMes($dentistas, $planos_config);
$recorrencia_mensal = calcularRecorrenciaMensal($dentistas, $planos_config);

// Estatísticas por plano
$planos_stats = [];
foreach ($planos_config as $plano_key => $plano) {
    $planos_stats[$plano_key] = [
        'nome' => $plano['nome'],
        'preco' => $plano['preco'],
        'quantidade' => 0,
        'receita' => 0
    ];
}

foreach ($dentistas as $dentista) {
    $plano = $dentista['plano'] ?? 'teste';
    if (isset($planos_stats[$plano])) {
        $planos_stats[$plano]['quantidade']++;
        $planos_stats[$plano]['receita'] += $planos_stats[$plano]['preco'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Admin Odontfy</title>
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

        /* Card hover effect */
        .modern-card {
            transition: all 0.3s ease;
        }

        .modern-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="bg-gray-50 font-inter antialiased">
        <!-- Header Moderno -->
        <!-- Header Moderno -->
    <header class="bg-white shadow-md border-b-4 border-indigo-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-chart-line text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Relatórios e Análises</h1>
                        <p class="text-sm text-gray-500">Painel Administrativo</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="admin_dashboard.php" class="flex items-center space-x-2 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all duration-200">
                        <i class="fas fa-arrow-left"></i>
                        <span class="hidden sm:inline font-medium">Dashboard</span>
                    </a>
                    <a href="admin_logout.php" class="flex items-center space-x-2 px-4 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-200 shadow-md hover:shadow-lg">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="hidden sm:inline font-medium">Sair</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Abas -->
        <div class="mb-8">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button onclick="showTab('faturamento')" id="tab-faturamento" class="tab-button active py-2 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600">
                        <i class="fas fa-dollar-sign mr-2"></i>Faturamento
                    </button>
                    <button onclick="showTab('agendamentos')" id="tab-agendamentos" class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        <i class="fas fa-calendar-alt mr-2"></i>Agendamentos
                    </button>
                    <button onclick="showTab('clientes')" id="tab-clientes" class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        <i class="fas fa-users mr-2"></i>Clientes
                    </button>
                </nav>
            </div>
        </div>

        <!-- Conteúdo das Abas -->
        
        <!-- Aba Faturamento -->
        <div id="content-faturamento" class="tab-content">
            <!-- Resumo de Faturamento -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 mb-8">
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-calendar-day text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Hoje</p>
                            <p class="text-2xl font-semibold text-gray-900">R$ <?php echo number_format($receita_hoje, 2, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-calendar-week text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Esta Semana</p>
                            <p class="text-2xl font-semibold text-gray-900">R$ <?php echo number_format($receita_semana, 2, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-calendar text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Este Mês</p>
                            <p class="text-2xl font-semibold text-gray-900">R$ <?php echo number_format($receita_mes, 2, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <i class="fas fa-calendar-alt text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Este Ano</p>
                            <p class="text-2xl font-semibold text-gray-900">R$ <?php echo number_format($receita_ano, 2, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600">
                            <i class="fas fa-chart-line text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Geral</p>
                            <p class="text-2xl font-semibold text-gray-900">R$ <?php echo number_format($receita_total, 2, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos de Faturamento -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Receita por Dia -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Receita por Dia (Últimos 30 dias)</h3>
                    <canvas id="receitaDiaChart" width="400" height="200"></canvas>
                </div>

                <!-- Receita por Semana -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Receita por Semana (Últimas 12 semanas)</h3>
                    <canvas id="receitaSemanaChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Receita por Mês -->
            <div class="bg-white p-6 rounded-lg shadow mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Receita por Mês (Últimos 12 meses)</h3>
                <canvas id="receitaMesChart" width="400" height="200"></canvas>
            </div>

            <!-- Recorrência Mensal por Consultório -->
            <div class="bg-white rounded-lg shadow mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Recorrência Mensal por Consultório</h3>
                    <p class="text-sm text-gray-600 mt-1">Valor mensal estimado baseado no plano atual de cada consultório</p>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Consultório</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plano Atual</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Total Pago</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recorrência Mensal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duração</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php 
                                $total_recorrencia_mensal = 0;
                                foreach ($recorrencia_mensal as $consultorio): 
                                    $total_recorrencia_mensal += $consultorio['preco_mensal'];
                                ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($consultorio['nome']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($planos_config[$consultorio['plano']]['nome'] ?? $consultorio['plano']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            R$ <?php echo number_format($consultorio['preco_total'], 2, ',', '.'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                                            R$ <?php echo number_format($consultorio['preco_mensal'], 2, ',', '.'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $consultorio['duracao_dias']; ?> dias
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="3" class="px-6 py-3 text-sm font-medium text-gray-900 text-right">
                                        <strong>Total de Recorrência Mensal:</strong>
                                    </td>
                                    <td class="px-6 py-3 text-sm font-bold text-green-600">
                                        R$ <?php echo number_format($total_recorrencia_mensal, 2, ',', '.'); ?>
                                    </td>
                                    <td class="px-6 py-3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Detalhamento por Plano -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Detalhamento por Plano</h3>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plano</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preço</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantidade</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receita Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($planos_stats as $plano_key => $stats): ?>
                                    <?php if ($stats['preco'] > 1.00): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($stats['nome']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                R$ <?php echo number_format($stats['preco'], 2, ',', '.'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $stats['quantidade']; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                R$ <?php echo number_format($stats['receita'], 2, ',', '.'); ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aba Agendamentos -->
        <div id="content-agendamentos" class="tab-content hidden">
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Relatórios de Agendamentos</h3>
                <p class="text-gray-600">Em desenvolvimento...</p>
            </div>
        </div>

        <!-- Aba Clientes -->
        <div id="content-clientes" class="tab-content hidden">
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Relatórios de Clientes</h3>
                <p class="text-gray-600">Em desenvolvimento...</p>
            </div>
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

        // Função para alternar abas
        function showTab(tabName) {
            // Esconder todos os conteúdos
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remover classe active de todas as abas
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active', 'border-blue-500', 'text-blue-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Mostrar conteúdo da aba selecionada
            document.getElementById('content-' + tabName).classList.remove('hidden');
            
            // Ativar aba selecionada
            const activeTab = document.getElementById('tab-' + tabName);
            activeTab.classList.add('active', 'border-blue-500', 'text-blue-600');
            activeTab.classList.remove('border-transparent', 'text-gray-500');
        }

        // Dados para os gráficos
        const receitaPorDia = <?php echo json_encode(array_values($receita_por_dia)); ?>;
        const dias = <?php echo json_encode(array_keys($receita_por_dia)); ?>;
        
        const receitaPorSemana = <?php echo json_encode(array_values($receita_por_semana)); ?>;
        const semanas = <?php echo json_encode(array_keys($receita_por_semana)); ?>;
        
        const receitaPorMes = <?php echo json_encode(array_values($receita_por_mes)); ?>;
        const meses = <?php echo json_encode(array_keys($receita_por_mes)); ?>;

        // Gráfico de Receita por Dia
        const ctxDia = document.getElementById('receitaDiaChart').getContext('2d');
        new Chart(ctxDia, {
            type: 'line',
            data: {
                labels: dias.map(dia => {
                    const [ano, mes, diaNum] = dia.split('-');
                    return diaNum + '/' + mes;
                }),
                datasets: [{
                    label: 'Receita (R$)',
                    data: receitaPorDia,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de Receita por Semana
        const ctxSemana = document.getElementById('receitaSemanaChart').getContext('2d');
        new Chart(ctxSemana, {
            type: 'bar',
            data: {
                labels: semanas.map(semana => {
                    const [ano, mes, dia] = semana.split('-');
                    return dia + '/' + mes;
                }),
                datasets: [{
                    label: 'Receita (R$)',
                    data: receitaPorSemana,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de Receita por Mês
        const ctxMes = document.getElementById('receitaMesChart').getContext('2d');
        new Chart(ctxMes, {
            type: 'bar',
            data: {
                labels: meses.map(mes => {
                    const [ano, mesNum] = mes.split('-');
                    const mesesNomes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
                    return mesesNomes[parseInt(mesNum) - 1] + '/' + ano;
                }),
                datasets: [{
                    label: 'Receita (R$)',
                    data: receitaPorMes,
                    backgroundColor: 'rgba(168, 85, 247, 0.8)',
                    borderColor: 'rgb(168, 85, 247)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });
    </script>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center">
                <p class="text-gray-600 text-sm">© 2025 Odontfy. Todos os direitos reservados.</p>
                <p class="text-gray-500 text-sm mt-2 sm:mt-0">Dashboard Administrativo v2.0</p>
            </div>
        </div>
    </footer>
</body>
</html>
