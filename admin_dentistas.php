<?php
session_start();
require_once 'config/admin_auth.php';
require_once 'config/database.php';
require_once 'config/upload.php';
require_once 'config/planos.php';

AdminAuth::requireLogin();

$success = '';
$error = '';

// Buscar dentistas
$dentistas = Database::get('dentistas');
$agendamentos = Database::get('agendamentos');

// Estatísticas por dentista
foreach ($dentistas as &$dentista) {
    $agendamentos_dentista = array_filter($agendamentos, function($agendamento) use ($dentista) {
        return $agendamento['dentista_id'] === $dentista['id'];
    });
    
    $dentista['total_agendamentos'] = count($agendamentos_dentista);
    $dentista['agendamentos_hoje'] = 0;
    $dentista['agendamentos_pendentes'] = 0;
    $dentista['agendamentos_confirmados'] = 0;
    $dentista['agendamentos_cancelados'] = 0;
    $dentista['ultimo_agendamento'] = null;
    $dentista['proximo_agendamento'] = null;
    
    foreach ($agendamentos_dentista as $agendamento) {
        // Contar por status
        if ($agendamento['status'] === 'pendente') $dentista['agendamentos_pendentes']++;
        elseif ($agendamento['status'] === 'confirmado') $dentista['agendamentos_confirmados']++;
        elseif ($agendamento['status'] === 'cancelado') $dentista['agendamentos_cancelados']++;
        
        // Agendamentos de hoje
        if (date('Y-m-d', strtotime($agendamento['data'])) === date('Y-m-d')) {
            $dentista['agendamentos_hoje']++;
        }
        
        // Último agendamento
        if (!$dentista['ultimo_agendamento'] || strtotime($agendamento['data']) > strtotime($dentista['ultimo_agendamento']['data'])) {
            $dentista['ultimo_agendamento'] = $agendamento;
        }
        
        // Próximo agendamento (futuro)
        if (strtotime($agendamento['data']) > time()) {
            if (!$dentista['proximo_agendamento'] || strtotime($agendamento['data']) < strtotime($dentista['proximo_agendamento']['data'])) {
                $dentista['proximo_agendamento'] = $agendamento;
            }
        }
    }
    
    // Calcular receita estimada
    $dentista['receita_estimada'] = 0;
    if ($dentista['plano'] === 'mensal') {
        $dentista['receita_estimada'] = 97;
    } elseif ($dentista['plano'] === 'anual') {
        $dentista['receita_estimada'] = 970;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'alterar_status') {
        $dentista_id = $_POST['dentista_id'] ?? '';
        $novo_status = $_POST['novo_status'] ?? '';
        
        if ($dentista_id && $novo_status) {
            $result = Database::update('dentistas', $dentista_id, ['status' => $novo_status]);
            
            if ($result) {
                $success = 'Status do dentista alterado com sucesso!';
                // Recarregar dados
                $dentistas = Database::get('dentistas');
                $agendamentos = Database::get('agendamentos');
                
                // Recalcular estatísticas
                foreach ($dentistas as &$dentista) {
                    $agendamentos_dentista = array_filter($agendamentos, function($agendamento) use ($dentista) {
                        return $agendamento['dentista_id'] === $dentista['id'];
                    });
                    
                    $dentista['total_agendamentos'] = count($agendamentos_dentista);
                    $dentista['agendamentos_hoje'] = 0;
                    $dentista['agendamentos_pendentes'] = 0;
                    $dentista['agendamentos_confirmados'] = 0;
                    $dentista['agendamentos_cancelados'] = 0;
                    $dentista['ultimo_agendamento'] = null;
                    $dentista['proximo_agendamento'] = null;
                    
                    foreach ($agendamentos_dentista as $agendamento) {
                        // Contar por status
                        if ($agendamento['status'] === 'pendente') $dentista['agendamentos_pendentes']++;
                        elseif ($agendamento['status'] === 'confirmado') $dentista['agendamentos_confirmados']++;
                        elseif ($agendamento['status'] === 'cancelado') $dentista['agendamentos_cancelados']++;
                        
                        // Agendamentos de hoje
                        if (date('Y-m-d', strtotime($agendamento['data'])) === date('Y-m-d')) {
                            $dentista['agendamentos_hoje']++;
                        }
                        
                        // Último agendamento
                        if (!$dentista['ultimo_agendamento'] || strtotime($agendamento['data']) > strtotime($dentista['ultimo_agendamento']['data'])) {
                            $dentista['ultimo_agendamento'] = $agendamento;
                        }
                        
                        // Próximo agendamento (futuro)
                        if (strtotime($agendamento['data']) > time()) {
                            if (!$dentista['proximo_agendamento'] || strtotime($agendamento['data']) < strtotime($dentista['proximo_agendamento']['data'])) {
                                $dentista['proximo_agendamento'] = $agendamento;
                            }
                        }
                    }
                    
                    // Calcular receita estimada
                    $dentista['receita_estimada'] = 0;
                    if ($dentista['plano'] === 'mensal') {
                        $dentista['receita_estimada'] = 97;
                    } elseif ($dentista['plano'] === 'anual') {
                        $dentista['receita_estimada'] = 970;
                    }
                }
            } else {
                $error = 'Erro ao alterar status do dentista.';
            }
        }
    } elseif ($action === 'alterar_plano') {
        $dentista_id = $_POST['dentista_id'] ?? '';
        $novo_plano = $_POST['novo_plano'] ?? '';
        $data_fim = $_POST['data_fim'] ?? '';
        
        if ($dentista_id && $novo_plano) {
            $update_data = ['plano' => $novo_plano];
            
            if ($novo_plano !== 'teste') {
                // Se não foi fornecida data_fim, calcular automaticamente usando configurações centralizadas
                if (!$data_fim) {
                    $dias = PlanosConfig::getDuracaoDias($novo_plano);
                    $data_fim = date('Y-m-d', strtotime("+{$dias} days"));
                }
                
                $update_data['plano_fim'] = $data_fim;
                $update_data['assinatura_ativa'] = true;
                // Limpar dados de teste quando ativar plano pago
                $update_data['teste_inicio'] = null;
                $update_data['teste_fim'] = null;
            } elseif ($novo_plano === 'teste') {
                $update_data['teste_fim'] = date('Y-m-d', strtotime('+7 days'));
                $update_data['assinatura_ativa'] = false;
                $update_data['plano_fim'] = null;
            }
            
            $result = Database::update('dentistas', $dentista_id, $update_data);
            
            if ($result) {
                $success = 'Plano do dentista alterado com sucesso!';
                // Recarregar dados
                $dentistas = Database::get('dentistas');
                $agendamentos = Database::get('agendamentos');
                
                // Recalcular estatísticas
                foreach ($dentistas as &$dentista) {
                    $agendamentos_dentista = array_filter($agendamentos, function($agendamento) use ($dentista) {
                        return $agendamento['dentista_id'] === $dentista['id'];
                    });
                    
                    $dentista['total_agendamentos'] = count($agendamentos_dentista);
                    $dentista['agendamentos_hoje'] = 0;
                    $dentista['agendamentos_pendentes'] = 0;
                    $dentista['agendamentos_confirmados'] = 0;
                    $dentista['agendamentos_cancelados'] = 0;
                    $dentista['ultimo_agendamento'] = null;
                    $dentista['proximo_agendamento'] = null;
                    
                    foreach ($agendamentos_dentista as $agendamento) {
                        // Contar por status
                        if ($agendamento['status'] === 'pendente') $dentista['agendamentos_pendentes']++;
                        elseif ($agendamento['status'] === 'confirmado') $dentista['agendamentos_confirmados']++;
                        elseif ($agendamento['status'] === 'cancelado') $dentista['agendamentos_cancelados']++;
                        
                        // Agendamentos de hoje
                        if (date('Y-m-d', strtotime($agendamento['data'])) === date('Y-m-d')) {
                            $dentista['agendamentos_hoje']++;
                        }
                        
                        // Último agendamento
                        if (!$dentista['ultimo_agendamento'] || strtotime($agendamento['data']) > strtotime($dentista['ultimo_agendamento']['data'])) {
                            $dentista['ultimo_agendamento'] = $agendamento;
                        }
                        
                        // Próximo agendamento (futuro)
                        if (strtotime($agendamento['data']) > time()) {
                            if (!$dentista['proximo_agendamento'] || strtotime($agendamento['data']) < strtotime($dentista['proximo_agendamento']['data'])) {
                                $dentista['proximo_agendamento'] = $agendamento;
                            }
                        }
                    }
                    
                    // Calcular receita estimada
                    $dentista['receita_estimada'] = 0;
                    if ($dentista['plano'] === 'mensal') {
                        $dentista['receita_estimada'] = 97;
                    } elseif ($dentista['plano'] === 'anual') {
                        $dentista['receita_estimada'] = 970;
                    }
                }
            } else {
                $error = 'Erro ao alterar plano do dentista.';
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
    <title>Gerenciar Dentistas - Admin Odontfy</title>
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
    <header class="bg-white shadow-md border-b-4 border-blue-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-user-md text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Gerenciar Dentistas</h1>
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

        <!-- Lista de Dentistas -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">
                <i class="fas fa-list mr-2 text-red-600"></i>Todos os Dentistas
            </h2>

            <?php if (empty($dentistas)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-user-md text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhum dentista cadastrado</h3>
                    <p class="text-gray-500">Os dentistas aparecerão aqui quando se cadastrarem.</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($dentistas as $dentista): ?>
                        <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-4 mb-4">
                                        <div class="w-16 h-16 rounded-full overflow-hidden border-2 border-gray-200">
                                            <img src="<?php echo Upload::getImageUrl($dentista['foto'] ?? '', 'dentista', $dentista['nome']); ?>" 
                                                 alt="Foto do dentista" 
                                                 class="w-full h-full object-cover">
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="text-lg font-medium text-gray-900">
                                                <?php echo htmlspecialchars($dentista['nome'] ?? 'Nome não informado'); ?>
                                            </h3>
                                            <p class="text-sm text-gray-600">
                                                <?php echo htmlspecialchars($dentista['clinica'] ?? 'Clínica não informada'); ?>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                <i class="fas fa-envelope mr-1"></i><?php echo htmlspecialchars($dentista['email'] ?? 'Email não informado'); ?>
                                                <span class="mx-2">•</span>
                                                <i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($dentista['telefone'] ?? 'Telefone não informado'); ?>
                                            </p>
                                            
                                            <!-- Informações adicionais do consultório -->
                                            <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2 text-xs text-gray-500">
                                                <?php if (!empty($dentista['cro'])): ?>
                                                    <p><i class="fas fa-id-card mr-1"></i><strong>CRO:</strong> <?php echo htmlspecialchars($dentista['cro']); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($dentista['especialidade'])): ?>
                                                    <p><i class="fas fa-stethoscope mr-1"></i><strong>Especialidade:</strong> 
                                                        <?php
                                                        $especialidades = [
                                                            'clinico_geral' => 'Clínico Geral',
                                                            'ortodontia' => 'Ortodontia',
                                                            'implantodontia' => 'Implantodontia',
                                                            'endodontia' => 'Endodontia',
                                                            'periodontia' => 'Periodontia',
                                                            'odontopediatria' => 'Odontopediatria',
                                                            'prostese' => 'Prótese',
                                                            'cirurgia' => 'Cirurgia',
                                                            'estetica' => 'Odontologia Estética',
                                                            'outros' => 'Outros'
                                                        ];
                                                        echo $especialidades[$dentista['especialidade']] ?? ucfirst($dentista['especialidade']);
                                                        ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($dentista['endereco'])): ?>
                                                    <p><i class="fas fa-map-marker-alt mr-1"></i><strong>Endereço:</strong> <?php echo htmlspecialchars($dentista['endereco']); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($dentista['cidade']) || !empty($dentista['estado'])): ?>
                                                    <p><i class="fas fa-city mr-1"></i><strong>Cidade/Estado:</strong> 
                                                        <?php 
                                                        $cidade_estado = [];
                                                        if (!empty($dentista['cidade'])) $cidade_estado[] = $dentista['cidade'];
                                                        if (!empty($dentista['estado'])) $cidade_estado[] = $dentista['estado'];
                                                        echo htmlspecialchars(implode(' - ', $cidade_estado));
                                                        ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($dentista['cep'])): ?>
                                                    <p><i class="fas fa-mail-bulk mr-1"></i><strong>CEP:</strong> <?php echo htmlspecialchars($dentista['cep']); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($dentista['username'])): ?>
                                                    <p><i class="fas fa-link mr-1"></i><strong>Link Público:</strong> 
                                                        <a href="agendar.php?username=<?php echo htmlspecialchars($dentista['username']); ?>" 
                                                           target="_blank" class="text-blue-600 hover:text-blue-800">
                                                            <?php echo htmlspecialchars($dentista['username']); ?>
                                                        </a>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($dentista['created_at'])): ?>
                                                    <p><i class="fas fa-calendar-plus mr-1"></i><strong>Cadastrado em:</strong> 
                                                        <?php echo date('d/m/Y H:i', strtotime($dentista['created_at'])); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                        <div class="bg-blue-50 rounded-lg p-4">
                                            <div class="text-2xl font-bold text-blue-600">
                                                <?php echo $dentista['total_agendamentos'] ?? 0; ?>
                                            </div>
                                            <div class="text-sm text-gray-600">Total Agendamentos</div>
                                        </div>
                                        
                                        <div class="bg-green-50 rounded-lg p-4">
                                            <div class="text-2xl font-bold text-green-600">
                                                <?php echo $dentista['agendamentos_hoje'] ?? 0; ?>
                                            </div>
                                            <div class="text-sm text-gray-600">Agendamentos Hoje</div>
                                        </div>
                                        
                                        <div class="bg-purple-50 rounded-lg p-4">
                                            <div class="text-sm font-medium text-purple-600">
                                                <?php echo ucfirst($dentista['plano']); ?>
                                            </div>
                                            <div class="text-sm text-gray-600">Plano Atual</div>
                                            <?php if (($dentista['receita_estimada'] ?? 0) > 0): ?>
                                                <div class="text-xs text-gray-500">R$ <?php echo number_format($dentista['receita_estimada'], 2, ',', '.'); ?>/mês</div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="bg-yellow-50 rounded-lg p-4">
                                            <div class="text-sm font-medium text-yellow-600">
                                                <?php echo ucfirst($dentista['status'] ?? 'ativo'); ?>
                                            </div>
                                            <div class="text-sm text-gray-600">Status</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Estatísticas detalhadas -->
                                    <div class="grid grid-cols-3 gap-4 mb-4">
                                        <div class="bg-yellow-50 rounded-lg p-3">
                                            <div class="text-lg font-bold text-yellow-600">
                                                <?php echo $dentista['agendamentos_pendentes'] ?? 0; ?>
                                            </div>
                                            <div class="text-xs text-gray-600">Pendentes</div>
                                        </div>
                                        
                                        <div class="bg-green-50 rounded-lg p-3">
                                            <div class="text-lg font-bold text-green-600">
                                                <?php echo $dentista['agendamentos_confirmados'] ?? 0; ?>
                                            </div>
                                            <div class="text-xs text-gray-600">Confirmados</div>
                                        </div>
                                        
                                        <div class="bg-red-50 rounded-lg p-3">
                                            <div class="text-lg font-bold text-red-600">
                                                <?php echo $dentista['agendamentos_cancelados'] ?? 0; ?>
                                            </div>
                                            <div class="text-xs text-gray-600">Cancelados</div>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <?php if (!empty($dentista['ultimo_agendamento'])): ?>
                                            <div class="bg-gray-50 rounded-lg p-4">
                                                <h4 class="text-sm font-medium text-gray-700 mb-2">Último Agendamento</h4>
                                                <p class="text-sm text-gray-600">
                                                    <?php echo htmlspecialchars($dentista['ultimo_agendamento']['paciente_nome']); ?> - 
                                                    <?php echo date('d/m/Y H:i', strtotime($dentista['ultimo_agendamento']['data'])); ?>
                                                </p>
                                                <span class="inline-block px-2 py-1 text-xs font-medium rounded-full <?php 
                                                    echo $dentista['ultimo_agendamento']['status'] === 'pendente' ? 'bg-yellow-100 text-yellow-800' :
                                                    ($dentista['ultimo_agendamento']['status'] === 'confirmado' ? 'bg-green-100 text-green-800' :
                                                    ($dentista['ultimo_agendamento']['status'] === 'cancelado' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'));
                                                ?>">
                                                    <?php echo ucfirst($dentista['ultimo_agendamento']['status']); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($dentista['proximo_agendamento'])): ?>
                                            <div class="bg-blue-50 rounded-lg p-4">
                                                <h4 class="text-sm font-medium text-gray-700 mb-2">Próximo Agendamento</h4>
                                                <p class="text-sm text-gray-600">
                                                    <?php echo htmlspecialchars($dentista['proximo_agendamento']['paciente_nome']); ?> - 
                                                    <?php echo date('d/m/Y H:i', strtotime($dentista['proximo_agendamento']['data'])); ?>
                                                </p>
                                                <span class="inline-block px-2 py-1 text-xs font-medium rounded-full <?php 
                                                    echo $dentista['proximo_agendamento']['status'] === 'pendente' ? 'bg-yellow-100 text-yellow-800' :
                                                    ($dentista['proximo_agendamento']['status'] === 'confirmado' ? 'bg-green-100 text-green-800' :
                                                    ($dentista['proximo_agendamento']['status'] === 'cancelado' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'));
                                                ?>">
                                                    <?php echo ucfirst($dentista['proximo_agendamento']['status']); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="ml-4 flex flex-col space-y-2">
                                    <!-- Alterar Status -->
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="alterar_status">
                                        <input type="hidden" name="dentista_id" value="<?php echo $dentista['id']; ?>">
                                        <select name="novo_status" onchange="this.form.submit()" 
                                            class="text-xs border border-gray-300 rounded px-2 py-1">
                                            <option value="ativo" <?php echo ($dentista['status'] ?? 'ativo') === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                            <option value="suspenso" <?php echo ($dentista['status'] ?? 'ativo') === 'suspenso' ? 'selected' : ''; ?>>Suspenso</option>
                                            <option value="bloqueado" <?php echo ($dentista['status'] ?? 'ativo') === 'bloqueado' ? 'selected' : ''; ?>>Bloqueado</option>
                                        </select>
                                    </form>
                                    
                                    <!-- Alterar Plano -->
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="alterar_plano">
                                        <input type="hidden" name="dentista_id" value="<?php echo $dentista['id']; ?>">
                                        <select name="novo_plano" onchange="calcularDataFim(this)" 
                                            class="text-xs border border-gray-300 rounded px-2 py-1">
                                            <option value="teste" <?php echo $dentista['plano'] === 'teste' ? 'selected' : ''; ?>>Teste</option>
                                            <option value="mensal" <?php echo $dentista['plano'] === 'mensal' ? 'selected' : ''; ?>>Mensal</option>
                                            <option value="semestral" <?php echo $dentista['plano'] === 'semestral' ? 'selected' : ''; ?>>Semestral</option>
                                            <option value="anual" <?php echo $dentista['plano'] === 'anual' ? 'selected' : ''; ?>>Anual</option>
                                        </select>
                                        <input type="hidden" name="data_fim" id="data_fim_<?php echo $dentista['id']; ?>" value="">
                                    </form>
                                    
                                    <a href="tel:<?php echo htmlspecialchars($dentista['telefone']); ?>" 
                                       class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-xs text-center">
                                        <i class="fas fa-phone mr-1"></i>Ligar
                                    </a>
                                    
                                    <a href="mailto:<?php echo htmlspecialchars($dentista['email']); ?>" 
                                       class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs text-center">
                                        <i class="fas fa-envelope mr-1"></i>Email
                                    </a>
                                </div>
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

        function calcularDataFim(select) {
            const plano = select.value;
            const form = select.closest('form');
            const dataFimInput = form.querySelector('input[name="data_fim"]');
            
            if (plano === 'teste') {
                dataFimInput.value = '';
            } else {
                // Calcular data de fim baseada no plano
                const hoje = new Date();
                let dias = 30; // padrão mensal
                
                // Usar as durações configuradas (mesmo que hardcoded no JS por simplicidade)
                if (plano === 'semestral') {
                    dias = 180;
                } else if (plano === 'anual') {
                    dias = 365;
                }
                
                const dataFim = new Date(hoje);
                dataFim.setDate(hoje.getDate() + dias);
                
                // Formatar para YYYY-MM-DD
                const ano = dataFim.getFullYear();
                const mes = String(dataFim.getMonth() + 1).padStart(2, '0');
                const dia = String(dataFim.getDate()).padStart(2, '0');
                
                dataFimInput.value = `${ano}-${mes}-${dia}`;
            }
            
            // Submeter o formulário
            form.submit();
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
