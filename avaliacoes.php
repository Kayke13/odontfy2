<?php
session_start();
require_once 'auth.php';
require_once 'config/database.php';
require_once 'config/upload.php';
require_once 'config/plano_status.php';

Auth::requireLogin();

$dentista = Auth::getCurrentUser();

// Verificar status do plano e aplicar restrições
$status_plano = PlanoStatus::verificarStatus($dentista);

// Aplicar restrições se necessário
if (!$status_plano['pode_usar_sistema']) {
    PlanoStatus::aplicarRestricoes($dentista);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliações - Odontfy</title>
    <link rel="icon" type="image/x-icon" href="assets/images/LOGO.ico">
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

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .float-animation {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes pulse-slow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .pulse-slow {
            animation: pulse-slow 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 font-inter min-h-screen">
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
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
                
                <a href="avaliacoes.php" class="menu-item active flex items-center px-4 py-3 text-white bg-blue-700 rounded-lg hover:bg-blue-500 transition-all group">
                    <i class="fas fa-star w-5 text-center mr-3 group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Avaliações</span>
                    <span class="ml-2 text-xs bg-green-500 text-white px-2 py-0.5 rounded-full font-semibold">Novo</span>
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
    
    <!-- Main Content -->
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
                                Avaliações
                            </h1>
                            <p class="text-sm text-gray-500">Sistema de avaliações e feedback</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="max-w-4xl mx-auto">
                <!-- Card de Em Desenvolvimento -->
                <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
                    <!-- Header com Gradiente -->
                    <div class="bg-gradient-to-r from-yellow-400 via-orange-400 to-yellow-500 p-8 text-center relative overflow-hidden">
                        <div class="absolute inset-0 opacity-20">
                            <div class="absolute top-10 left-10 w-20 h-20 bg-white rounded-full pulse-slow"></div>
                            <div class="absolute top-20 right-20 w-16 h-16 bg-white rounded-full pulse-slow" style="animation-delay: 0.5s;"></div>
                            <div class="absolute bottom-10 left-1/3 w-24 h-24 bg-white rounded-full pulse-slow" style="animation-delay: 1s;"></div>
                        </div>
                        
                        <div class="relative float-animation">
                            <i class="fas fa-star text-white text-8xl mb-4"></i>
                        </div>
                        <h2 class="text-4xl font-bold text-white mb-2">Em Desenvolvimento</h2>
                        <p class="text-white text-lg opacity-90">Estamos construindo algo incrível para você!</p>
                    </div>
                    
                    <!-- Body -->
                    <div class="p-8">
                        <div class="text-center mb-8">
                            <h3 class="text-2xl font-bold text-gray-900 mb-3">
                                Sistema de Avaliações
                            </h3>
                            <p class="text-gray-600 text-lg">
                                Em breve você poderá receber e gerenciar avaliações dos seus pacientes!
                            </p>
                        </div>
                        
                        <!-- Features Preview -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl p-6 border-2 border-blue-100">
                                <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mb-4">
                                    <i class="fas fa-star text-white text-xl"></i>
                                </div>
                                <h4 class="text-lg font-bold text-gray-900 mb-2">Avaliações de Pacientes</h4>
                                <p class="text-gray-600 text-sm">Receba feedback direto dos seus pacientes após cada consulta.</p>
                            </div>
                            
                            <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6 border-2 border-purple-100">
                                <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center mb-4">
                                    <i class="fas fa-chart-line text-white text-xl"></i>
                                </div>
                                <h4 class="text-lg font-bold text-gray-900 mb-2">Estatísticas Detalhadas</h4>
                                <p class="text-gray-600 text-sm">Acompanhe sua média de avaliações e evolução ao longo do tempo.</p>
                            </div>
                            
                            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border-2 border-green-100">
                                <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center mb-4">
                                    <i class="fas fa-comments text-white text-xl"></i>
                                </div>
                                <h4 class="text-lg font-bold text-gray-900 mb-2">Comentários e Feedback</h4>
                                <p class="text-gray-600 text-sm">Leia comentários detalhados e melhore seu atendimento.</p>
                            </div>
                            
                            <div class="bg-gradient-to-br from-orange-50 to-yellow-50 rounded-xl p-6 border-2 border-orange-100">
                                <div class="w-12 h-12 bg-orange-600 rounded-xl flex items-center justify-center mb-4">
                                    <i class="fas fa-trophy text-white text-xl"></i>
                                </div>
                                <h4 class="text-lg font-bold text-gray-900 mb-2">Página Pública</h4>
                                <p class="text-gray-600 text-sm">Suas avaliações serão exibidas na página pública de agendamento.</p>
                            </div>
                        </div>
                        
                        <!-- Info Box -->
                        <div class="bg-gradient-to-r from-blue-100 to-cyan-100 border-l-4 border-blue-600 rounded-lg p-6">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-info-circle text-blue-600 text-3xl"></i>
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-blue-900 mb-2">Funcionalidade em Desenvolvimento</h4>
                                    <p class="text-blue-800 mb-3">
                                        Nossa equipe está trabalhando para trazer o melhor sistema de avaliações para você. 
                                        Esta funcionalidade permitirá que seus pacientes deixem feedback após cada consulta, 
                                        ajudando você a melhorar continuamente seu atendimento.
                                    </p>
                                    <div class="flex items-center gap-2 text-blue-700 font-semibold">
                                        <i class="fas fa-clock"></i>
                                        <span>Previsão de lançamento: Em breve</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Button -->
                        <div class="mt-8 text-center">
                            <a href="dashboard.php" class="inline-flex items-center gap-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white px-8 py-4 rounded-xl font-bold text-lg transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                                <i class="fas fa-arrow-left"></i>
                                <span>Voltar ao Dashboard</span>
                            </a>
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
    </script>
</body>
</html>

