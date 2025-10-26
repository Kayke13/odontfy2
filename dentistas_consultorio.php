<?php
session_start();
require_once 'auth.php';
require_once 'config/database.php';
require_once 'config/upload.php';
require_once 'config/plano_status.php';

Auth::requireLogin();

$dentista_principal = Auth::getCurrentUser();

// Verificar status do plano e aplicar restrições
$status_plano = PlanoStatus::verificarStatus($dentista_principal);

// Verificar se pode usar múltiplos dentistas
if (!PlanoStatus::podeUsarFuncionalidade($dentista_principal, 'multiplos_dentistas')) {
    header('Location: planos.php?recurso=multiplos_dentistas');
    exit;
}
$success = '';
$error = '';

// Carregar dentistas do consultório
$dentistas_file = 'data/dentistas_consultorio.json';
$dentistas_consultorio = [];
if (file_exists($dentistas_file)) {
    $dentistas_consultorio = json_decode(file_get_contents($dentistas_file), true) ?: [];
}

// Filtrar dentistas do consultório atual
$dentistas_meu_consultorio = array_filter($dentistas_consultorio, function($item) use ($dentista_principal) {
    return $item['consultorio_id'] === $dentista_principal['id'];
});

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'adicionar_dentista') {
        $nome = trim($_POST['nome'] ?? '');
        $especialidade = trim($_POST['especialidade'] ?? '');
        $cro = trim($_POST['cro'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        
        if ($nome && $especialidade && $cro) {
            $novo_dentista = [
                'id' => uniqid(),
                'consultorio_id' => $dentista_principal['id'],
                'nome' => $nome,
                'especialidade' => $especialidade,
                'cro' => $cro,
                'telefone' => $telefone,
                'bio' => $bio,
                'foto' => '',
                'ativo' => true,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $dentistas_consultorio[] = $novo_dentista;
            
            if (file_put_contents($dentistas_file, json_encode($dentistas_consultorio, JSON_PRETTY_PRINT))) {
                $success = 'Dentista adicionado com sucesso!';
                $dentistas_meu_consultorio = array_filter($dentistas_consultorio, function($item) use ($dentista_principal) {
                    return $item['consultorio_id'] === $dentista_principal['id'];
                });
            } else {
                $error = 'Erro ao salvar dentista.';
            }
        } else {
            $error = 'Por favor, preencha todos os campos obrigatórios.';
        }
    } elseif ($action === 'upload_foto') {
        $dentista_id = $_POST['dentista_id'] ?? '';
        
        if ($dentista_id && isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = Upload::uploadImage($_FILES['foto'], 'dentista_consultorio');
            
            if ($uploadResult['success']) {
                // Atualizar foto do dentista
                foreach ($dentistas_consultorio as &$d) {
                    if ($d['id'] === $dentista_id) {
                        // Remover foto anterior se existir
                        if (!empty($d['foto'])) {
                            $oldPhoto = 'uploads/dentistas_consultorio/' . $d['foto'];
                            if (file_exists($oldPhoto)) {
                                unlink($oldPhoto);
                            }
                        }
                        
                        $d['foto'] = $uploadResult['filename'];
                        break;
                    }
                }
                
                if (file_put_contents($dentistas_file, json_encode($dentistas_consultorio, JSON_PRETTY_PRINT))) {
                    $success = 'Foto atualizada com sucesso!';
                    $dentistas_meu_consultorio = array_filter($dentistas_consultorio, function($item) use ($dentista_principal) {
                        return $item['consultorio_id'] === $dentista_principal['id'];
                    });
                } else {
                    $error = 'Erro ao salvar foto.';
                }
            } else {
                $error = $uploadResult['message'];
            }
        } else {
            $error = 'Nenhuma foto foi selecionada.';
        }
    } elseif ($action === 'toggle_status') {
        $dentista_id = $_POST['dentista_id'] ?? '';
        
        if ($dentista_id) {
            foreach ($dentistas_consultorio as &$d) {
                if ($d['id'] === $dentista_id) {
                    $d['ativo'] = !$d['ativo'];
                    break;
                }
            }
            
            if (file_put_contents($dentistas_file, json_encode($dentistas_consultorio, JSON_PRETTY_PRINT))) {
                $success = 'Status do dentista atualizado!';
                $dentistas_meu_consultorio = array_filter($dentistas_consultorio, function($item) use ($dentista_principal) {
                    return $item['consultorio_id'] === $dentista_principal['id'];
                });
            } else {
                $error = 'Erro ao atualizar status.';
            }
        }
    } elseif ($action === 'excluir_dentista') {
        $dentista_id = $_POST['dentista_id'] ?? '';
        
        if ($dentista_id) {
            // Remover foto se existir
            foreach ($dentistas_consultorio as $d) {
                if ($d['id'] === $dentista_id && !empty($d['foto'])) {
                    $oldPhoto = 'uploads/dentistas_consultorio/' . $d['foto'];
                    if (file_exists($oldPhoto)) {
                        unlink($oldPhoto);
                    }
                    break;
                }
            }
            
            $dentistas_consultorio = array_filter($dentistas_consultorio, function($item) use ($dentista_id) {
                return $item['id'] !== $dentista_id;
            });
            
            if (file_put_contents($dentistas_file, json_encode($dentistas_consultorio, JSON_PRETTY_PRINT))) {
                $success = 'Dentista excluído com sucesso!';
                $dentistas_meu_consultorio = array_filter($dentistas_consultorio, function($item) use ($dentista_principal) {
                    return $item['consultorio_id'] === $dentista_principal['id'];
                });
            } else {
                $error = 'Erro ao excluir dentista.';
            }
        }
    }
}

// Especialidades predefinidas
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dentistas do Consultório - Odontfy</title>
    <link rel="icon" type="image/x-icon" href="assets/images/LOGO.ico">
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
                    <img src="<?php echo Upload::getImageUrl($dentista_principal['foto'] ?? '', 'dentista', $dentista_principal['nome']); ?>" 
                         alt="Foto" 
                         class="w-12 h-12 rounded-full border-2 border-white object-cover">
                    <div class="flex-1 min-w-0">
                        <p class="text-white font-semibold truncate"><?php echo htmlspecialchars($dentista_principal['nome']); ?></p>
                        <p class="text-blue-200 text-sm truncate"><?php echo htmlspecialchars($dentista_principal['email']); ?></p>
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
                <a href="dentistas_consultorio.php" class="menu-item active flex items-center px-4 py-3 text-white bg-blue-700 rounded-lg hover:bg-blue-500 transition-all group">
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
                <a href="agendar.php?username=<?php echo $dentista_principal['username']; ?>" target="_blank" class="menu-item flex items-center px-4 py-3 text-blue-100 hover:bg-blue-700 hover:text-white rounded-lg transition-all group">
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
        <!-- Header Moderno -->
        <header class="bg-white shadow-lg sticky top-0 z-30 border-b border-gray-200">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <div class="flex items-center space-x-4">
                        <button onclick="toggleSidebar()" class="lg:hidden text-gray-600 hover:text-gray-900 transition-colors">
                            <i class="fas fa-bars text-2xl"></i>
                        </button>
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center shadow-md">
                                <i class="fas fa-user-md text-white text-2xl"></i>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Dentistas do Consultório</h1>
                                <p class="text-gray-600 text-sm">Gerencie sua equipe de profissionais</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <div class="p-4 sm:p-6 lg:p-8 bg-gray-50 min-h-screen">

        <?php if ($success): ?>
            <div class="animate-fade-in bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 rounded-xl p-6 mb-6 shadow-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-green-800 font-medium"><?php echo $success; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="animate-fade-in bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-500 rounded-xl p-6 mb-6 shadow-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-exclamation-circle text-red-600 text-2xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-red-800 font-medium"><?php echo $error; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Adicionar Dentista -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-200 hover:shadow-2xl transition-all duration-300">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-user-plus text-white text-xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900">Adicionar Profissional</h3>
                    </div>
                    
                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="action" value="adicionar_dentista">
                        
                        <div>
                            <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-user text-blue-500 mr-2"></i>
                                Nome Completo *
                            </label>
                            <input type="text" name="nome" required
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                placeholder="Dr(a). Nome Completo">
                        </div>
                        
                        <div>
                            <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-stethoscope text-blue-500 mr-2"></i>
                                Especialidade *
                            </label>
                            <select name="especialidade" required
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="">Selecione a especialidade</option>
                                <?php foreach ($especialidades as $key => $value): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-id-card text-blue-500 mr-2"></i>
                                CRO *
                            </label>
                            <input type="text" name="cro" required
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                placeholder="12345">
                        </div>
                        
                        <div>
                            <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                                <i class="fab fa-whatsapp text-blue-500 mr-2"></i>
                                Telefone / WhatsApp
                            </label>
                            <input type="text" name="telefone"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                placeholder="(13) 98765-4321">
                        </div>
                        
                        <div>
                            <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-file-alt text-blue-500 mr-2"></i>
                                Biografia
                            </label>
                            <textarea name="bio" rows="3"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"
                                placeholder="Conte sobre a experiência..."></textarea>
                        </div>
                        
                        <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white py-3.5 px-4 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl transition-all transform hover:scale-105">
                            <i class="fas fa-plus mr-2"></i>Adicionar à Equipe
                        </button>
                    </form>
                </div>
            </div>

            <!-- Lista de Dentistas -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-200">
                    <div class="bg-gradient-to-r from-blue-50 to-cyan-50 px-8 py-6 border-b border-gray-200">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-users text-white"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900">Equipe de Profissionais</h3>
                        </div>
                    </div>
                    
                    <div class="p-8">
                        <?php if (empty($dentistas_meu_consultorio)): ?>
                            <div class="text-center py-16">
                                <div class="w-24 h-24 bg-gradient-to-br from-blue-100 to-cyan-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <i class="fas fa-user-md text-5xl text-blue-400"></i>
                                </div>
                                <h4 class="text-xl font-bold text-gray-700 mb-2">Nenhum profissional cadastrado</h4>
                                <p class="text-gray-500 mb-1">Comece a montar sua equipe agora!</p>
                                <p class="text-sm text-gray-400">Use o formulário ao lado para adicionar dentistas.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-6">
                                <?php foreach ($dentistas_meu_consultorio as $dentista_consultorio): ?>
                                    <div class="bg-white border-2 border-gray-200 rounded-2xl p-6 hover:shadow-2xl hover:border-blue-300 transition-all duration-300 transform hover:-translate-y-1">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-5 mb-5">
                                                    <div class="relative">
                                                        <div class="w-20 h-20 rounded-2xl overflow-hidden border-4 border-white shadow-lg">
                                                            <img src="<?php echo Upload::getImageUrl($dentista_consultorio['foto'] ?? '', 'dentista_consultorio', $dentista_consultorio['nome']); ?>" 
                                                                 alt="Foto do dentista" 
                                                                 class="w-full h-full object-cover">
                                                        </div>
                                                        <?php if ($dentista_consultorio['ativo']): ?>
                                                        <div class="absolute -bottom-1 -right-1 w-7 h-7 bg-green-500 rounded-full border-4 border-white flex items-center justify-center">
                                                            <i class="fas fa-check text-white text-xs"></i>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex-1">
                                                        <h4 class="text-xl font-bold text-gray-900 mb-1">
                                                            <?php echo htmlspecialchars($dentista_consultorio['nome']); ?>
                                                        </h4>
                                                        <div class="flex items-center space-x-2 mb-2">
                                                            <span class="inline-flex items-center px-3 py-1 bg-gradient-to-r from-blue-100 to-cyan-100 text-blue-800 text-sm font-semibold rounded-full">
                                                                <i class="fas fa-stethoscope mr-2"></i>
                                                                <?php echo $especialidades[$dentista_consultorio['especialidade']] ?? $dentista_consultorio['especialidade']; ?>
                                                            </span>
                                                            <span class="inline-flex items-center px-3 py-1 <?php echo $dentista_consultorio['ativo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> text-xs font-bold rounded-full">
                                                                <i class="fas fa-circle text-xs mr-2"></i>
                                                                <?php echo $dentista_consultorio['ativo'] ? 'ATIVO' : 'INATIVO'; ?>
                                                            </span>
                                                        </div>
                                                        <p class="text-sm text-gray-600 flex items-center">
                                                            <i class="fas fa-id-card mr-2 text-blue-500"></i>
                                                            <span class="font-semibold">CRO:</span>
                                                            <span class="ml-1"><?php echo htmlspecialchars($dentista_consultorio['cro']); ?></span>
                                                        </p>
                                                        <?php if (!empty($dentista_consultorio['telefone'])): ?>
                                                        <a href="<?php echo Upload::getWhatsAppLink($dentista_consultorio['telefone']); ?>" target="_blank" class="text-sm text-green-600 hover:text-green-700 flex items-center mt-1 font-medium">
                                                            <i class="fab fa-whatsapp mr-2 text-lg"></i>
                                                            <?php echo htmlspecialchars($dentista_consultorio['telefone']); ?>
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($dentista_consultorio['bio'])): ?>
                                                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                                                        <p class="text-sm text-gray-700 leading-relaxed">
                                                            <i class="fas fa-quote-left text-blue-300 mr-2"></i>
                                                            <?php echo htmlspecialchars($dentista_consultorio['bio']); ?>
                                                            <i class="fas fa-quote-right text-blue-300 ml-2"></i>
                                                        </p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="ml-6 flex flex-col space-y-3">
                                                <!-- Upload de Foto -->
                                                <form method="POST" enctype="multipart/form-data" class="inline">
                                                    <input type="hidden" name="action" value="upload_foto">
                                                    <input type="hidden" name="dentista_id" value="<?php echo $dentista_consultorio['id']; ?>">
                                                    <input type="file" name="foto" id="foto_<?php echo $dentista_consultorio['id']; ?>" accept="image/*" 
                                                           class="hidden" onchange="this.form.submit()">
                                                    <label for="foto_<?php echo $dentista_consultorio['id']; ?>" 
                                                           class="flex items-center justify-center bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white px-4 py-2.5 rounded-xl text-sm font-bold cursor-pointer shadow-md hover:shadow-lg transition-all">
                                                        <i class="fas fa-camera mr-2"></i>Foto
                                                    </label>
                                                </form>
                                                
                                                <!-- Toggle Status -->
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="dentista_id" value="<?php echo $dentista_consultorio['id']; ?>">
                                                    <button type="submit" class="w-full flex items-center justify-center text-sm px-4 py-2.5 rounded-xl font-bold shadow-md hover:shadow-lg transition-all <?php echo $dentista_consultorio['ativo'] ? 'bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-white' : 'bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white'; ?>">
                                                        <i class="fas fa-<?php echo $dentista_consultorio['ativo'] ? 'pause' : 'play'; ?> mr-2"></i>
                                                        <?php echo $dentista_consultorio['ativo'] ? 'Desativar' : 'Ativar'; ?>
                                                    </button>
                                                </form>
                                                
                                                <!-- Excluir -->
                                                <form method="POST" class="inline" onsubmit="return confirm('⚠️ Tem certeza que deseja excluir este profissional?\n\nEsta ação não pode ser desfeita!')">
                                                    <input type="hidden" name="action" value="excluir_dentista">
                                                    <input type="hidden" name="dentista_id" value="<?php echo $dentista_consultorio['id']; ?>">
                                                    <button type="submit" class="w-full flex items-center justify-center text-sm px-4 py-2.5 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white rounded-xl font-bold shadow-md hover:shadow-lg transition-all">
                                                        <i class="fas fa-trash mr-2"></i>Excluir
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
        </div>
    </div>

    <style>
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in {
            animation: fade-in 0.5s ease-out;
        }
        
        /* Animação para os cards */
        @keyframes slide-up {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Efeito hover suave */
        .hover-lift {
            transition: all 0.3s ease;
        }
        
        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        /* Gradient animado */
        @keyframes gradient-shift {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        .gradient-animate {
            background-size: 200% 200%;
            animation: gradient-shift 3s ease infinite;
        }
    </style>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
        
        // Animação de entrada para os cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.space-y-6 > div');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
        
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
