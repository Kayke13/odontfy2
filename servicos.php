<?php
session_start();
require_once 'auth.php';
require_once 'config/database.php';
require_once 'config/plano_status.php';

Auth::requireLogin();

$dentista = Auth::getCurrentUser();

// Verificar status do plano e aplicar restrições
$status_plano = PlanoStatus::verificarStatus($dentista);

// Aplicar restrições se necessário
if (!$status_plano['pode_usar_sistema']) {
    PlanoStatus::aplicarRestricoes($dentista);
}
$success = '';
$error = '';

// Carregar dentistas do consultório
$dentistas_consultorio = [];
$dentistas_file = 'data/dentistas_consultorio.json';
if (file_exists($dentistas_file)) {
    $all_dentistas = json_decode(file_get_contents($dentistas_file), true) ?: [];
    $dentistas_consultorio = array_filter($all_dentistas, function($d) use ($dentista) {
        return $d['consultorio_id'] === $dentista['id'] && ($d['ativo'] ?? true);
    });
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        // Verificar limite de serviços baseado no plano
        $servicos = $dentista['servicos'] ?? [];
        $limite_servicos = $status_plano['limite_servicos'];
        
        if (count($servicos) >= $limite_servicos) {
            $error = "Limite de {$limite_servicos} serviços atingido para seu plano. Assine um plano superior para mais serviços.";
        } else {
            // Coletar dentistas selecionados
            $dentistas_selecionados = $_POST['dentistas'] ?? [];
            
            $novo_servico = [
                'nome' => $_POST['nome'],
                'duracao' => (int)$_POST['duracao'],
                'preco' => (float)$_POST['preco'],
                'descricao' => $_POST['descricao'] ?? '',
                'dentistas' => $dentistas_selecionados, // Array de IDs dos dentistas
                'ativo' => true
            ];
            
            $servicos[] = $novo_servico;
            
            $result = Database::update('dentistas', $dentista['id'], ['servicos' => $servicos]);
            
            if ($result) {
                $success = 'Serviço adicionado com sucesso!';
                $dentista = Database::find('dentistas', 'id', $dentista['id']);
            } else {
                $error = 'Erro ao adicionar serviço. Tente novamente.';
            }
        }
    } elseif ($action === 'toggle') {
        $index = (int)$_POST['index'];
        $servicos = $dentista['servicos'] ?? [];
        
        if (isset($servicos[$index])) {
            $servicos[$index]['ativo'] = !$servicos[$index]['ativo'];
            $result = Database::update('dentistas', $dentista['id'], ['servicos' => $servicos]);
            
            if ($result) {
                $success = 'Status do serviço atualizado!';
                $dentista = Database::find('dentistas', 'id', $dentista['id']);
            } else {
                $error = 'Erro ao atualizar serviço.';
            }
        }
    } elseif ($action === 'edit') {
        $index = (int)$_POST['index'];
        $servicos = $dentista['servicos'] ?? [];
        
        if (isset($servicos[$index])) {
            // Coletar dentistas selecionados
            $dentistas_selecionados = $_POST['dentistas'] ?? [];
            
            $servicos[$index] = [
                'nome' => $_POST['nome'],
                'duracao' => (int)$_POST['duracao'],
                'preco' => (float)$_POST['preco'],
                'descricao' => $_POST['descricao'] ?? '',
                'dentistas' => $dentistas_selecionados,
                'ativo' => $servicos[$index]['ativo'] ?? true
            ];
            
            $result = Database::update('dentistas', $dentista['id'], ['servicos' => $servicos]);
            
            if ($result) {
                $success = 'Serviço atualizado com sucesso!';
                $dentista = Database::find('dentistas', 'id', $dentista['id']);
            } else {
                $error = 'Erro ao atualizar serviço.';
            }
        }
    } elseif ($action === 'delete') {
        $index = (int)$_POST['index'];
        $servicos = $dentista['servicos'] ?? [];
        
        if (isset($servicos[$index])) {
            unset($servicos[$index]);
            $servicos = array_values($servicos); // Reindexar array
            
            $result = Database::update('dentistas', $dentista['id'], ['servicos' => $servicos]);
            
            if ($result) {
                $success = 'Serviço removido com sucesso!';
                $dentista = Database::find('dentistas', 'id', $dentista['id']);
            } else {
                $error = 'Erro ao remover serviço.';
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
    <title>Gerenciar Serviços - Odontfy</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/LOGO.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        body, h1, h2, h3, h4, h5, h6, p, a, button, input, textarea, select, label, span, div {
            font-family: 'Inter', sans-serif !important;
        }
        
        .fa, .fas, .far, .fal, .fab, [class^="fa-"], [class*=" fa-"] {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Pro", "Font Awesome 6 Brands" !important;
        }
        
        i.fa, i.fas, i.far, i.fal, i.fab {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Pro", "Font Awesome 6 Brands" !important;
        }
        
        /* Destaque visual para dentistas selecionados */
        .dentista-checkbox-label {
            transition: all 0.3s ease;
        }
        
        .dentista-checkbox-label:has(input:checked) {
            background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%) !important;
            border-color: #3B82F6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .dentista-checkbox-label input:checked {
            background-color: #3B82F6;
            border-color: #3B82F6;
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

        .service-card {
            transition: all 0.3s ease;
        }

        .service-card:hover {
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

        .input-field {
            transition: all 0.3s ease;
        }

        .input-field:focus {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.1), 0 2px 4px -1px rgba(59, 130, 246, 0.06);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 font-inter min-h-screen">
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
                <a href="servicos.php" class="menu-item active flex items-center px-4 py-3 text-white bg-blue-700 rounded-lg hover:bg-blue-500 transition-all group">
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
                                Gerenciar Serviços
                            </h1>
                            <p class="text-sm text-gray-500">Configure os serviços do seu consultório</p>
                        </div>
                    </div>
                    <div class="hidden sm:flex items-center space-x-2">
                        <?php 
                        $servicos_count = count($dentista['servicos'] ?? []);
                        $limite_servicos = $status_plano['limite_servicos'];
                        ?>
                        <span class="px-4 py-2 rounded-lg text-sm font-semibold <?php echo $servicos_count >= $limite_servicos ? 'bg-red-100 text-red-800 border-2 border-red-300' : 'bg-blue-50 text-blue-900'; ?>">
                            <i class="fas fa-list-check mr-2"></i><?php echo $servicos_count; ?>/<?php echo $limite_servicos; ?> serviços
                        </span>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Statistics Card -->
            <div class="stat-card bg-white rounded-2xl shadow-lg p-6 mb-8 fade-in-up">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-list text-blue-600 text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total de Serviços</p>
                            <p class="text-3xl font-bold text-blue-600"><?php echo $servicos_count; ?></p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-check-circle text-blue-600 text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Serviços Ativos</p>
                            <p class="text-3xl font-bold text-blue-600">
                                <?php echo count(array_filter($dentista['servicos'] ?? [], function($s) { return $s['ativo']; })); ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-crown text-blue-600 text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Limite do Plano</p>
                            <p class="text-3xl font-bold text-blue-600"><?php echo $limite_servicos; ?></p>
                        </div>
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Adicionar Serviço -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-lg p-6 fade-in-up sticky top-24">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-plus text-white text-xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900">Novo Serviço</h3>
                        </div>
                        
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="add">
                            
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-tooth text-blue-600 mr-2"></i>Nome do Serviço *
                                </label>
                                <input type="text" name="nome" required
                                    class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-all"
                                    placeholder="Ex: Limpeza, Restauração">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-clock text-blue-600 mr-2"></i>Duração (minutos) *
                                </label>
                                <input type="number" name="duracao" required min="15" step="15"
                                    class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-all"
                                    placeholder="30">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-dollar-sign text-blue-600 mr-2"></i>Preço (R$)
                                </label>
                                <input type="number" name="preco" step="0.01" min="0"
                                    class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-all"
                                    placeholder="0,00">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-file-alt text-blue-600 mr-2"></i>Descrição
                                </label>
                                <input type="text" name="descricao"
                                    class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-all"
                                    placeholder="Descrição opcional">
                            </div>
                            
                            <!-- Seleção de Dentistas -->
                            <div class="border-t-2 border-gray-200 pt-4">
                                <label class="block text-sm font-semibold text-gray-700 mb-3">
                                    <i class="fas fa-user-md text-blue-600 mr-2"></i>Dentistas que executam este serviço
                                </label>
                                
                                <?php if (empty($dentistas_consultorio)): ?>
                                    <div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-4">
                                        <p class="text-sm text-blue-900 font-medium mb-2">
                                            <i class="fas fa-info-circle mr-2"></i>Nenhum dentista cadastrado
                                        </p>
                                        <p class="text-xs text-blue-700">
                                            Este serviço será executado por você (dentista principal). 
                                            <a href="dentistas_consultorio.php" class="underline font-semibold">Adicionar dentistas</a>
                                        </p>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-2 max-h-48 overflow-y-auto bg-gray-50 rounded-xl p-3">
                                        <!-- Dentista Principal -->
                                        <label class="flex items-center p-3 bg-white rounded-lg border-2 border-blue-200 hover:border-blue-400 cursor-pointer transition-all">
                                            <input type="checkbox" name="dentistas[]" value="principal" checked
                                                class="w-4 h-4 text-blue-600 focus:ring-blue-500 rounded">
                                            <div class="ml-3 flex items-center flex-1">
                                                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center mr-2">
                                                    <i class="fas fa-star text-white text-xs"></i>
                                                </div>
                                                <div>
                                                    <span class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($dentista['nome']); ?></span>
                                                    <span class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-800 text-xs rounded-full font-semibold">Principal</span>
                                                </div>
                                            </div>
                                        </label>
                                        
                                        <!-- Outros Dentistas -->
                                        <?php foreach ($dentistas_consultorio as $dc): ?>
                                            <label class="flex items-center p-3 bg-white rounded-lg border-2 border-gray-200 hover:border-blue-400 cursor-pointer transition-all">
                                                <input type="checkbox" name="dentistas[]" value="<?php echo $dc['id']; ?>"
                                                    class="w-4 h-4 text-blue-600 focus:ring-blue-500 rounded">
                                                <div class="ml-3 flex items-center flex-1">
                                                    <?php require_once 'config/upload.php'; ?>
                                                    <img src="<?php echo Upload::getImageUrl($dc['foto'] ?? '', 'dentista_consultorio', $dc['nome']); ?>" 
                                                         alt="<?php echo htmlspecialchars($dc['nome']); ?>"
                                                         class="w-8 h-8 rounded-full object-cover mr-2 border-2 border-gray-300">
                                                    <span class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($dc['nome']); ?></span>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2">
                                        <i class="fas fa-lightbulb text-yellow-500 mr-1"></i>
                                        Selecione os dentistas que podem realizar este serviço. Por padrão, você (principal) está sempre selecionado.
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-xl font-semibold shadow-lg transition-all transform hover:scale-105">
                                <i class="fas fa-plus mr-2"></i>Adicionar Serviço
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Lista de Serviços -->
                <div class="lg:col-span-2">
                    <?php if (empty($dentista['servicos'])): ?>
                        <div class="bg-white rounded-2xl shadow-lg p-12 text-center fade-in-up">
                            <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-tooth text-blue-600 text-4xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">Nenhum serviço cadastrado</h3>
                            <p class="text-gray-500 mb-2">Comece adicionando o primeiro serviço ao seu consultório.</p>
                            <p class="text-sm text-gray-400">Use o formulário ao lado para adicionar um novo serviço.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-6">
                            <?php foreach ($dentista['servicos'] as $index => $servico): ?>
                                <div class="service-card bg-white rounded-2xl shadow-lg overflow-hidden fade-in-up" 
                                     style="animation-delay: <?php echo ($index * 0.1); ?>s;">
                                    
                                    <!-- Card Header -->
                                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 px-6 py-5 border-b-2 border-blue-200">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-4 flex-1">
                                                <div class="w-14 h-14 bg-blue-600 rounded-2xl flex items-center justify-center">
                                                    <i class="fas fa-tooth text-white text-2xl"></i>
                                                </div>
                                                <div class="flex-1">
                                                    <h4 class="text-xl font-bold text-gray-900 mb-1">
                                                        <?php echo htmlspecialchars($servico['nome']); ?>
                                                    </h4>
                                                    <?php if (!empty($servico['descricao'])): ?>
                                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($servico['descricao']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="px-4 py-2 text-sm font-semibold rounded-full <?php echo $servico['ativo'] ? 'bg-green-100 text-green-800 border-2 border-green-300' : 'bg-red-100 text-red-800 border-2 border-red-300'; ?>">
                                                    <?php echo $servico['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Card Body -->
                                    <div class="p-6">
                                        <div class="grid grid-cols-2 gap-6 mb-6">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                                    <i class="fas fa-clock text-blue-600 text-xl"></i>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500">Duração</p>
                                                    <p class="text-lg font-bold text-gray-900"><?php echo $servico['duracao']; ?> min</p>
                                                </div>
                                            </div>
                                            <?php if ($servico['preco'] > 0): ?>
                                            <div class="flex items-center space-x-3">
                                                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                                    <i class="fas fa-dollar-sign text-blue-600 text-xl"></i>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500">Preço</p>
                                                    <p class="text-lg font-bold text-gray-900">R$ <?php echo number_format($servico['preco'], 2, ',', '.'); ?></p>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Dentistas Associados -->
                                        <?php if (!empty($servico['dentistas'])): ?>
                                            <div class="mb-6 pt-4 border-t border-gray-200">
                                                <p class="text-xs font-semibold text-gray-600 mb-3 flex items-center">
                                                    <i class="fas fa-user-md text-blue-600 mr-2"></i>
                                                    Dentistas que executam este serviço:
                                                </p>
                                                <div class="flex flex-wrap gap-2">
                                                    <?php foreach ($servico['dentistas'] as $dentista_id): ?>
                                                        <?php if ($dentista_id === 'principal'): ?>
                                                            <span class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-800 rounded-lg text-xs font-semibold border-2 border-blue-300">
                                                                <i class="fas fa-star mr-1.5"></i>
                                                                <?php echo htmlspecialchars($dentista['nome']); ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <?php 
                                                            foreach ($dentistas_consultorio as $dc) {
                                                                if ($dc['id'] === $dentista_id) {
                                                                    echo '<span class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-800 rounded-lg text-xs font-semibold border-2 border-gray-300">';
                                                                    echo '<i class="fas fa-user-md mr-1.5"></i>';
                                                                    echo htmlspecialchars($dc['nome']);
                                                                    echo '</span>';
                                                                    break;
                                                                }
                                                            }
                                                            ?>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="mb-6 pt-4 border-t border-gray-200">
                                                <p class="text-xs text-gray-500 italic flex items-center">
                                                    <i class="fas fa-info-circle text-gray-400 mr-2"></i>
                                                    Todos os dentistas podem executar este serviço
                                                </p>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Actions -->
                                        <div class="flex flex-wrap gap-3">
                                            <!-- Editar -->
                                            <button type="button" onclick="openEditModal(<?php echo $index; ?>)" class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold transition-all shadow-md hover:shadow-lg transform hover:scale-105 bg-blue-100 text-blue-800 hover:bg-blue-200 border-2 border-blue-300">
                                                <i class="fas fa-edit mr-2"></i>Editar
                                            </button>
                                            
                                            <!-- Toggle Status -->
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="index" value="<?php echo $index; ?>">
                                                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold transition-all shadow-md hover:shadow-lg transform hover:scale-105 <?php echo $servico['ativo'] ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200 border-2 border-yellow-300' : 'bg-green-100 text-green-800 hover:bg-green-200 border-2 border-green-300'; ?>">
                                                    <i class="fas <?php echo $servico['ativo'] ? 'fa-pause' : 'fa-play'; ?> mr-2"></i>
                                                    <?php echo $servico['ativo'] ? 'Desativar' : 'Ativar'; ?>
                                                </button>
                                            </form>
                                            
                                            <!-- Excluir -->
                                            <form method="POST" class="inline" onsubmit="return confirm('⚠️ Tem certeza que deseja remover este serviço?\n\nEsta ação não pode ser desfeita.')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="index" value="<?php echo $index; ?>">
                                                <button type="submit" class="inline-flex items-center bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all shadow-md hover:shadow-lg transform hover:scale-105">
                                                    <i class="fas fa-trash mr-2"></i>Remover
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edição -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-cyan-600 text-white px-6 py-4 flex items-center justify-between rounded-t-2xl">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-edit text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold">Editar Serviço</h3>
                </div>
                <button type="button" onclick="closeEditModal()" class="w-8 h-8 bg-white bg-opacity-20 rounded-lg hover:bg-opacity-30 transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Form -->
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="index" id="edit-index">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-tooth text-blue-600 mr-2"></i>Nome do Serviço *
                    </label>
                    <input type="text" name="nome" id="edit-nome" required
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-all">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-clock text-blue-600 mr-2"></i>Duração (minutos) *
                        </label>
                        <input type="number" name="duracao" id="edit-duracao" required min="15" step="15"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-all">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-dollar-sign text-blue-600 mr-2"></i>Preço (R$)
                        </label>
                        <input type="number" name="preco" id="edit-preco" step="0.01" min="0"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-all">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-file-alt text-blue-600 mr-2"></i>Descrição
                    </label>
                    <input type="text" name="descricao" id="edit-descricao"
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-all">
                </div>
                
                <!-- Seleção de Dentistas -->
                <div class="border-t-2 border-gray-200 pt-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-user-md text-blue-600 mr-2"></i>Dentistas que executam este serviço
                    </label>
                    
                    <?php if (empty($dentistas_consultorio)): ?>
                        <div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-4">
                            <p class="text-sm text-blue-900 font-medium">
                                <i class="fas fa-info-circle mr-2"></i>Este serviço será executado por você (dentista principal).
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2 max-h-48 overflow-y-auto bg-gray-50 rounded-xl p-3">
                            <!-- Dentista Principal -->
                            <label class="dentista-checkbox-label flex items-center p-3 bg-white rounded-lg border-2 border-blue-200 hover:border-blue-400 cursor-pointer transition-all">
                                <input type="checkbox" name="dentistas[]" value="principal" id="edit-dentista-principal"
                                    class="w-5 h-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                                <div class="ml-3 flex items-center flex-1">
                                    <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-cyan-600 rounded-lg flex items-center justify-center text-white font-bold text-sm mr-3">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($dentista['nome']); ?></p>
                                        <p class="text-xs text-blue-600 font-medium">Dentista Principal</p>
                                    </div>
                                </div>
                            </label>
                            
                            <!-- Outros Dentistas -->
                            <?php foreach ($dentistas_consultorio as $dc): ?>
                                <label class="dentista-checkbox-label flex items-center p-3 bg-white rounded-lg border-2 border-gray-200 hover:border-gray-300 cursor-pointer transition-all">
                                    <input type="checkbox" name="dentistas[]" value="<?php echo $dc['id']; ?>" class="w-5 h-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                                    <div class="ml-3 flex items-center flex-1">
                                        <?php if (!empty($dc['foto'])): ?>
                                            <img src="<?php echo htmlspecialchars($dc['foto']); ?>" alt="<?php echo htmlspecialchars($dc['nome']); ?>" 
                                                class="w-10 h-10 rounded-lg object-cover mr-3">
                                        <?php else: ?>
                                            <div class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center text-gray-600 font-bold text-sm mr-3">
                                                <?php echo strtoupper(substr($dc['nome'], 0, 2)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($dc['nome']); ?></p>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Se nenhum dentista for selecionado, todos poderão executar este serviço.
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- Buttons -->
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeEditModal()" 
                        class="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition-all">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </button>
                    <button type="submit" 
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-cyan-700 transition-all shadow-lg hover:shadow-xl">
                        <i class="fas fa-save mr-2"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Dados dos serviços para JavaScript
        const servicos = <?php echo json_encode($dentista['servicos'] ?? []); ?>;
        
        function openEditModal(index) {
            const servico = servicos[index];
            if (!servico) return;
            
            console.log('=== Abrindo modal de edição ===');
            console.log('Serviço:', servico);
            console.log('Dentistas do serviço:', servico.dentistas);
            
            // Preencher campos
            document.getElementById('edit-index').value = index;
            document.getElementById('edit-nome').value = servico.nome || '';
            document.getElementById('edit-duracao').value = servico.duracao || 30;
            document.getElementById('edit-preco').value = servico.preco || 0;
            document.getElementById('edit-descricao').value = servico.descricao || '';
            
            // IMPORTANTE: Buscar checkboxes APENAS dentro do modal de edição
            const modal = document.getElementById('editModal');
            const allCheckboxes = modal.querySelectorAll('input[name="dentistas[]"]');
            console.log('Total de checkboxes no modal:', allCheckboxes.length);
            
            // Desmarcar TODOS os checkboxes primeiro
            allCheckboxes.forEach(cb => {
                console.log('Checkbox value:', cb.value);
                cb.checked = false;
            });
            
            // Marcar dentistas selecionados
            const dentistasSelecionados = servico.dentistas || [];
            console.log('Dentistas selecionados:', dentistasSelecionados);
            
            if (dentistasSelecionados.length > 0) {
                dentistasSelecionados.forEach(dentistaId => {
                    // Buscar checkbox APENAS dentro do modal
                    const checkbox = modal.querySelector(`input[name="dentistas[]"][value="${dentistaId}"]`);
                    console.log(`Procurando checkbox para dentista ${dentistaId}:`, checkbox);
                    if (checkbox) {
                        checkbox.checked = true;
                        console.log(`✓ Checkbox do dentista ${dentistaId} marcado`);
                    } else {
                        console.log(`✗ Checkbox do dentista ${dentistaId} NÃO encontrado`);
                    }
                });
            } else {
                console.log('Nenhum dentista específico selecionado - serviço disponível para todos');
            }
            
            // Mostrar modal
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        
        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        
        // Fechar modal ao clicar fora
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
        
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
