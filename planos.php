<?php
session_start();
require_once 'auth.php';
require_once 'config/database.php';
require_once 'config/planos.php';

Auth::requireLogin();

$dentista = Auth::getCurrentUser();

// Carregar configurações dos planos
$planos = PlanosConfig::getPlanos();

// Remover plano teste da lista de planos para compra
unset($planos['teste']);

// Verificar status da assinatura atual
$assinatura_ativa = $dentista['assinatura_ativa'] ?? false;
$plano_fim = $dentista['plano_fim'] ?? null;
$plano_atual = $dentista['plano'] ?? 'mensal';

// Verificar se a assinatura expirou
$assinatura_expirada = false;
if ($plano_fim && strtotime($plano_fim) < time()) {
    $assinatura_expirada = true;
    $assinatura_ativa = false;
}

// Verificar se está no período de teste (7 dias) - apenas se não tiver assinatura ativa
$teste_inicio = $dentista['teste_inicio'] ?? null;
$teste_fim = $dentista['teste_fim'] ?? null;
$em_teste = false;
$teste_expirado = false;

if (!$assinatura_ativa && $teste_inicio && $teste_fim) {
    // Garantir que as datas tenham formato completo com horário
    $teste_fim_timestamp = strtotime($teste_fim);
    
    if ($teste_fim_timestamp > time()) {
        $em_teste = true;
    } else {
        $teste_expirado = true;
    }
}

$success = '';
$error = '';

// Verificar se veio de um pagamento bem-sucedido
if (isset($_GET['success']) && $_GET['success'] === '1') {
    $success = 'Plano ativado com sucesso! Sua assinatura está ativa.';
}

// Verificar se veio de tentativa de acesso a recurso bloqueado
$recurso_bloqueado = $_GET['recurso'] ?? '';
$mensagem_recurso = '';
if ($recurso_bloqueado) {
    $recursos_nomes = [
        'controle_financeiro' => 'Controle Financeiro',
        'multiplos_dentistas' => 'Múltiplos Dentistas',
        'relatorios_avancados' => 'Relatórios Avançados'
    ];
    $nome_recurso = $recursos_nomes[$recurso_bloqueado] ?? $recurso_bloqueado;
    $mensagem_recurso = "O recurso '{$nome_recurso}' está disponível apenas em planos pagos. Assine um plano para acessar esta funcionalidade.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos - Odontfy</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/LOGO.ico">
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
                <a href="planos.php" class="menu-item active flex items-center px-4 py-3 text-white bg-blue-700 rounded-lg hover:bg-blue-500 transition-all group">
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
                        <h1 class="text-xl font-bold text-gray-900">Planos e Assinaturas</h1>
                    </div>
                </div>
            </div>
        </nav>
        <div class="p-4 sm:p-6 lg:p-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Escolha seu Plano</h1>
            <p class="text-gray-600 mt-2">Selecione o plano que melhor se adequa às suas necessidades</p>
        </div>

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

        <?php if ($mensagem_recurso): ?>
            <div class="bg-orange-100 border border-orange-400 text-orange-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-lock mr-2"></i><?php echo $mensagem_recurso; ?>
            </div>
        <?php endif; ?>

        <!-- Status Atual -->
        <?php if ($assinatura_ativa && !$assinatura_expirada): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-blue-800">Plano Atual</h3>
                        <p class="text-blue-600">
                            Você está atualmente no <?php echo $planos[$plano_atual]['nome']; ?>
                        </p>
                        <p class="text-sm text-blue-500 mt-1">
                            Vence em: <?php echo date('d/m/Y', strtotime($plano_fim)); ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php elseif ($em_teste): ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-clock text-yellow-400 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-yellow-800">Período de Teste</h3>
                        <p class="text-yellow-600">Você está no período de teste de 7 dias</p>
                        <p class="text-sm text-yellow-500 mt-1">
                            Teste termina em: <?php echo date('d/m/Y H:i', strtotime($teste_fim)); ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Planos -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach ($planos as $key => $plano): ?>
                <div class="bg-white rounded-lg shadow-lg overflow-hidden <?php echo $plano_atual === $key && $assinatura_ativa ? 'ring-2 ring-blue-500' : ''; ?>">
                    <?php if ($plano_atual === $key && $assinatura_ativa): ?>
                        <div class="bg-blue-500 text-white text-center py-2 text-sm font-medium">
                            <i class="fas fa-check mr-2"></i>Plano Atual
                        </div>
                    <?php endif; ?>
                    
                    <div class="p-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2"><?php echo $plano['nome']; ?></h3>
                        <p class="text-gray-600 mb-4"><?php echo $plano['descricao']; ?></p>
                        
                        <div class="mb-6">
                            <span class="text-4xl font-bold text-gray-900">R$ <?php echo number_format($plano['preco'], 2, ',', '.'); ?></span>
                            <span class="text-gray-500">
                                <?php 
                                $periodo = $key === 'mensal' ? 'mês' : ($key === 'semestral' ? '6 meses' : 'ano');
                                echo "/{$periodo}";
                                ?>
                            </span>
                        </div>
                        
                        <ul class="space-y-3 mb-8">
                            <?php foreach ($plano['recursos'] as $recurso): ?>
                                <li class="flex items-center">
                                    <i class="fas fa-check text-green-500 mr-3"></i>
                                    <span class="text-gray-700"><?php echo $recurso; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <?php if ($plano_atual === $key && $assinatura_ativa): ?>
                            <button disabled class="w-full bg-gray-300 text-gray-500 py-3 px-4 rounded-md font-medium cursor-not-allowed">
                                <i class="fas fa-check mr-2"></i>Plano Ativo
                            </button>
                        <?php else: ?>
                            <a href="pagamento.php?plano=<?php echo $key; ?>" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-4 rounded-md font-medium transition-colors inline-block text-center">
                                <i class="fas fa-credit-card mr-2"></i>
                                <?php echo $assinatura_ativa ? 'Trocar Plano' : 'Assinar Plano'; ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Informações Adicionais -->
        <div class="mt-12 bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Informações Importantes</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Formas de Pagamento</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Cartão de crédito</li>
                        <li>• PIX</li>
                        <li>• Boleto bancário</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Garantia</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Cancele a qualquer momento</li>
                        <li>• Suporte 24/7</li>
                        <li>• Backup automático dos dados</li>
                    </ul>
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
    </script>
</body>
</html>
