<?php
session_start();
require_once 'config/admin_auth.php';

// Se já estiver logado, redirecionar para dashboard
if (AdminAuth::isLoggedIn()) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (AdminAuth::login($username, $password)) {
        header('Location: admin_dashboard.php');
        exit;
    } else {
        $error = 'Credenciais inválidas.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrador - Odontfy</title>
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
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .animate-fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }

        /* Background pattern */
        body {
            background-color: #f8fafc;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(99, 102, 241, 0.05) 0%, transparent 50%);
        }

        /* Input focus effect */
        input:focus {
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        /* Button hover effect */
        .login-btn {
            position: relative;
            overflow: hidden;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.5s ease;
        }

        .login-btn:hover::before {
            left: 100%;
        }
    </style>
</head>
<body class="min-h-screen font-inter antialiased">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <!-- Logo e Título -->
            <div class="text-center mb-8 animate-fade-in">
                <div class="mx-auto w-20 h-20 bg-blue-600 rounded-2xl flex items-center justify-center mb-6 shadow-2xl animate-float">
                    <i class="fas fa-shield-alt text-white text-3xl"></i>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-3">
                    Odontfy Admin
                </h1>
                <p class="text-gray-600 text-lg">
                    Painel de Administração
                </p>
                <div class="mt-4 flex items-center justify-center space-x-2 text-sm text-gray-500">
                    <i class="fas fa-lock text-xs"></i>
                    <span>Acesso Restrito</span>
                </div>
            </div>
            
            <!-- Card de Login -->
            <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden animate-fade-in" style="animation-delay: 0.2s">
                <!-- Header do Card -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6">
                    <h2 class="text-white text-xl font-bold text-center">
                        <i class="fas fa-key mr-2"></i>Login Administrativo
                    </h2>
                </div>
                
                <!-- Conteúdo do Card -->
                <div class="p-8">
                    <?php if ($error): ?>
                        <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-lg p-4 animate-fade-in">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-red-800"><?php echo $error; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-6">
                        <!-- Campo Usuário -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-user-shield mr-2 text-blue-600"></i>Usuário
                            </label>
                            <div class="relative">
                                <input type="text" name="username" required
                                    class="w-full px-4 py-3 pl-12 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200"
                                    placeholder="Digite seu usuário">
                                <div class="absolute left-4 top-1/2 transform -translate-y-1/2">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Campo Senha -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-lock mr-2 text-blue-600"></i>Senha
                            </label>
                            <div class="relative">
                                <input type="password" name="password" required
                                    class="w-full px-4 py-3 pl-12 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200"
                                    placeholder="Digite sua senha">
                                <div class="absolute left-4 top-1/2 transform -translate-y-1/2">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Botão de Login -->
                        <button type="submit" 
                            class="login-btn w-full bg-blue-600 hover:bg-blue-700 text-white py-4 px-4 rounded-xl font-bold text-lg transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            <i class="fas fa-sign-in-alt mr-2"></i>Entrar no Painel
                        </button>
                    </form>
                </div>
                
                <!-- Footer do Card -->
                <div class="bg-gray-50 px-8 py-4 border-t border-gray-100">
                    <div class="text-center">
                        <a href="index.php" class="inline-flex items-center text-gray-600 hover:text-blue-600 text-sm font-medium transition-colors duration-200">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Voltar para o Site
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Informações de Segurança -->
            <div class="mt-6 text-center animate-fade-in" style="animation-delay: 0.4s">
                <p class="text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Este é um ambiente seguro. Todas as ações são registradas.
                </p>
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
    </script>
</body>
</html>
