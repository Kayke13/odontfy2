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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $horarios = [
        'segunda' => [
            'ativo' => isset($_POST['segunda_ativo']),
            'inicio' => $_POST['segunda_inicio'] ?? '08:00',
            'fim' => $_POST['segunda_fim'] ?? '18:00'
        ],
        'terca' => [
            'ativo' => isset($_POST['terca_ativo']),
            'inicio' => $_POST['terca_inicio'] ?? '08:00',
            'fim' => $_POST['terca_fim'] ?? '18:00'
        ],
        'quarta' => [
            'ativo' => isset($_POST['quarta_ativo']),
            'inicio' => $_POST['quarta_inicio'] ?? '08:00',
            'fim' => $_POST['quarta_fim'] ?? '18:00'
        ],
        'quinta' => [
            'ativo' => isset($_POST['quinta_ativo']),
            'inicio' => $_POST['quinta_inicio'] ?? '08:00',
            'fim' => $_POST['quinta_fim'] ?? '18:00'
        ],
        'sexta' => [
            'ativo' => isset($_POST['sexta_ativo']),
            'inicio' => $_POST['sexta_inicio'] ?? '08:00',
            'fim' => $_POST['sexta_fim'] ?? '18:00'
        ],
        'sabado' => [
            'ativo' => isset($_POST['sabado_ativo']),
            'inicio' => $_POST['sabado_inicio'] ?? '08:00',
            'fim' => $_POST['sabado_fim'] ?? '18:00'
        ],
        'domingo' => [
            'ativo' => isset($_POST['domingo_ativo']),
            'inicio' => $_POST['domingo_inicio'] ?? '08:00',
            'fim' => $_POST['domingo_fim'] ?? '18:00'
        ]
    ];
    
    $result = Database::update('dentistas', $dentista['id'], ['horarios' => $horarios]);
    
    if ($result) {
        $success = 'Horários atualizados com sucesso!';
        $dentista = Database::find('dentistas', 'id', $dentista['id']); // Atualizar dados
    } else {
        $error = 'Erro ao atualizar horários. Tente novamente.';
    }
}

$dias_semana = [
    'segunda' => 'Segunda-feira',
    'terca' => 'Terça-feira', 
    'quarta' => 'Quarta-feira',
    'quinta' => 'Quinta-feira',
    'sexta' => 'Sexta-feira',
    'sabado' => 'Sábado',
    'domingo' => 'Domingo'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Odontfy</title>
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

        .day-card {
            transition: all 0.3s ease;
        }

        .day-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.1), 0 4px 6px -2px rgba(59, 130, 246, 0.05);
        }

        .tab-button {
            transition: all 0.3s ease;
        }

        .tab-button.active {
            border-bottom-width: 3px;
        }

        .input-field {
            transition: all 0.3s ease;
        }

        .input-field:focus {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.1), 0 2px 4px -1px rgba(59, 130, 246, 0.06);
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: .4s;
            border-radius: 24px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: #3B82F6;
        }

        input:checked + .toggle-slider:before {
            transform: translateX(24px);
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
                <a href="servicos.php" class="menu-item flex items-center px-4 py-3 text-blue-100 hover:bg-blue-700 hover:text-white rounded-lg transition-all group">
                    <i class="fas fa-tooth w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Serviços</span>
                </a>
                <a href="financeiro.php" class="menu-item flex items-center px-4 py-3 text-blue-100 hover:bg-blue-700 hover:text-white rounded-lg transition-all group">
                    <i class="fas fa-dollar-sign w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Financeiro</span>
                </a>
                <a href="configuracoes.php" class="menu-item active flex items-center px-4 py-3 text-white bg-blue-700 rounded-lg hover:bg-blue-500 transition-all group">
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
                                Configurações
                            </h1>
                            <p class="text-sm text-gray-500">Configure horários de atendimento</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-4 sm:p-6 lg:p-8">
            <div class="max-w-6xl mx-auto">
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

                <!-- Tabs -->
                <div class="bg-white rounded-2xl shadow-lg mb-8 fade-in-up">
                    <div class="border-b-2 border-gray-100">
                        <nav class="flex space-x-8 px-6">
                            <button onclick="showTab('consultorio')" id="tab-consultorio" class="tab-button active py-4 px-4 border-b-3 border-blue-600 font-semibold text-blue-600 flex items-center">
                                <i class="fas fa-building mr-2"></i>
                                <span class="hidden sm:inline">Horários do Consultório</span>
                                <span class="sm:hidden">Consultório</span>
                            </button>
                            <button onclick="showTab('dentistas')" id="tab-dentistas" class="tab-button py-4 px-4 border-b-3 border-transparent font-semibold text-gray-500 hover:text-gray-700 flex items-center">
                                <i class="fas fa-user-md mr-2"></i>
                                <span class="hidden sm:inline">Horários dos Dentistas</span>
                                <span class="sm:hidden">Dentistas</span>
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Tab Horários do Consultório -->
                <div id="content-consultorio" class="tab-content fade-in-up">
                    <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-clock text-white text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">Horários de Atendimento</h2>
                                <p class="text-sm text-gray-500">Configure os dias e horários que seu consultório atende</p>
                            </div>
                        </div>

                        <form method="POST" class="space-y-4">
                            <?php foreach ($dias_semana as $dia => $nome_dia): ?>
                                <div class="day-card border-2 border-gray-200 rounded-xl p-5 bg-gradient-to-r from-white to-blue-50">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
                                        <h3 class="text-lg font-bold text-gray-900 mb-3 sm:mb-0 flex items-center">
                                            <i class="far fa-calendar text-blue-600 mr-2"></i>
                                            <?php echo $nome_dia; ?>
                                        </h3>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="<?php echo $dia; ?>_ativo" 
                                                <?php echo ($dentista['horarios'][$dia]['ativo'] ?? false) ? 'checked' : ''; ?>
                                                class="toggle-day">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 day-schedule" 
                                         style="display: <?php echo ($dentista['horarios'][$dia]['ativo'] ?? false) ? 'grid' : 'none'; ?>">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                <i class="fas fa-sun text-blue-600 mr-2"></i>Horário de Início
                                            </label>
                                            <input type="time" name="<?php echo $dia; ?>_inicio" 
                                                value="<?php echo $dentista['horarios'][$dia]['inicio'] ?? '08:00'; ?>"
                                                class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                <i class="fas fa-moon text-blue-600 mr-2"></i>Horário de Fim
                                            </label>
                                            <input type="time" name="<?php echo $dia; ?>_fim" 
                                                value="<?php echo $dentista['horarios'][$dia]['fim'] ?? '18:00'; ?>"
                                                class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition-all">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6">
                                <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold text-center transition-all shadow-lg transform hover:scale-105">
                                    <i class="fas fa-times mr-2"></i>Cancelar
                                </a>
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all shadow-lg transform hover:scale-105">
                                    <i class="fas fa-save mr-2"></i>Salvar Horários
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tab Horários dos Dentistas -->
                <div id="content-dentistas" class="tab-content hidden">
                    <?php
                    // Carregar dentistas do consultório
                    $dentistas_consultorio = [];
                    $dentistas_file = 'data/dentistas_consultorio.json';
                    if (file_exists($dentistas_file)) {
                        $dentistas_consultorio = json_decode(file_get_contents($dentistas_file), true) ?: [];
                        $dentistas_consultorio = array_filter($dentistas_consultorio, function($d) use ($dentista) {
                            return $d['consultorio_id'] === $dentista['id'];
                        });
                    }
                    ?>

                    <?php if (empty($dentistas_consultorio)): ?>
                        <div class="bg-white rounded-2xl shadow-lg p-12 text-center fade-in-up">
                            <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-user-md text-blue-600 text-4xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">Nenhum dentista cadastrado</h3>
                            <p class="text-gray-500 mb-6">Adicione dentistas ao seu consultório para configurar horários individuais.</p>
                            <a href="dentistas_consultorio.php" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg transition-all transform hover:scale-105">
                                <i class="fas fa-plus mr-2"></i>Gerenciar Dentistas
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-6">
                            <?php foreach ($dentistas_consultorio as $index => $dentista_consultorio): ?>
                                <div class="bg-white rounded-2xl shadow-lg overflow-hidden fade-in-up" style="animation-delay: <?php echo ($index * 0.1); ?>s;">
                                    <!-- Dentist Header -->
                                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 px-6 py-5 border-b-2 border-blue-200">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-4">
                                                <img src="<?php echo Upload::getImageUrl($dentista_consultorio['foto'] ?? '', 'dentista_consultorio', $dentista_consultorio['nome']); ?>" 
                                                     alt="Foto" 
                                                     class="w-16 h-16 rounded-full object-cover border-4 border-white shadow-lg">
                                                <div>
                                                    <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($dentista_consultorio['nome']); ?></h3>
                                                    <p class="text-sm text-gray-600 flex items-center">
                                                        <i class="fas fa-stethoscope mr-1.5"></i>
                                                        <?php 
                                                        $especialidades = [
                                                            'clinico_geral' => 'Clínico Geral',
                                                            'ortodontia' => 'Ortodontia',
                                                            'implantodontia' => 'Implantodontia',
                                                            'endodontia' => 'Endodontia',
                                                            'periodontia' => 'Periodontia',
                                                            'odontopediatria' => 'Odontopediatria',
                                                            'protese' => 'Prótese',
                                                            'cirurgia' => 'Cirurgia',
                                                            'estetica' => 'Estética',
                                                            'outros' => 'Outros'
                                                        ];
                                                        echo $especialidades[$dentista_consultorio['especialidade']] ?? 'Não informada';
                                                        ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500">CRO: <?php echo htmlspecialchars($dentista_consultorio['cro'] ?? 'Não informado'); ?></p>
                                                </div>
                                            </div>
                                            <button type="button" 
                                                    onclick="toggleAccordion('dentista-<?php echo $dentista_consultorio['id']; ?>')" 
                                                    class="flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold transition-all shadow-md hover:shadow-lg transform hover:scale-105">
                                                <i class="fas fa-clock mr-2"></i>
                                                <span class="hidden sm:inline">Configurar</span>
                                                <i class="fas fa-chevron-down ml-2 transition-transform duration-200" id="icon-<?php echo $dentista_consultorio['id']; ?>"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Dentist Schedule Form -->
                                    <div id="dentista-<?php echo $dentista_consultorio['id']; ?>" class="hidden p-6 bg-gray-50">
                                        <form onsubmit="saveDentistaHorarios(event, '<?php echo $dentista_consultorio['id']; ?>')" class="space-y-4">
                                            <?php 
                                            $horarios_dentista = $dentista_consultorio['horarios'] ?? [];
                                            foreach ($dias_semana as $dia => $nome_dia): 
                                                $horario_dia = $horarios_dentista[$dia] ?? ['ativo' => false, 'inicio' => '08:00', 'fim' => '18:00'];
                                            ?>
                                                <div class="day-card border-2 border-gray-200 rounded-xl p-4 bg-white">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <h4 class="text-sm font-bold text-gray-900 flex items-center">
                                                            <i class="far fa-calendar text-blue-600 mr-2"></i>
                                                            <?php echo $nome_dia; ?>
                                                        </h4>
                                                        <label class="toggle-switch">
                                                            <input type="checkbox" 
                                                                   name="<?php echo $dia; ?>_ativo" 
                                                                   <?php echo $horario_dia['ativo'] ? 'checked' : ''; ?>
                                                                   class="toggle-day-dentista"
                                                                   data-dia="<?php echo $dia; ?>"
                                                                   data-dentista="<?php echo $dentista_consultorio['id']; ?>">
                                                            <span class="toggle-slider"></span>
                                                        </label>
                                                    </div>

                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 day-schedule-dentista-<?php echo $dentista_consultorio['id']; ?>-<?php echo $dia; ?>" 
                                                         style="display: <?php echo $horario_dia['ativo'] ? 'grid' : 'none'; ?>">
                                                        <div>
                                                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                                                <i class="fas fa-sun text-blue-600 mr-1"></i>Início
                                                            </label>
                                                            <input type="time" 
                                                                   name="<?php echo $dia; ?>_inicio" 
                                                                   value="<?php echo $horario_dia['inicio']; ?>"
                                                                   class="input-field w-full px-3 py-2 text-sm border-2 border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 transition-all">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                                                <i class="fas fa-moon text-blue-600 mr-1"></i>Fim
                                                            </label>
                                                            <input type="time" 
                                                                   name="<?php echo $dia; ?>_fim" 
                                                                   value="<?php echo $horario_dia['fim']; ?>"
                                                                   class="input-field w-full px-3 py-2 text-sm border-2 border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 transition-all">
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>

                                            <div class="flex justify-end pt-4">
                                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all shadow-lg transform hover:scale-105">
                                                    <i class="fas fa-save mr-2"></i>Salvar Horários de <?php echo htmlspecialchars(explode(' ', $dentista_consultorio['nome'])[0]); ?>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
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

        // Toggle day schedule visibility
        document.querySelectorAll('.toggle-day').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const daySchedule = this.closest('.day-card').querySelector('.day-schedule');
                if (this.checked) {
                    daySchedule.style.display = 'grid';
                } else {
                    daySchedule.style.display = 'none';
                }
            });
        });

        // Tab functionality
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(function(content) {
                content.classList.add('hidden');
            });
            
            document.querySelectorAll('.tab-button').forEach(function(button) {
                button.classList.remove('active', 'border-blue-600', 'text-blue-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });
            
            document.getElementById('content-' + tabName).classList.remove('hidden');
            
            const activeButton = document.getElementById('tab-' + tabName);
            activeButton.classList.add('active', 'border-blue-600', 'text-blue-600');
            activeButton.classList.remove('border-transparent', 'text-gray-500');
        }
        
        // Toggle accordion
        function toggleAccordion(id) {
            const content = document.getElementById(id);
            const icon = document.getElementById('icon-' + id.replace('dentista-', ''));
            
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
            } else {
                content.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            }
        }
        
        // Toggle day schedule para dentistas
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.toggle-day-dentista').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    const dia = this.dataset.dia;
                    const dentistaId = this.dataset.dentista;
                    const daySchedule = document.querySelector('.day-schedule-dentista-' + dentistaId + '-' + dia);
                    
                    if (this.checked) {
                        daySchedule.style.display = 'grid';
                    } else {
                        daySchedule.style.display = 'none';
                    }
                });
            });
        });
        
        // Salvar horários de um dentista
        async function saveDentistaHorarios(event, dentistaId) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            
            const horarios = {
                segunda: {
                    ativo: formData.get('segunda_ativo') === 'on',
                    inicio: formData.get('segunda_inicio'),
                    fim: formData.get('segunda_fim')
                },
                terca: {
                    ativo: formData.get('terca_ativo') === 'on',
                    inicio: formData.get('terca_inicio'),
                    fim: formData.get('terca_fim')
                },
                quarta: {
                    ativo: formData.get('quarta_ativo') === 'on',
                    inicio: formData.get('quarta_inicio'),
                    fim: formData.get('quarta_fim')
                },
                quinta: {
                    ativo: formData.get('quinta_ativo') === 'on',
                    inicio: formData.get('quinta_inicio'),
                    fim: formData.get('quinta_fim')
                },
                sexta: {
                    ativo: formData.get('sexta_ativo') === 'on',
                    inicio: formData.get('sexta_inicio'),
                    fim: formData.get('sexta_fim')
                },
                sabado: {
                    ativo: formData.get('sabado_ativo') === 'on',
                    inicio: formData.get('sabado_inicio'),
                    fim: formData.get('sabado_fim')
                },
                domingo: {
                    ativo: formData.get('domingo_ativo') === 'on',
                    inicio: formData.get('domingo_inicio'),
                    fim: formData.get('domingo_fim')
                }
            };
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Salvando...';
            
            try {
                const response = await fetch('api/update_dentista_horarios.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'dentista_id=' + encodeURIComponent(dentistaId) + '&horarios=' + encodeURIComponent(JSON.stringify(horarios))
                });
                
                const result = await response.json();
                
                if (result.success) {
                    submitBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Salvo!';
                    submitBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                    submitBtn.classList.add('bg-green-600');
                    
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.classList.remove('bg-green-600');
                        submitBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                        submitBtn.disabled = false;
                    }, 2000);
                } else {
                    alert('Erro ao salvar horários: ' + result.error);
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao salvar horários. Tente novamente.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
    </script>
</body>
</html>
