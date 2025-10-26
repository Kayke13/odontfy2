<?php
session_start();
require_once 'auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    if (Auth::login($email, $password)) {
        // Se "lembrar de mim" estiver marcado, salvar cookie
        if ($remember) {
            setcookie('remember_email', $email, time() + (30 * 24 * 60 * 60), '/'); // 30 dias
        } else {
            // Remover cookie se existir
            setcookie('remember_email', '', time() - 3600, '/');
        }
        
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Email ou senha incorretos';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Odontfy</title>
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

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .animate-fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }

        .animate-slide-in {
            animation: slideIn 0.6s ease-out forwards;
        }

        .animate-pulse-slow {
            animation: pulse 2s ease-in-out infinite;
        }

        /* Background pattern */
        body {
            background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 50%, #BFDBFE 100%);
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(96, 165, 250, 0.1) 0%, transparent 50%);
        }

        /* Input focus effect */
        input:focus {
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        /* Glass effect */
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        /* Tooth icon animation */
        @keyframes wiggle {
            0%, 100% { transform: rotate(-3deg); }
            50% { transform: rotate(3deg); }
        }

        .tooth-icon:hover {
            animation: wiggle 0.5s ease-in-out;
        }
    </style>
</head>
<body class="min-h-screen font-inter antialiased">
    <div class="relative min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full z-10">
            <!-- Logo e Título -->
            <div class="text-center mb-8 animate-fade-in">
                <div class="inline-block mb-6">
                    <div class="w-24 h-24 bg-white rounded-3xl shadow-2xl flex items-center justify-center tooth-icon animate-pulse-slow">
                        <i class="fas fa-tooth text-blue-600 text-5xl"></i>
                    </div>
                </div>
                <h1 class="text-5xl font-bold text-gray-900 mb-3 drop-shadow-lg">
                    Odontfy
                </h1>
                <p class="text-gray-700 text-lg font-medium">
                    Sistema de Gestão Odontológica
                </p>
            </div>
            
            <!-- Card de Login -->
            <div class="glass-effect rounded-3xl shadow-2xl overflow-hidden animate-fade-in" style="animation-delay: 0.2s">
                <!-- Header do Card -->
                <div class="bg-gradient-to-r from-blue-600 to-cyan-600 px-8 py-6 text-center">
                    <h2 class="text-white text-2xl font-bold">
                        <i class="fas fa-user-circle mr-2"></i>Bem-vindo de volta!
                    </h2>
                    <p class="text-blue-100 text-sm mt-1">Entre com suas credenciais</p>
                </div>
                
                <!-- Conteúdo do Card -->
                <div class="p-8">
                    <?php if ($error): ?>
                        <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-lg p-4 animate-fade-in">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-red-800"><?php echo $error; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-6">
                        <!-- Campo Email -->
                        <div class="animate-slide-in">
                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                <i class="fas fa-envelope mr-2 text-blue-600"></i>Email
                            </label>
                            <div class="relative">
                                <input type="email" name="email" required
                                    class="w-full px-4 py-3 pl-12 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200"
                                    placeholder="seu@email.com"
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : (isset($_COOKIE['remember_email']) ? htmlspecialchars($_COOKIE['remember_email']) : ''); ?>">
                                <div class="absolute left-4 top-1/2 transform -translate-y-1/2">
                                    <i class="fas fa-at text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Campo Senha -->
                        <div class="animate-slide-in" style="animation-delay: 0.1s">
                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                <i class="fas fa-lock mr-2 text-blue-600"></i>Senha
                            </label>
                            <div class="relative">
                                <input type="password" name="password" id="password" required
                                    class="w-full px-4 py-3 pl-12 pr-12 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200"
                                    placeholder="••••••••">
                                <div class="absolute left-4 top-1/2 transform -translate-y-1/2">
                                    <i class="fas fa-key text-gray-400"></i>
                                </div>
                                <button type="button" id="togglePassword" 
                                    class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-blue-600 transition-colors duration-200">
                                    <i class="fas fa-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Lembrar-me -->
                        <div class="flex items-center animate-slide-in" style="animation-delay: 0.2s">
                            <input type="checkbox" name="remember" id="remember" 
                                class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="remember" class="ml-3 block text-sm font-medium text-gray-700">
                                <i class="fas fa-history mr-1 text-blue-600"></i>Lembrar de mim
                            </label>
                        </div>
                        
                        <!-- Botão de Login -->
                        <button type="submit" 
                            class="w-full bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white py-4 px-4 rounded-xl font-bold text-lg transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 animate-slide-in"
                            style="animation-delay: 0.3s">
                            <i class="fas fa-sign-in-alt mr-2"></i>Entrar no Sistema
                        </button>
                    </form>
                </div>
                
                <!-- Footer do Card -->
                <div class="bg-gray-50 px-8 py-6 border-t border-gray-100 text-center">
                    <p class="text-sm text-gray-600 mb-3">
                        Ainda não tem uma conta?
                    </p>
                    <a href="register.php" class="inline-flex items-center text-blue-600 hover:text-blue-700 font-semibold transition-colors duration-200">
                        <i class="fas fa-user-plus mr-2"></i>
                        Criar nova conta
                    </a>
                </div>
            </div>
            
            <!-- Link para Admin -->
            <div class="mt-6 text-center animate-fade-in" style="animation-delay: 0.4s">
                <a href="admin_login.php" class="inline-flex items-center text-gray-700 text-sm hover:text-gray-900 transition-colors duration-200">
                    <i class="fas fa-shield-alt mr-2"></i>
                    Acesso Administrativo
                </a>
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

        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        });

        // Marcar checkbox se houver cookie salvo
        document.addEventListener('DOMContentLoaded', function() {
            const emailField = document.querySelector('input[name="email"]');
            const rememberCheckbox = document.getElementById('remember');
            
            if (emailField.value && emailField.value.trim() !== '') {
                rememberCheckbox.checked = true;
            }
        });
    </script>
</body>
</html>
