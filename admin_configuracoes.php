<?php
session_start();
require_once 'config/admin_auth.php';
require_once 'config/database.php';

AdminAuth::requireLogin();

$success = '';
$error = '';

// Carregar configurações atuais
$configuracoes_file = 'data/configuracoes.json';
$configuracoes = [];

if (file_exists($configuracoes_file)) {
    $configuracoes = json_decode(file_get_contents($configuracoes_file), true) ?: [];
}

// Configurações padrão
$configuracoes_default = [
    'sistema' => [
        'nome' => 'Odontfy',
        'versao' => '1.0.0',
        'manutencao' => false,
        'manutencao_mensagem' => 'Sistema em manutenção. Voltaremos em breve.',
        'email_contato' => 'contato@odontfy.com',
        'telefone_contato' => '(11) 99999-9999',
        'endereco' => 'São Paulo, SP',
        'horario_funcionamento' => 'Segunda a Sexta: 8h às 18h'
    ],
    'planos' => [
        'mensal' => [
            'nome' => 'Plano Mensal',
            'preco' => 97.00,
            'descricao' => 'Acesso completo por 30 dias',
            'recursos' => ['Agendamentos ilimitados', 'Gestão de clientes', 'Relatórios básicos'],
            'duracao_dias' => 30
        ],
        'semestral' => [
            'nome' => 'Plano Semestral',
            'preco' => 485.00,
            'descricao' => 'Acesso completo por 6 meses (1 mês grátis)',
            'recursos' => ['Agendamentos ilimitados', 'Gestão de clientes', 'Relatórios intermediários', 'Suporte por email'],
            'duracao_dias' => 180
        ],
        'anual' => [
            'nome' => 'Plano Anual',
            'preco' => 970.00,
            'descricao' => 'Acesso completo por 12 meses (2 meses grátis)',
            'recursos' => ['Agendamentos ilimitados', 'Gestão de clientes', 'Relatórios avançados', 'Suporte prioritário'],
            'duracao_dias' => 365
        ]
    ],
    'notificacoes' => [
        'email_agendamento' => true,
        'email_confirmacao' => true,
        'email_lembrete' => true,
        'email_cancelamento' => true,
        'sms_agendamento' => false,
        'sms_lembrete' => false
    ],
    'backup' => [
        'automatico' => true,
        'frequencia' => 'diario',
        'manter_backups' => 7
    ]
];

// Mesclar com configurações existentes
$configuracoes = array_merge($configuracoes_default, $configuracoes);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'salvar_sistema') {
        $configuracoes['sistema'] = [
            'nome' => $_POST['nome_sistema'] ?? $configuracoes['sistema']['nome'],
            'versao' => $_POST['versao'] ?? $configuracoes['sistema']['versao'],
            'manutencao' => isset($_POST['manutencao']),
            'manutencao_mensagem' => $_POST['manutencao_mensagem'] ?? $configuracoes['sistema']['manutencao_mensagem'],
            'email_contato' => $_POST['email_contato'] ?? $configuracoes['sistema']['email_contato'],
            'telefone_contato' => $_POST['telefone_contato'] ?? $configuracoes['sistema']['telefone_contato'],
            'endereco' => $_POST['endereco'] ?? $configuracoes['sistema']['endereco'],
            'horario_funcionamento' => $_POST['horario_funcionamento'] ?? $configuracoes['sistema']['horario_funcionamento']
        ];
        $success = 'Configurações do sistema atualizadas com sucesso!';
    }
    
    elseif ($action === 'salvar_planos') {
        $configuracoes['planos']['mensal'] = [
            'nome' => $_POST['mensal_nome'] ?? $configuracoes['planos']['mensal']['nome'],
            'preco' => floatval($_POST['mensal_preco'] ?? $configuracoes['planos']['mensal']['preco']),
            'descricao' => $_POST['mensal_descricao'] ?? $configuracoes['planos']['mensal']['descricao'],
            'recursos' => explode("\n", $_POST['mensal_recursos'] ?? implode("\n", $configuracoes['planos']['mensal']['recursos'])),
            'duracao_dias' => intval($_POST['mensal_duracao'] ?? $configuracoes['planos']['mensal']['duracao_dias'])
        ];
        
        $configuracoes['planos']['semestral'] = [
            'nome' => $_POST['semestral_nome'] ?? $configuracoes['planos']['semestral']['nome'],
            'preco' => floatval($_POST['semestral_preco'] ?? $configuracoes['planos']['semestral']['preco']),
            'descricao' => $_POST['semestral_descricao'] ?? $configuracoes['planos']['semestral']['descricao'],
            'recursos' => explode("\n", $_POST['semestral_recursos'] ?? implode("\n", $configuracoes['planos']['semestral']['recursos'])),
            'duracao_dias' => intval($_POST['semestral_duracao'] ?? $configuracoes['planos']['semestral']['duracao_dias'])
        ];
        
        $configuracoes['planos']['anual'] = [
            'nome' => $_POST['anual_nome'] ?? $configuracoes['planos']['anual']['nome'],
            'preco' => floatval($_POST['anual_preco'] ?? $configuracoes['planos']['anual']['preco']),
            'descricao' => $_POST['anual_descricao'] ?? $configuracoes['planos']['anual']['descricao'],
            'recursos' => explode("\n", $_POST['anual_recursos'] ?? implode("\n", $configuracoes['planos']['anual']['recursos'])),
            'duracao_dias' => intval($_POST['anual_duracao'] ?? $configuracoes['planos']['anual']['duracao_dias'])
        ];
        $success = 'Configurações dos planos atualizadas com sucesso!';
    }
    
    elseif ($action === 'salvar_notificacoes') {
        $configuracoes['notificacoes'] = [
            'email_agendamento' => isset($_POST['email_agendamento']),
            'email_confirmacao' => isset($_POST['email_confirmacao']),
            'email_lembrete' => isset($_POST['email_lembrete']),
            'email_cancelamento' => isset($_POST['email_cancelamento']),
            'sms_agendamento' => isset($_POST['sms_agendamento']),
            'sms_lembrete' => isset($_POST['sms_lembrete'])
        ];
        $success = 'Configurações de notificações atualizadas com sucesso!';
    }
    
    elseif ($action === 'salvar_backup') {
        $configuracoes['backup'] = [
            'automatico' => isset($_POST['backup_automatico']),
            'frequencia' => $_POST['backup_frequencia'] ?? $configuracoes['backup']['frequencia'],
            'manter_backups' => intval($_POST['manter_backups'] ?? $configuracoes['backup']['manter_backups'])
        ];
        $success = 'Configurações de backup atualizadas com sucesso!';
    }
    
    elseif ($action === 'fazer_backup') {
        // Criar backup dos dados
        $backup_data = [
            'data' => date('Y-m-d H:i:s'),
            'agendamentos' => Database::get('agendamentos'),
            'dentistas' => Database::get('dentistas'),
            'configuracoes' => $configuracoes
        ];
        
        $backup_dir = 'backups';
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        $backup_file = $backup_dir . '/backup_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($backup_file, json_encode($backup_data, JSON_PRETTY_PRINT));
        
        $success = 'Backup criado com sucesso! Arquivo: ' . basename($backup_file);
    }
    
    // Salvar configurações
    file_put_contents($configuracoes_file, json_encode($configuracoes, JSON_PRETTY_PRINT));
}

// Estatísticas do sistema
$agendamentos = Database::get('agendamentos');
$dentistas = Database::get('dentistas');
$total_agendamentos = count($agendamentos);
$total_dentistas = count($dentistas);
$agendamentos_hoje = count(array_filter($agendamentos, function($a) { 
    return date('Y-m-d', strtotime($a['data'])) === date('Y-m-d'); 
}));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Admin Odontfy</title>
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
    <header class="bg-white shadow-md border-b-4 border-gray-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gray-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-cog text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Configurações do Sistema</h1>
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

        <!-- Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-calendar-alt text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Agendamentos</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $total_agendamentos; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-user-md text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Dentistas Ativos</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $total_dentistas; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-calendar-day text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Agendamentos Hoje</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $agendamentos_hoje; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-server text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Status do Sistema</p>
                        <p class="text-lg font-semibold <?php echo $configuracoes['sistema']['manutencao'] ? 'text-red-600' : 'text-green-600'; ?>">
                            <?php echo $configuracoes['sistema']['manutencao'] ? 'Manutenção' : 'Online'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Abas -->
        <div class="bg-white rounded-lg shadow-lg">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                    <button onclick="mostrarAba('sistema')" id="tab-sistema" 
                            class="py-4 px-1 border-b-2 font-medium text-sm tab-button active">
                        <i class="fas fa-cog mr-2"></i>Sistema
                    </button>
                    <button onclick="mostrarAba('planos')" id="tab-planos" 
                            class="py-4 px-1 border-b-2 font-medium text-sm tab-button">
                        <i class="fas fa-credit-card mr-2"></i>Planos
                    </button>
                    <button onclick="mostrarAba('notificacoes')" id="tab-notificacoes" 
                            class="py-4 px-1 border-b-2 font-medium text-sm tab-button">
                        <i class="fas fa-bell mr-2"></i>Notificações
                    </button>
                    <button onclick="mostrarAba('backup')" id="tab-backup" 
                            class="py-4 px-1 border-b-2 font-medium text-sm tab-button">
                        <i class="fas fa-database mr-2"></i>Backup
                    </button>
                </nav>
            </div>

            <!-- Aba Sistema -->
            <div id="aba-sistema" class="tab-content p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Configurações do Sistema</h3>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="salvar_sistema">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Sistema</label>
                            <input type="text" name="nome_sistema" value="<?php echo htmlspecialchars($configuracoes['sistema']['nome']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Versão</label>
                            <input type="text" name="versao" value="<?php echo htmlspecialchars($configuracoes['sistema']['versao']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="manutencao" id="manutencao" 
                               <?php echo $configuracoes['sistema']['manutencao'] ? 'checked' : ''; ?>
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="manutencao" class="ml-2 block text-sm text-gray-900">
                            Sistema em manutenção
                        </label>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mensagem de Manutenção</label>
                        <textarea name="manutencao_mensagem" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($configuracoes['sistema']['manutencao_mensagem']); ?></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email de Contato</label>
                            <input type="email" name="email_contato" value="<?php echo htmlspecialchars($configuracoes['sistema']['email_contato']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Telefone de Contato</label>
                            <input type="text" name="telefone_contato" value="<?php echo htmlspecialchars($configuracoes['sistema']['telefone_contato']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Endereço</label>
                        <input type="text" name="endereco" value="<?php echo htmlspecialchars($configuracoes['sistema']['endereco']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Horário de Funcionamento</label>
                        <input type="text" name="horario_funcionamento" value="<?php echo htmlspecialchars($configuracoes['sistema']['horario_funcionamento']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <button type="submit" 
                            class="text-white px-6 py-2 rounded-md font-medium"
                            style="background-color: #1570EF;"
                            onmouseover="this.style.backgroundColor='#0D5BD9'"
                            onmouseout="this.style.backgroundColor='#1570EF'">
                        <i class="fas fa-save mr-2"></i>Salvar Configurações
                    </button>
                </form>
            </div>

            <!-- Aba Planos -->
            <div id="aba-planos" class="tab-content p-6 hidden">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Configurações dos Planos</h3>
                
                <form method="POST" class="space-y-8">
                    <input type="hidden" name="action" value="salvar_planos">
                    
                    <!-- Plano Mensal -->
                    <div class="border border-gray-200 rounded-lg p-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Plano Mensal</h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Plano</label>
                                <input type="text" name="mensal_nome" value="<?php echo htmlspecialchars($configuracoes['planos']['mensal']['nome']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Preço (R$)</label>
                                <input type="number" step="0.01" name="mensal_preco" value="<?php echo $configuracoes['planos']['mensal']['preco']; ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Duração (dias)</label>
                                <input type="number" name="mensal_duracao" value="<?php echo $configuracoes['planos']['mensal']['duracao_dias']; ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                            <input type="text" name="mensal_descricao" value="<?php echo htmlspecialchars($configuracoes['planos']['mensal']['descricao']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Recursos (um por linha)</label>
                            <textarea name="mensal_recursos" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo implode("\n", $configuracoes['planos']['mensal']['recursos']); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Plano Semestral -->
                    <div class="border border-gray-200 rounded-lg p-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Plano Semestral</h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Plano</label>
                                <input type="text" name="semestral_nome" value="<?php echo htmlspecialchars($configuracoes['planos']['semestral']['nome']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Preço (R$)</label>
                                <input type="number" step="0.01" name="semestral_preco" value="<?php echo $configuracoes['planos']['semestral']['preco']; ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Duração (dias)</label>
                                <input type="number" name="semestral_duracao" value="<?php echo $configuracoes['planos']['semestral']['duracao_dias']; ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                            <input type="text" name="semestral_descricao" value="<?php echo htmlspecialchars($configuracoes['planos']['semestral']['descricao']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Recursos (um por linha)</label>
                            <textarea name="semestral_recursos" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo implode("\n", $configuracoes['planos']['semestral']['recursos']); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Plano Anual -->
                    <div class="border border-gray-200 rounded-lg p-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Plano Anual</h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Plano</label>
                                <input type="text" name="anual_nome" value="<?php echo htmlspecialchars($configuracoes['planos']['anual']['nome']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Preço (R$)</label>
                                <input type="number" step="0.01" name="anual_preco" value="<?php echo $configuracoes['planos']['anual']['preco']; ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Duração (dias)</label>
                                <input type="number" name="anual_duracao" value="<?php echo $configuracoes['planos']['anual']['duracao_dias']; ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                            <input type="text" name="anual_descricao" value="<?php echo htmlspecialchars($configuracoes['planos']['anual']['descricao']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Recursos (um por linha)</label>
                            <textarea name="anual_recursos" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo implode("\n", $configuracoes['planos']['anual']['recursos']); ?></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" 
                            class="text-white px-6 py-2 rounded-md font-medium"
                            style="background-color: #1570EF;"
                            onmouseover="this.style.backgroundColor='#0D5BD9'"
                            onmouseout="this.style.backgroundColor='#1570EF'">
                        <i class="fas fa-save mr-2"></i>Salvar Planos
                    </button>
                </form>
            </div>

            <!-- Aba Notificações -->
            <div id="aba-notificacoes" class="tab-content p-6 hidden">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Configurações de Notificações</h3>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="salvar_notificacoes">
                    
                    <div class="space-y-4">
                        <h4 class="text-md font-medium text-gray-900">Notificações por Email</h4>
                        
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <input type="checkbox" name="email_agendamento" id="email_agendamento" 
                                       <?php echo $configuracoes['notificacoes']['email_agendamento'] ? 'checked' : ''; ?>
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="email_agendamento" class="ml-2 block text-sm text-gray-900">
                                    Enviar email quando um agendamento for criado
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="email_confirmacao" id="email_confirmacao" 
                                       <?php echo $configuracoes['notificacoes']['email_confirmacao'] ? 'checked' : ''; ?>
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="email_confirmacao" class="ml-2 block text-sm text-gray-900">
                                    Enviar email de confirmação de agendamento
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="email_lembrete" id="email_lembrete" 
                                       <?php echo $configuracoes['notificacoes']['email_lembrete'] ? 'checked' : ''; ?>
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="email_lembrete" class="ml-2 block text-sm text-gray-900">
                                    Enviar email de lembrete antes do agendamento
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="email_cancelamento" id="email_cancelamento" 
                                       <?php echo $configuracoes['notificacoes']['email_cancelamento'] ? 'checked' : ''; ?>
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="email_cancelamento" class="ml-2 block text-sm text-gray-900">
                                    Enviar email quando um agendamento for cancelado
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <h4 class="text-md font-medium text-gray-900">Notificações por SMS</h4>
                        
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <input type="checkbox" name="sms_agendamento" id="sms_agendamento" 
                                       <?php echo $configuracoes['notificacoes']['sms_agendamento'] ? 'checked' : ''; ?>
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="sms_agendamento" class="ml-2 block text-sm text-gray-900">
                                    Enviar SMS quando um agendamento for criado
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="sms_lembrete" id="sms_lembrete" 
                                       <?php echo $configuracoes['notificacoes']['sms_lembrete'] ? 'checked' : ''; ?>
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="sms_lembrete" class="ml-2 block text-sm text-gray-900">
                                    Enviar SMS de lembrete antes do agendamento
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" 
                            class="text-white px-6 py-2 rounded-md font-medium"
                            style="background-color: #1570EF;"
                            onmouseover="this.style.backgroundColor='#0D5BD9'"
                            onmouseout="this.style.backgroundColor='#1570EF'">
                        <i class="fas fa-save mr-2"></i>Salvar Notificações
                    </button>
                </form>
            </div>

            <!-- Aba Backup -->
            <div id="aba-backup" class="tab-content p-6 hidden">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Configurações de Backup</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Configurações -->
                    <div>
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="action" value="salvar_backup">
                            
                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <input type="checkbox" name="backup_automatico" id="backup_automatico" 
                                           <?php echo $configuracoes['backup']['automatico'] ? 'checked' : ''; ?>
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="backup_automatico" class="ml-2 block text-sm text-gray-900">
                                        Backup automático habilitado
                                    </label>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Frequência do Backup</label>
                                    <select name="backup_frequencia" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="diario" <?php echo $configuracoes['backup']['frequencia'] === 'diario' ? 'selected' : ''; ?>>Diário</option>
                                        <option value="semanal" <?php echo $configuracoes['backup']['frequencia'] === 'semanal' ? 'selected' : ''; ?>>Semanal</option>
                                        <option value="mensal" <?php echo $configuracoes['backup']['frequencia'] === 'mensal' ? 'selected' : ''; ?>>Mensal</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Manter Backups (dias)</label>
                                    <input type="number" name="manter_backups" value="<?php echo $configuracoes['backup']['manter_backups']; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <button type="submit" 
                                    class="text-white px-6 py-2 rounded-md font-medium"
                                    style="background-color: #1570EF;"
                                    onmouseover="this.style.backgroundColor='#0D5BD9'"
                                    onmouseout="this.style.backgroundColor='#1570EF'">
                                <i class="fas fa-save mr-2"></i>Salvar Configurações
                            </button>
                        </form>
                    </div>
                    
                    <!-- Ações -->
                    <div>
                        <h4 class="text-md font-medium text-gray-900 mb-4">Ações de Backup</h4>
                        
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="fazer_backup">
                            
                            <button type="submit" 
                                    class="w-full text-white px-6 py-3 rounded-md font-medium"
                                    style="background-color: #10B981;"
                                    onmouseover="this.style.backgroundColor='#059669'"
                                    onmouseout="this.style.backgroundColor='#10B981'">
                                <i class="fas fa-download mr-2"></i>Criar Backup Agora
                            </button>
                        </form>
                        
                        <div class="mt-6 p-4 bg-yellow-50 rounded-lg">
                            <h5 class="text-sm font-medium text-yellow-800 mb-2">Informações sobre Backup</h5>
                            <ul class="text-sm text-yellow-700 space-y-1">
                                <li>• Backups são salvos na pasta 'backups/'</li>
                                <li>• Cada backup contém todos os dados do sistema</li>
                                <li>• Backups antigos são removidos automaticamente</li>
                                <li>• Recomenda-se fazer backup antes de atualizações</li>
                            </ul>
                        </div>
                    </div>
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

        function mostrarAba(aba) {
            // Esconder todas as abas
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remover classe active de todos os botões
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active', 'border-blue-500', 'text-blue-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Mostrar aba selecionada
            document.getElementById('aba-' + aba).classList.remove('hidden');
            
            // Ativar botão selecionado
            const button = document.getElementById('tab-' + aba);
            button.classList.add('active', 'border-blue-500', 'text-blue-600');
            button.classList.remove('border-transparent', 'text-gray-500');
        }
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
