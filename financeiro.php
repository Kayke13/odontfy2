<?php
session_start();
require_once 'auth.php';
require_once 'config/database.php';
require_once 'config/plano_status.php';

Auth::requireLogin();

$dentista = Auth::getCurrentUser();

// Verificar status do plano e aplicar restrições
$status_plano = PlanoStatus::verificarStatus($dentista);

// Verificar se pode usar controle financeiro
if (!PlanoStatus::podeUsarFuncionalidade($dentista, 'controle_financeiro')) {
    header('Location: planos.php?recurso=controle_financeiro');
    exit;
}
$success = '';
$error = '';

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

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'adicionar_transacao') {
        $tipo = $_POST['tipo'] ?? '';
        $categoria = $_POST['categoria'] ?? '';
        $descricao = trim($_POST['descricao'] ?? '');
        $valor = (float)($_POST['valor'] ?? 0);
        $data = $_POST['data'] ?? date('Y-m-d');
        
        if ($tipo && $categoria && $descricao && $valor > 0) {
            $nova_transacao = [
                'id' => uniqid(),
                'dentista_id' => $dentista['id'],
                'tipo' => $tipo, // 'entrada' ou 'saida'
                'categoria' => $categoria,
                'descricao' => $descricao,
                'valor' => $valor,
                'data' => $data,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $financeiro_data[] = $nova_transacao;
            
            if (file_put_contents($financeiro_file, json_encode($financeiro_data, JSON_PRETTY_PRINT))) {
                $success = 'Transação adicionada com sucesso!';
                $dentista_financeiro = array_filter($financeiro_data, function($item) use ($dentista) {
                    return $item['dentista_id'] === $dentista['id'];
                });
            } else {
                $error = 'Erro ao salvar transação.';
            }
        } else {
            $error = 'Por favor, preencha todos os campos obrigatórios.';
        }
    } elseif ($action === 'excluir_transacao') {
        $transacao_id = $_POST['transacao_id'] ?? '';
        
        if ($transacao_id) {
            $financeiro_data = array_filter($financeiro_data, function($item) use ($transacao_id) {
                return $item['id'] !== $transacao_id;
            });
            
            if (file_put_contents($financeiro_file, json_encode($financeiro_data, JSON_PRETTY_PRINT))) {
                $success = 'Transação excluída com sucesso!';
                $dentista_financeiro = array_filter($financeiro_data, function($item) use ($dentista) {
                    return $item['dentista_id'] === $dentista['id'];
                });
            } else {
                $error = 'Erro ao excluir transação.';
            }
        }
    }
}

// Ordenar por data (mais recente primeiro)
usort($dentista_financeiro, function($a, $b) {
    return strtotime($b['data']) - strtotime($a['data']);
});

// Calcular totais
$total_entradas = 0;
$total_saidas = 0;
$saldo_atual = 0;

foreach ($dentista_financeiro as $transacao) {
    if ($transacao['tipo'] === 'entrada') {
        $total_entradas += $transacao['valor'];
    } else {
        $total_saidas += $transacao['valor'];
    }
}

$saldo_atual = $total_entradas - $total_saidas;

// Categorias predefinidas
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle Financeiro - Odontfy</title>
        <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
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
                <a href="financeiro.php" class="menu-item active flex items-center px-4 py-3 text-white bg-blue-700 rounded-lg hover:bg-blue-500 transition-all group">
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
        <nav class="bg-white shadow-lg sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <button onclick="toggleSidebar()" class="lg:hidden mr-4 text-gray-600 hover:text-gray-900">
                            <i class="fas fa-bars text-2xl"></i>
                        </button>
                        <h1 class="text-xl font-bold text-gray-900">Controle Financeiro</h1>
                    </div>
                </div>
            </div>
        </nav>
        <div class="p-4 sm:p-6 lg:p-8">

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Resumo Financeiro -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-arrow-up text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total de Entradas</p>
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
                        <p class="text-sm font-medium text-gray-500">Total de Saídas</p>
                        <p class="text-2xl font-semibold text-gray-900">R$ <?php echo number_format($total_saidas, 2, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full <?php echo $saldo_atual >= 0 ? 'bg-blue-100 text-blue-600' : 'bg-red-100 text-red-600'; ?>">
                        <i class="fas fa-balance-scale text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Saldo Atual</p>
                        <p class="text-2xl font-semibold text-gray-900">R$ <?php echo number_format($saldo_atual, 2, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Adicionar Transação -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-plus mr-2"></i>Nova Transação
                    </h3>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="adicionar_transacao">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                            <select name="tipo" required id="tipo_transacao"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecione o tipo</option>
                                <option value="entrada">Entrada</option>
                                <option value="saida">Saída</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Categoria</label>
                            <select name="categoria" required id="categoria_transacao"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecione a categoria</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                            <input type="text" name="descricao" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Ex: Consulta de limpeza">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Valor (R$)</label>
                            <input type="number" name="valor" required step="0.01" min="0.01"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="0,00">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data</label>
                            <input type="date" name="data" required
                                value="<?php echo date('Y-m-d'); ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md font-medium">
                            <i class="fas fa-save mr-2"></i>Adicionar Transação
                        </button>
                    </form>
                </div>
            </div>

            <!-- Lista de Transações -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">
                            <i class="fas fa-list mr-2"></i>Histórico de Transações
                        </h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <?php if (empty($dentista_financeiro)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-chart-line text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500">Nenhuma transação registrada ainda.</p>
                                <p class="text-sm text-gray-400">Adicione sua primeira transação ao lado.</p>
                            </div>
                        <?php else: ?>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($dentista_financeiro as $transacao): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo date('d/m/Y', strtotime($transacao['data'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $transacao['tipo'] === 'entrada' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                    <?php echo ucfirst($transacao['tipo']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php 
                                                $categorias = $transacao['tipo'] === 'entrada' ? $categorias_entrada : $categorias_saida;
                                                echo $categorias[$transacao['categoria']] ?? $transacao['categoria'];
                                                ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                <?php echo htmlspecialchars($transacao['descricao']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo $transacao['tipo'] === 'entrada' ? 'text-green-600' : 'text-red-600'; ?>">
                                                <?php echo $transacao['tipo'] === 'entrada' ? '+' : '-'; ?>R$ <?php echo number_format($transacao['valor'], 2, ',', '.'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir esta transação?')">
                                                    <input type="hidden" name="action" value="excluir_transacao">
                                                    <input type="hidden" name="transacao_id" value="<?php echo $transacao['id']; ?>">
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        <i class="fas fa-trash mr-1"></i>Excluir
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>

    <script>
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

        // Categorias por tipo
        const categoriasEntrada = <?php echo json_encode($categorias_entrada); ?>;
        const categoriasSaida = <?php echo json_encode($categorias_saida); ?>;

        // Atualizar categorias baseado no tipo selecionado
        document.getElementById('tipo_transacao').addEventListener('change', function() {
            const tipo = this.value;
            const categoriaSelect = document.getElementById('categoria_transacao');
            
            // Limpar opções
            categoriaSelect.innerHTML = '<option value="">Selecione a categoria</option>';
            
            // Adicionar categorias baseado no tipo
            const categorias = tipo === 'entrada' ? categoriasEntrada : categoriasSaida;
            
            for (const [key, value] of Object.entries(categorias)) {
                const option = document.createElement('option');
                option.value = key;
                option.textContent = value;
                categoriaSelect.appendChild(option);
            }
        });
    </script>
</body>
</html>
