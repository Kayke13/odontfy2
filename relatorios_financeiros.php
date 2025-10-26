<?php
session_start();
require_once 'auth.php';
require_once 'config/database.php';

Auth::requireLogin();

$dentista = Auth::getCurrentUser();

// Carregar dados financeiros
$financeiro_file = 'data/financeiro.json';
$financeiro_data = [];
if (file_exists($financeiro_file)) {
    $financeiro_data = json_decode(file_get_contents($financeiro_file), true) ?: [];
}

// Filtrar dados do dentista atual
$dentista_financeiro = array_filter($financeiro_data, function($item) use ($dentista) {
    return $item['dentista_id'] === $dentista['id'];
});

// Filtros
$periodo = $_GET['periodo'] ?? 'mes_atual';
$categoria_filtro = $_GET['categoria'] ?? '';

// Aplicar filtros
$dados_filtrados = $dentista_financeiro;

if ($periodo !== 'todos') {
    $hoje = new DateTime();
    $inicio = clone $hoje;
    
    switch ($periodo) {
        case 'mes_atual':
            $inicio->modify('first day of this month');
            break;
        case 'mes_anterior':
            $inicio->modify('first day of last month');
            $hoje->modify('last day of last month');
            break;
        case 'ultimos_3_meses':
            $inicio->modify('-3 months');
            break;
        case 'ultimos_6_meses':
            $inicio->modify('-6 months');
            break;
        case 'ano_atual':
            $inicio->modify('first day of January this year');
            break;
    }
    
    $dados_filtrados = array_filter($dados_filtrados, function($item) use ($inicio, $hoje) {
        $data_transacao = new DateTime($item['data']);
        return $data_transacao >= $inicio && $data_transacao <= $hoje;
    });
}

if ($categoria_filtro) {
    $dados_filtrados = array_filter($dados_filtrados, function($item) use ($categoria_filtro) {
        return $item['categoria'] === $categoria_filtro;
    });
}

// Calcular estatísticas
$total_entradas = 0;
$total_saidas = 0;
$saldo_periodo = 0;
$total_transacoes = count($dados_filtrados);

$entradas_por_categoria = [];
$saidas_por_categoria = [];

foreach ($dados_filtrados as $transacao) {
    if ($transacao['tipo'] === 'entrada') {
        $total_entradas += $transacao['valor'];
        $entradas_por_categoria[$transacao['categoria']] = ($entradas_por_categoria[$transacao['categoria']] ?? 0) + $transacao['valor'];
    } else {
        $total_saidas += $transacao['valor'];
        $saidas_por_categoria[$transacao['categoria']] = ($saidas_por_categoria[$transacao['categoria']] ?? 0) + $transacao['valor'];
    }
}

$saldo_periodo = $total_entradas - $total_saidas;

// Categorias
$categorias_entrada = [
    'consulta' => 'Consultas',
    'procedimento' => 'Procedimentos',
    'tratamento' => 'Tratamentos',
    'limpeza' => 'Limpezas',
    'ortodontia' => 'Ortodontia',
    'implantodontia' => 'Implantodontia',
    'outros' => 'Outros'
];

$categorias_saida = [
    'aluguel' => 'Aluguel',
    'salarios' => 'Salários',
    'equipamentos' => 'Equipamentos',
    'material' => 'Material Odontológico',
    'marketing' => 'Marketing',
    'utilitarios' => 'Utilidades',
    'manutencao' => 'Manutenção',
    'outros' => 'Outros'
];

// Dados para gráfico mensal (últimos 6 meses)
$dados_mensais = [];
for ($i = 5; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i months"));
    $dados_mensais[$mes] = ['entradas' => 0, 'saidas' => 0];
}

foreach ($dentista_financeiro as $transacao) {
    $mes_transacao = date('Y-m', strtotime($transacao['data']));
    if (isset($dados_mensais[$mes_transacao])) {
        if ($transacao['tipo'] === 'entrada') {
            $dados_mensais[$mes_transacao]['entradas'] += $transacao['valor'];
        } else {
            $dados_mensais[$mes_transacao]['saidas'] += $transacao['valor'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios Financeiros - Odontfy</title>
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
    </style>
</head>
<body class="bg-gray-50 font-inter">
    <!-- Header -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <a href="dashboard.php" class="text-2xl font-bold text-blue-600 hover:text-blue-700 transition-colors duration-200">
                            <i class="fas fa-tooth mr-2"></i>Odontfy
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="financeiro.php" class="text-gray-700 hover:text-blue-600">
                        <i class="fas fa-arrow-left mr-2"></i>Voltar ao Financeiro
                    </a>
                    <span class="text-gray-700">Olá, <?php echo htmlspecialchars($dentista['nome']); ?>!</span>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium">
                        Sair
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-chart-bar mr-2 text-purple-600"></i>
                Relatórios Financeiros
            </h1>
            <p class="text-gray-600 mt-2">Análise detalhada das finanças do consultório</p>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Filtros</h3>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Período</label>
                    <select name="periodo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="mes_atual" <?php echo $periodo === 'mes_atual' ? 'selected' : ''; ?>>Mês Atual</option>
                        <option value="mes_anterior" <?php echo $periodo === 'mes_anterior' ? 'selected' : ''; ?>>Mês Anterior</option>
                        <option value="ultimos_3_meses" <?php echo $periodo === 'ultimos_3_meses' ? 'selected' : ''; ?>>Últimos 3 Meses</option>
                        <option value="ultimos_6_meses" <?php echo $periodo === 'ultimos_6_meses' ? 'selected' : ''; ?>>Últimos 6 Meses</option>
                        <option value="ano_atual" <?php echo $periodo === 'ano_atual' ? 'selected' : ''; ?>>Ano Atual</option>
                        <option value="todos" <?php echo $periodo === 'todos' ? 'selected' : ''; ?>>Todos</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Categoria</label>
                    <select name="categoria" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todas as categorias</option>
                        <?php foreach (array_merge($categorias_entrada, $categorias_saida) as $key => $value): ?>
                            <option value="<?php echo $key; ?>" <?php echo $categoria_filtro === $key ? 'selected' : ''; ?>>
                                <?php echo $value; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md font-medium">
                        <i class="fas fa-filter mr-2"></i>Aplicar Filtros
                    </button>
                </div>
            </form>
        </div>

        <!-- Resumo do Período -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-arrow-up text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Entradas</p>
                        <p class="text-2xl font-semibold text-gray-900">R$ <?php echo number_format($total_entradas, 2, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <i class="fas fa-arrow-down text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Saídas</p>
                        <p class="text-2xl font-semibold text-gray-900">R$ <?php echo number_format($total_saidas, 2, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full <?php echo $saldo_periodo >= 0 ? 'bg-blue-100 text-blue-600' : 'bg-red-100 text-red-600'; ?>">
                        <i class="fas fa-balance-scale text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Saldo</p>
                        <p class="text-2xl font-semibold text-gray-900">R$ <?php echo number_format($saldo_periodo, 2, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-list text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Transações</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $total_transacoes; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Gráfico de Evolução Mensal -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Evolução Mensal (Últimos 6 Meses)</h3>
                <canvas id="evolucaoChart" width="400" height="200"></canvas>
            </div>

            <!-- Distribuição por Categoria - Entradas -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Entradas por Categoria</h3>
                <div class="space-y-3">
                    <?php if (empty($entradas_por_categoria)): ?>
                        <p class="text-gray-500 text-center py-4">Nenhuma entrada no período</p>
                    <?php else: ?>
                        <?php foreach ($entradas_por_categoria as $categoria => $valor): ?>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-4 h-4 bg-green-500 rounded mr-3"></div>
                                    <span class="text-sm font-medium text-gray-700">
                                        <?php echo $categorias_entrada[$categoria] ?? $categoria; ?>
                                    </span>
                                </div>
                                <span class="text-sm font-bold text-gray-900">R$ <?php echo number_format($valor, 2, ',', '.'); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Distribuição por Categoria - Saídas -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Saídas por Categoria</h3>
                <div class="space-y-3">
                    <?php if (empty($saidas_por_categoria)): ?>
                        <p class="text-gray-500 text-center py-4">Nenhuma saída no período</p>
                    <?php else: ?>
                        <?php foreach ($saidas_por_categoria as $categoria => $valor): ?>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-4 h-4 bg-red-500 rounded mr-3"></div>
                                    <span class="text-sm font-medium text-gray-700">
                                        <?php echo $categorias_saida[$categoria] ?? $categoria; ?>
                                    </span>
                                </div>
                                <span class="text-sm font-bold text-gray-900">R$ <?php echo number_format($valor, 2, ',', '.'); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top 5 Transações -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Maiores Transações do Período</h3>
                <div class="space-y-3">
                    <?php 
                    $transacoes_ordenadas = $dados_filtrados;
                    usort($transacoes_ordenadas, function($a, $b) {
                        return $b['valor'] - $a['valor'];
                    });
                    $top_transacoes = array_slice($transacoes_ordenadas, 0, 5);
                    ?>
                    
                    <?php if (empty($top_transacoes)): ?>
                        <p class="text-gray-500 text-center py-4">Nenhuma transação no período</p>
                    <?php else: ?>
                        <?php foreach ($top_transacoes as $transacao): ?>
                            <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-md">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($transacao['descricao']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo date('d/m/Y', strtotime($transacao['data'])); ?></p>
                                </div>
                                <span class="text-sm font-bold <?php echo $transacao['tipo'] === 'entrada' ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $transacao['tipo'] === 'entrada' ? '+' : '-'; ?>R$ <?php echo number_format($transacao['valor'], 2, ',', '.'); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
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

        // Gráfico de evolução mensal
        const ctx = document.getElementById('evolucaoChart').getContext('2d');
        const dadosMensais = <?php echo json_encode($dados_mensais); ?>;
        
        const meses = Object.keys(dadosMensais).map(mes => {
            const [ano, mesNum] = mes.split('-');
            const mesesNomes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
            return mesesNomes[parseInt(mesNum) - 1] + '/' + ano;
        });
        
        const entradas = Object.values(dadosMensais).map(dados => dados.entradas);
        const saidas = Object.values(dadosMensais).map(dados => dados.saidas);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: meses,
                datasets: [{
                    label: 'Entradas',
                    data: entradas,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.1
                }, {
                    label: 'Saídas',
                    data: saidas,
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
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
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': R$ ' + context.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
