<?php
session_start();
require_once 'config/admin_auth.php';
require_once 'config/database.php';

AdminAuth::requireLogin();

// Buscar todos os agendamentos
$agendamentos = Database::get('agendamentos');
$dentistas = Database::get('dentistas');

// Criar array de dentistas para lookup
$dentistas_lookup = [];
foreach ($dentistas as $dentista) {
    $dentistas_lookup[$dentista['id']] = $dentista;
}

// Estatísticas dos agendamentos
$total_agendamentos = count($agendamentos);
$agendamentos_hoje = 0;
$agendamentos_pendentes = 0;
$agendamentos_confirmados = 0;
$agendamentos_cancelados = 0;
$agendamentos_por_dentista = [];

foreach ($agendamentos as $agendamento) {
    // Contar por status
    if ($agendamento['status'] === 'pendente') $agendamentos_pendentes++;
    elseif ($agendamento['status'] === 'confirmado') $agendamentos_confirmados++;
    elseif ($agendamento['status'] === 'cancelado') $agendamentos_cancelados++;
    
    // Agendamentos de hoje
    if (date('Y-m-d', strtotime($agendamento['data'])) === date('Y-m-d')) {
        $agendamentos_hoje++;
    }
    
    // Contar por dentista
    $dentista_id = $agendamento['dentista_id'];
    if (!isset($agendamentos_por_dentista[$dentista_id])) {
        $agendamentos_por_dentista[$dentista_id] = 0;
    }
    $agendamentos_por_dentista[$dentista_id]++;
}

// Ordenar agendamentos por data (mais recentes primeiro)
usort($agendamentos, function($a, $b) {
    return strtotime($b['data']) - strtotime($a['data']);
});

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'alterar_status') {
        $agendamento_id = $_POST['agendamento_id'] ?? '';
        $novo_status = $_POST['novo_status'] ?? '';
        
        if ($agendamento_id && $novo_status) {
            $result = Database::update('agendamentos', $agendamento_id, ['status' => $novo_status]);
            
            if ($result) {
                $success = 'Status do agendamento alterado com sucesso!';
                // Recarregar dados
                $agendamentos = Database::get('agendamentos');
                usort($agendamentos, function($a, $b) {
                    return strtotime($b['data']) - strtotime($a['data']);
                });
            } else {
                $error = 'Erro ao alterar status do agendamento.';
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
    <title>Gerenciar Agendamentos - Admin Odontfy</title>
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
    <header class="bg-white shadow-md border-b-4 border-purple-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-calendar-alt text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Gerenciar Agendamentos</h1>
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
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-calendar-check text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total de Agendamentos</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $total_agendamentos; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-calendar-day text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Hoje</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $agendamentos_hoje; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Pendentes</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $agendamentos_pendentes; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-check text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Confirmados</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $agendamentos_confirmados; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <i class="fas fa-times text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Cancelados</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $agendamentos_cancelados; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Agendamentos -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">
                <i class="fas fa-list mr-2" style="color: #1570EF;"></i>Lista de Agendamentos
            </h2>

            <?php if (empty($agendamentos)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-calendar-times text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhum agendamento encontrado</h3>
                    <p class="text-gray-500">Os agendamentos aparecerão aqui quando forem criados.</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($agendamentos as $agendamento): ?>
                        <?php $dentista = $dentistas_lookup[$agendamento['dentista_id']] ?? null; ?>
                        <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-4 mb-4">
                                        <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-gray-200">
                                            <img src="<?php echo Upload::getImageUrl($agendamento['paciente_foto'] ?? '', 'cliente', $agendamento['paciente_nome']); ?>" 
                                                 alt="Foto do cliente" 
                                                 class="w-full h-full object-cover">
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-medium text-gray-900">
                                                <?php echo htmlspecialchars($agendamento['paciente_nome']); ?>
                                            </h3>
                                            <p class="text-sm text-gray-600">
                                                <?php echo htmlspecialchars($agendamento['servico']); ?>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                <i class="fas fa-envelope mr-1"></i><?php echo htmlspecialchars($agendamento['paciente_email']); ?>
                                                <span class="mx-2">•</span>
                                                <i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($agendamento['paciente_telefone']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700 mb-1">Data e Horário</h4>
                                            <p class="text-sm text-gray-600">
                                                <?php echo date('d/m/Y', strtotime($agendamento['data'])); ?> às 
                                                <?php echo date('H:i', strtotime($agendamento['data'])); ?>
                                            </p>
                                        </div>
                                        
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700 mb-1">Dentista</h4>
                                            <p class="text-sm text-gray-600">
                                                <?php echo $dentista ? htmlspecialchars($dentista['nome']) : 'Dentista não encontrado'; ?>
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                <?php echo $dentista ? htmlspecialchars($dentista['clinica']) : ''; ?>
                                            </p>
                                        </div>
                                        
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700 mb-1">Criado em</h4>
                                            <p class="text-sm text-gray-600">
                                                <?php echo date('d/m/Y H:i', strtotime($agendamento['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($agendamento['observacoes'])): ?>
                                        <div class="bg-gray-50 rounded-lg p-4">
                                            <h4 class="text-sm font-medium text-gray-700 mb-2">Observações</h4>
                                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($agendamento['observacoes']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="ml-4 flex flex-col space-y-2">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="alterar_status">
                                        <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                        <select name="novo_status" onchange="this.form.submit()" 
                                            class="text-xs border border-gray-300 rounded px-2 py-1">
                                            <option value="pendente" <?php echo $agendamento['status'] === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                            <option value="confirmado" <?php echo $agendamento['status'] === 'confirmado' ? 'selected' : ''; ?>>Confirmado</option>
                                            <option value="cancelado" <?php echo $agendamento['status'] === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                        </select>
                                    </form>
                                    
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php 
                                        echo $agendamento['status'] === 'pendente' ? 'bg-yellow-100 text-yellow-800' :
                                        ($agendamento['status'] === 'confirmado' ? 'bg-green-100 text-green-800' :
                                        ($agendamento['status'] === 'cancelado' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'));
                                    ?>">
                                        <?php echo ucfirst($agendamento['status']); ?>
                                    </span>
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
