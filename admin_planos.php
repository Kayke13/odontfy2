<?php
session_start();
require_once 'config/admin_auth.php';
require_once 'config/database.php';
require_once 'config/planos.php';

AdminAuth::requireLogin();

$success = '';
$error = '';

// Carregar configurações de planos do sistema centralizado
$configuracoes = PlanosConfig::carregar();
$planos_config = $configuracoes['planos'] ?? [];

// Adicionar plano de teste se não existir
if (!isset($planos_config['teste'])) {
    $planos_config['teste'] = [
        'nome' => 'Período de Teste',
        'preco' => 0,
        'duracao_dias' => 7,
        'recursos' => ['Agendamentos ilimitados', 'Até 5 serviços', 'Suporte básico']
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_planos') {
        // Atualizar configurações centralizadas
        $configuracoes['planos'] = [];
        
        foreach (['teste', 'mensal', 'semestral', 'anual'] as $plano_key) {
            if (isset($planos_config[$plano_key])) {
                $configuracoes['planos'][$plano_key] = [
                    'nome' => $_POST[$plano_key . '_nome'] ?? $planos_config[$plano_key]['nome'],
                    'preco' => (float)($_POST[$plano_key . '_preco'] ?? $planos_config[$plano_key]['preco']),
                    'duracao_dias' => (int)($_POST[$plano_key . '_duracao'] ?? $planos_config[$plano_key]['duracao_dias']),
                    'recursos' => $planos_config[$plano_key]['recursos'],
                    'descricao' => $_POST[$plano_key . '_descricao'] ?? ($planos_config[$plano_key]['descricao'] ?? '')
                ];
            }
        }
        
        // Salvar no arquivo centralizado
        $config_file = 'data/configuracoes.json';
        if (file_put_contents($config_file, json_encode($configuracoes, JSON_PRETTY_PRINT))) {
            $success = 'Configurações de planos atualizadas com sucesso!';
            $planos_config = $configuracoes['planos'];
        } else {
            $error = 'Erro ao salvar configurações.';
        }
    } elseif ($action === 'alterar_plano_dentista') {
        $dentista_id = $_POST['dentista_id'] ?? '';
        $novo_plano = $_POST['novo_plano'] ?? '';
        $data_fim = $_POST['data_fim'] ?? '';
        
        if ($dentista_id && $novo_plano) {
            $update_data = ['plano' => $novo_plano];
            
            if ($novo_plano === 'teste') {
                // Plano de teste
                if (!$data_fim) {
                    $data_fim = date('Y-m-d', strtotime('+7 days'));
                }
                $update_data['teste_fim'] = $data_fim;
                $update_data['assinatura_ativa'] = false;
                $update_data['plano_fim'] = null;
            } elseif ($novo_plano === 'expirado') {
                // Plano expirado - bloquear acesso
                $update_data['assinatura_ativa'] = false;
                $update_data['plano_fim'] = null;
                $update_data['teste_fim'] = null;
            } else {
                // Planos pagos (mensal, semestral, anual)
                if (!$data_fim) {
                    $dias = PlanosConfig::getDuracaoDias($novo_plano);
                    $data_fim = date('Y-m-d', strtotime("+{$dias} days"));
                }
                
                $update_data['plano_fim'] = $data_fim;
                $update_data['assinatura_ativa'] = true;
                // Limpar dados de teste quando ativar plano pago
                $update_data['teste_inicio'] = null;
                $update_data['teste_fim'] = null;
            }
            
            $result = Database::update('dentistas', $dentista_id, $update_data);
            
            if ($result) {
                $success = 'Plano do dentista alterado com sucesso!';
            } else {
                $error = 'Erro ao alterar plano do dentista.';
            }
        } else {
            $error = 'Por favor, preencha todos os campos.';
        }
    }
}

// Buscar dentistas para alteração de plano
$dentistas = Database::get('dentistas');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Planos - Admin Odontfy</title>
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
        .plan-card {
            transition: all 0.3s ease;
        }

        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Input focus */
        input:focus, select:focus {
            outline: none;
            border-color: #3B82F6;
            ring: 2px;
            ring-color: rgba(59, 130, 246, 0.3);
        }
    </style>
</head>
<body class="bg-gray-50 font-inter antialiased">
    <!-- Header Moderno -->
    <header class="bg-white shadow-md border-b-4 border-green-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-credit-card text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Gerenciar Planos</h1>
                        <p class="text-sm text-gray-500">Configurações de assinaturas</p>
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
        <?php if ($success): ?>
            <div class="animate-fade-in bg-green-50 border-l-4 border-green-500 rounded-lg p-4 mb-6 shadow-md">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800"><?php echo $success; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="animate-fade-in bg-red-50 border-l-4 border-red-500 rounded-lg p-4 mb-6 shadow-md">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-600 text-2xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800"><?php echo $error; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Configuração de Planos -->
        <div class="mb-8 animate-fade-in">
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Configuração de Planos</h2>
            <p class="text-gray-600">Gerencie preços, durações e recursos dos planos de assinatura</p>
        </div>

        <form method="POST" class="space-y-6 mb-8">
            <input type="hidden" name="action" value="update_planos">
            
            <?php 
            $cores_planos = [
                'teste' => ['bg' => 'bg-yellow-100', 'border' => 'border-yellow-500', 'icon' => 'text-yellow-600', 'badge' => 'bg-yellow-500'],
                'mensal' => ['bg' => 'bg-blue-100', 'border' => 'border-blue-500', 'icon' => 'text-blue-600', 'badge' => 'bg-blue-500'],
                'semestral' => ['bg' => 'bg-green-100', 'border' => 'border-green-500', 'icon' => 'text-green-600', 'badge' => 'bg-green-500'],
                'anual' => ['bg' => 'bg-purple-100', 'border' => 'border-purple-500', 'icon' => 'text-purple-600', 'badge' => 'bg-purple-500']
            ];
            $index = 0;
            foreach ($planos_config as $plano_key => $plano): 
                $cores = $cores_planos[$plano_key] ?? ['bg' => 'bg-gray-100', 'border' => 'border-gray-500', 'icon' => 'text-gray-600', 'badge' => 'bg-gray-500'];
            ?>
                <div class="plan-card bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden animate-fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s">
                    <!-- Header do Card -->
                    <div class="<?php echo $cores['bg']; ?> border-l-4 <?php echo $cores['border']; ?> p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="w-14 h-14 bg-white rounded-xl flex items-center justify-center shadow-md">
                                    <i class="fas fa-tag <?php echo $cores['icon']; ?> text-2xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($plano['nome']); ?></h3>
                                    <p class="text-sm text-gray-600 mt-1">R$ <?php echo number_format($plano['preco'], 2, ',', '.'); ?> por <?php echo $plano['duracao_dias']; ?> dias</p>
                                </div>
                            </div>
                            <span class="<?php echo $cores['badge']; ?> text-white text-xs font-bold px-4 py-2 rounded-full uppercase">
                                <?php echo $plano_key; ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Conteúdo do Card -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-signature mr-2 text-gray-400"></i>Nome do Plano
                                </label>
                                <input type="text" name="<?php echo $plano_key; ?>_nome" 
                                    value="<?php echo htmlspecialchars($plano['nome']); ?>"
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-dollar-sign mr-2 text-gray-400"></i>Preço (R$)
                                </label>
                                <input type="number" name="<?php echo $plano_key; ?>_preco" 
                                    value="<?php echo $plano['preco']; ?>" step="0.01" min="0"
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-calendar-alt mr-2 text-gray-400"></i>Duração (dias)
                                </label>
                                <input type="number" name="<?php echo $plano_key; ?>_duracao" 
                                    value="<?php echo $plano['duracao_dias']; ?>" min="1"
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 rounded-xl p-4">
                            <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-list-check mr-2 text-gray-600"></i>
                                Recursos Incluídos
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <?php foreach ($plano['recursos'] as $recurso): ?>
                                    <div class="flex items-center space-x-2 text-sm text-gray-700">
                                        <i class="fas fa-check-circle text-green-500"></i>
                                        <span><?php echo htmlspecialchars($recurso); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
                $index++;
            endforeach; ?>
            
            <div class="flex justify-end animate-fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s">
                <button type="submit" class="flex items-center space-x-2 px-8 py-3.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all duration-200 shadow-lg hover:shadow-xl font-medium">
                    <i class="fas fa-save text-xl"></i>
                    <span>Salvar Todas as Configurações</span>
                </button>
            </div>
        </form>

        <!-- Alterar Plano de Dentista -->
        <div class="mb-8 animate-fade-in">
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Alterar Plano de Dentista</h2>
            <p class="text-gray-600">Gerencie a assinatura individual de cada dentista</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 animate-fade-in" style="animation-delay: 0.4s">
            <div class="flex items-center space-x-4 mb-8">
                <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-user-edit text-blue-600 text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Alterar Assinatura</h3>
                    <p class="text-sm text-gray-500">Selecione o dentista e o novo plano</p>
                </div>
            </div>

            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="alterar_plano_dentista">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-user-md mr-2 text-gray-400"></i>Selecionar Dentista
                        </label>
                        <select name="dentista_id" required
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                            <option value="">Escolha um dentista...</option>
                            <?php foreach ($dentistas as $dentista): ?>
                                <option value="<?php echo $dentista['id']; ?>">
                                    <?php echo htmlspecialchars($dentista['nome']); ?> - 
                                    <?php echo htmlspecialchars($dentista['clinica']); ?> 
                                    (<?php echo ucfirst($dentista['plano']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-tags mr-2 text-gray-400"></i>Novo Plano
                        </label>
                        <select name="novo_plano" required id="novo_plano_select"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                            <option value="">Escolha um plano...</option>
                            
                            <!-- Planos Pagos -->
                            <?php foreach ($planos_config as $plano_key => $plano): ?>
                                <option value="<?php echo $plano_key; ?>" 
                                        data-preco="<?php echo $plano['preco']; ?>"
                                        data-duracao="<?php echo $plano['duracao_dias']; ?>"
                                        data-descricao="<?php echo htmlspecialchars($plano['descricao'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($plano['nome']); ?> 
                                    (R$ <?php echo number_format($plano['preco'], 2, ',', '.'); ?>)
                                </option>
                            <?php endforeach; ?>
                            
                            <!-- Opções Especiais -->
                            <option value="teste" data-preco="0" data-duracao="7" data-descricao="Período de avaliação gratuito">
                                🎁 Período de Teste (Grátis - 7 dias)
                            </option>
                            <option value="expirado" data-preco="0" data-duracao="0" data-descricao="Plano expirado - sem acesso">
                                ❌ Expirado (Sem acesso aos agendamentos)
                            </option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-calendar-check mr-2 text-gray-400"></i>Data de Fim (opcional)
                        </label>
                        <input type="date" name="data_fim"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                    </div>
                </div>
                
                <!-- Informações do Plano Selecionado -->
                <div id="plano_info" class="hidden bg-blue-50 border-l-4 border-blue-500 rounded-xl p-6">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-info-circle text-blue-600 text-lg"></i>
                        </div>
                        <h4 class="text-lg font-bold text-blue-900">Detalhes do Plano</h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-white rounded-lg p-4">
                            <p class="text-xs font-semibold text-gray-500 mb-1 uppercase">Preço</p>
                            <p id="plano_preco" class="text-2xl font-bold text-blue-600"></p>
                        </div>
                        <div class="bg-white rounded-lg p-4">
                            <p class="text-xs font-semibold text-gray-500 mb-1 uppercase">Duração</p>
                            <p id="plano_duracao" class="text-2xl font-bold text-blue-600"></p>
                        </div>
                        <div class="bg-white rounded-lg p-4">
                            <p class="text-xs font-semibold text-gray-500 mb-1 uppercase">Término</p>
                            <p id="plano_data_fim" class="text-2xl font-bold text-blue-600"></p>
                        </div>
                    </div>
                    <div class="mt-4 bg-white rounded-lg p-4">
                        <p class="text-xs font-semibold text-gray-500 mb-2 uppercase">Descrição</p>
                        <p id="plano_descricao" class="text-sm text-gray-700"></p>
                    </div>
                </div>
                
                <div class="flex justify-end pt-4">
                    <button type="submit" class="flex items-center space-x-2 px-8 py-3.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 shadow-lg hover:shadow-xl font-medium">
                        <i class="fas fa-exchange-alt text-xl"></i>
                        <span>Alterar Plano do Dentista</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center">
                <p class="text-gray-600 text-sm">© 2025 Odontfy. Todos os direitos reservados.</p>
                <p class="text-gray-500 text-sm mt-2 sm:mt-0">Gerenciamento de Planos v2.0</p>
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
        
        // Função para atualizar informações do plano selecionado
        function atualizarInfoPlano() {
            const select = document.getElementById('novo_plano_select');
            const planoInfo = document.getElementById('plano_info');
            const dataFimInput = document.querySelector('input[name="data_fim"]');
            
            if (select.value) {
                const option = select.selectedOptions[0];
                const preco = option.dataset.preco;
                const duracao = option.dataset.duracao;
                const descricao = option.dataset.descricao;
                
                // Atualizar informações exibidas
                document.getElementById('plano_preco').textContent = 'R$ ' + parseFloat(preco).toLocaleString('pt-BR', {minimumFractionDigits: 2});
                document.getElementById('plano_duracao').textContent = duracao + ' dias';
                document.getElementById('plano_descricao').textContent = descricao || 'Sem descrição';
                
                // Calcular data de fim automaticamente
                if (duracao && duracao > 0) {
                    const hoje = new Date();
                    const dataFim = new Date(hoje);
                    dataFim.setDate(hoje.getDate() + parseInt(duracao));
                    
                    const ano = dataFim.getFullYear();
                    const mes = String(dataFim.getMonth() + 1).padStart(2, '0');
                    const dia = String(dataFim.getDate()).padStart(2, '0');
                    
                    const dataFormatada = `${ano}-${mes}-${dia}`;
                    dataFimInput.value = dataFormatada;
                    
                    // Exibir data calculada
                    document.getElementById('plano_data_fim').textContent = 
                        dataFim.toLocaleDateString('pt-BR');
                }
                
                // Mostrar informações do plano
                planoInfo.classList.remove('hidden');
            } else {
                // Esconder informações do plano
                planoInfo.classList.add('hidden');
                dataFimInput.value = '';
            }
        }
        
        // Adicionar evento ao select
        document.getElementById('novo_plano_select').addEventListener('change', atualizarInfoPlano);
    </script>
</body>
</html>
