<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Odontfy - Agendamento Odontológico Online</title>
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
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-cyan-50 min-h-screen font-inter">
    <!-- Header -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <a href="index.php" class="text-2xl font-bold text-blue-600 hover:text-blue-700 transition-colors duration-200">
                            <i class="fas fa-tooth mr-2"></i>Odontfy
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if (isset($_SESSION['dentista_id'])): ?>
                        <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium">
                            Sair
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                            Entrar
                        </a>
                        <a href="register.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium">
                            Cadastrar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="text-center">
            <h1 class="text-4xl md:text-6xl font-bold text-gray-900 mb-6">
                Agendamento Odontológico
                <span class="text-blue-600">Online</span>
            </h1>
            <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
                Simplifique o agendamento da sua clínica. Seus pacientes agendam online, 
                você recebe notificações e gerencia tudo em um só lugar.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="register.php" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-lg text-lg font-semibold transition duration-300">
                    <i class="fas fa-rocket mr-2"></i>Começar Teste Grátis
                </a>
                <a href="#features" class="border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white px-8 py-4 rounded-lg text-lg font-semibold transition duration-300">
                    <i class="fas fa-info-circle mr-2"></i>Saiba Mais
                </a>
            </div>
            <p class="text-sm text-gray-500 mt-4">
                <i class="fas fa-gift mr-1"></i>7 dias grátis • Sem cartão de crédito
            </p>
        </div>
    </div>

    <!-- Features Section -->
    <div id="features" class="bg-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">
                    Tudo que você precisa para agendamentos online
                </h2>
                <p class="text-xl text-gray-600">
                    Solução completa para consultórios odontológicos
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center p-6 rounded-lg bg-blue-50">
                    <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-calendar-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Agendamento 24/7</h3>
                    <p class="text-gray-600">
                        Seus pacientes agendam a qualquer hora, mesmo quando você não está online.
                    </p>
                </div>

                <div class="text-center p-6 rounded-lg bg-green-50">
                    <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-bell text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Lembretes Automáticos</h3>
                    <p class="text-gray-600">
                        Enviamos lembretes por email e WhatsApp para reduzir faltas.
                    </p>
                </div>

                <div class="text-center p-6 rounded-lg bg-purple-50">
                    <div class="w-16 h-16 bg-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-chart-line text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Relatórios Completos</h3>
                    <p class="text-gray-600">
                        Acompanhe seus agendamentos e performance da clínica.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing Section -->
    <div class="bg-gray-50 py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">
                    Preço Simples e Transparente
                </h2>
                <p class="text-xl text-gray-600">
                    Sem pegadinhas, sem taxas ocultas
                </p>
            </div>

            <div class="max-w-md mx-auto">
                <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Plano Mensal</h3>
                        <div class="text-4xl font-bold text-blue-600 mb-2">R$ 49,90</div>
                        <p class="text-gray-600">por mês</p>
                    </div>
                    
                    <ul class="text-left space-y-3 mb-8">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span>Agendamento online 24/7</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span>Lembretes automáticos</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span>Link personalizado</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span>Relatórios completos</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span>Suporte por email</span>
                        </li>
                    </ul>

                    <div class="bg-green-100 text-green-800 px-4 py-2 rounded-lg mb-6">
                        <i class="fas fa-gift mr-2"></i>7 dias grátis para testar
                    </div>

                    <a href="register.php" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-semibold transition duration-300 block">
                        Começar Teste Grátis
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h3 class="text-2xl font-bold mb-4">
                    <i class="fas fa-tooth mr-2"></i>Odontfy
                </h3>
                <p class="text-gray-400 mb-4">
                    Simplificando agendamentos odontológicos
                </p>
                <p class="text-sm text-gray-500">
                    © 2024 Odontfy. Todos os direitos reservados.
                </p>
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
    </script>
</body>
</html>
