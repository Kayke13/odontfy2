<?php
session_start();
require_once 'auth.php';
require_once 'config/database.php';
require_once 'config/planos.php';
require_once 'config/mercadopago.php';

Auth::requireLogin();

$dentista = Auth::getCurrentUser();

// Carregar configurações dos planos
$planos = PlanosConfig::getPlanos();

// Verificar se foi selecionado um plano
$plano_selecionado = $_GET['plano'] ?? '';
if (!isset($planos[$plano_selecionado])) {
    header('Location: planos.php');
    exit;
}

$plano = $planos[$plano_selecionado];

$success = '';
$error = '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento - Odontfy</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/LOGO.ico">
        <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .menu-item {
            transition: all 0.3s ease;
        }
        .menu-item:hover {
            background: rgba(59, 130, 246, 0.1);
            border-left: 4px solid #3b82f6;
        }
        .menu-item.active {
            background: rgba(59, 130, 246, 0.1);
            border-left: 4px solid #3b82f6;
            color: #3b82f6;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .animate-slide-in {
            animation: slideInRight 0.6s ease-out;
        }
        .payment-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .payment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
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
<body class="bg-gradient-to-br from-gray-50 to-blue-50 font-inter">
    <!-- Sidebar -->
    <div id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-white shadow-2xl transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-50">
        <div class="p-6 border-b border-gray-200">
            <a href="dashboard.php" class="flex items-center space-x-3">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-2 rounded-lg">
                    <i class="fas fa-tooth text-white text-2xl"></i>
                </div>
                <span class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">Odontfy</span>
            </a>
        </div>
        
        <nav class="mt-6 px-4">
            <a href="dashboard.php" class="menu-item flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg mb-2">
                <i class="fas fa-home w-5"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="agendamentos.php" class="menu-item flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg mb-2">
                <i class="fas fa-calendar-alt w-5"></i>
                <span>Agendamentos</span>
            </a>
            
            <a href="clientes.php" class="menu-item flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg mb-2">
                <i class="fas fa-users w-5"></i>
                <span>Clientes</span>
            </a>
            
            <a href="dentistas_consultorio.php" class="menu-item flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg mb-2">
                <i class="fas fa-user-md w-5"></i>
                <span>Dentistas</span>
            </a>
            
            <a href="servicos.php" class="menu-item flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg mb-2">
                <i class="fas fa-briefcase w-5"></i>
                <span>Serviços</span>
            </a>
            
            <a href="financeiro.php" class="menu-item flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg mb-2">
                <i class="fas fa-dollar-sign w-5"></i>
                <span>Financeiro</span>
            </a>
            
            <a href="planos.php" class="menu-item active flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg mb-2">
                <i class="fas fa-crown w-5"></i>
                <span>Planos</span>
            </a>
            
            <a href="configuracoes.php" class="menu-item flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg mb-2">
                <i class="fas fa-cog w-5"></i>
                <span>Configurações</span>
            </a>
            
            <a href="perfil_dentista.php" class="menu-item flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg mb-2">
                <i class="fas fa-user-circle w-5"></i>
                <span>Meu Perfil</span>
            </a>
            
            <a href="logout.php" class="menu-item flex items-center space-x-3 px-4 py-3 text-red-600 rounded-lg mb-2 mt-8">
                <i class="fas fa-sign-out-alt w-5"></i>
                <span>Sair</span>
            </a>
        </nav>
    </div>

    <!-- Mobile menu button -->
    <button onclick="toggleSidebar()" class="lg:hidden fixed top-4 left-4 z-50 bg-blue-600 text-white p-3 rounded-lg shadow-lg">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
    <!-- Header -->
        <header class="bg-white shadow-md sticky top-0 z-40">
            <div class="px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Finalizar Pagamento</h1>
                        <p class="text-sm text-gray-600 mt-1">Complete seu pagamento para ativar o plano</p>
                    </div>
                    <a href="planos.php" class="flex items-center space-x-2 text-gray-600 hover:text-blue-600 transition-colors">
                        <i class="fas fa-arrow-left"></i>
                        <span>Voltar</span>
                    </a>
                </div>
            </div>
        </header>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($success): ?>
            <div class="bg-gradient-to-r from-green-50 to-green-100 border-l-4 border-green-500 px-6 py-4 rounded-lg mb-6 animate-fade-in shadow-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-green-800 font-semibold"><?php echo $success; ?></p>
                        <p class="text-green-700 text-sm mt-1">Você será redirecionado automaticamente...</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-500 px-6 py-4 rounded-lg mb-6 animate-fade-in shadow-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-red-800 font-semibold"><?php echo $error; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Resumo do Plano -->
            <div class="payment-card bg-white rounded-2xl shadow-xl p-8 animate-fade-in">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="bg-gradient-to-br from-blue-500 to-purple-600 p-3 rounded-xl">
                        <i class="fas fa-crown text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900">Resumo do Plano</h3>
                </div>
                
                <div class="bg-gradient-to-br from-blue-50 to-purple-50 border-2 border-blue-200 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-2xl font-bold text-gray-900"><?php echo $plano['nome']; ?></h4>
                        <span class="bg-blue-600 text-white px-3 py-1 rounded-full text-xs font-bold uppercase">Popular</span>
                    </div>
                    <p class="text-gray-700 mb-6"><?php echo $plano['descricao']; ?></p>
                    
                    <div class="bg-white rounded-lg p-4 mb-6 shadow-sm">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 text-sm">Valor total:</span>
                            <div class="text-right">
                                <span class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                                    R$ <?php echo number_format($plano['preco'], 2, ',', '.'); ?>
                                </span>
                                <span class="text-gray-500 text-sm ml-1">
                            <?php 
                                    $periodo = $plano_selecionado === 'mensal' ? '/mês' : ($plano_selecionado === 'semestral' ? '/6 meses' : '/ano');
                                    echo $periodo;
                            ?>
                        </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <h5 class="font-semibold text-gray-900 text-sm uppercase tracking-wide">O que está incluído:</h5>
                        <?php foreach ($plano['recursos'] as $recurso): ?>
                            <div class="flex items-start space-x-3 p-2 rounded-lg hover:bg-white transition-colors">
                                <div class="flex-shrink-0 mt-0.5">
                                    <i class="fas fa-check-circle text-green-500 text-lg"></i>
                                </div>
                                <span class="text-gray-700 text-sm"><?php echo $recurso; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Formulário de Pagamento -->
            <div class="payment-card bg-white rounded-2xl shadow-xl p-8 animate-slide-in">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="bg-gradient-to-br from-green-500 to-blue-600 p-3 rounded-xl">
                        <i class="fas fa-credit-card text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900">Dados de Pagamento</h3>
                </div>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="processar_pagamento">
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-wallet mr-2 text-blue-500"></i>Forma de Pagamento
                        </label>
                        <select name="forma_pagamento" required
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            <option value="">Selecione uma forma de pagamento</option>
                            <option value="cartao">💳 Cartão de Crédito (até 12x)</option>
                            <option value="pix">📱 PIX - Aprovação Instantânea</option>
                        </select>
                    </div>
                    
                    <div id="dados-cartao" class="space-y-4 hidden">
                        <div class="bg-gradient-to-br from-blue-50 to-purple-50 border-2 border-blue-200 rounded-xl p-6">
                            <div class="flex items-center space-x-3 mb-3">
                                <div class="bg-blue-500 p-2 rounded-lg">
                                    <i class="fas fa-shield-alt text-white text-xl"></i>
                        </div>
                                <h4 class="font-bold text-blue-800 text-lg">Pagamento 100% Seguro</h4>
                            </div>
                            <p class="text-blue-700 text-sm leading-relaxed mb-4">
                                Ao clicar em "Finalizar Pagamento", você será redirecionado para a página segura do Mercado Pago 
                                onde poderá preencher os dados do seu cartão com total segurança.
                            </p>
                            <div class="flex items-center space-x-4 text-xs text-blue-600">
                                <div class="flex items-center">
                                    <i class="fas fa-lock mr-1"></i>
                                    <span>SSL Seguro</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    <span>Todos os cartões</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-credit-card mr-1"></i>
                                    <span>Parcelamento</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="dados-pix" class="space-y-4 hidden">
                        <div class="bg-gradient-to-br from-blue-50 to-cyan-50 border-2 border-blue-200 rounded-xl p-6">
                            <div class="flex items-center space-x-3 mb-3">
                                <div class="bg-blue-500 p-2 rounded-lg">
                                    <i class="fas fa-qrcode text-white text-xl"></i>
                                </div>
                                <h4 class="font-bold text-blue-800 text-lg">PIX - Pagamento Instantâneo</h4>
                            </div>
                            <p class="text-blue-700 text-sm leading-relaxed">
                                Após confirmar o pagamento, você receberá um QR Code e um código PIX copia-e-cola para finalizar a transação. 
                                <span class="font-semibold">Seu plano será ativado automaticamente após a confirmação!</span>
                            </p>
                        </div>
                    </div>
                    
                    
                    <div class="border-t-2 border-gray-100 pt-6 mt-6">
                        <div class="bg-gradient-to-r from-gray-50 to-blue-50 rounded-xl p-6 mb-6">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold text-gray-700">Total a pagar:</span>
                                <span class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                                    R$ <?php echo number_format($plano['preco'], 2, ',', '.'); ?>
                                </span>
                            </div>
                        </div>
                        
                        <button type="button" onclick="processarPagamento()" id="btn-finalizar" class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white py-4 px-6 rounded-xl font-bold text-lg transition-all transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <i class="fas fa-lock mr-2"></i>Finalizar Pagamento Seguro
                        </button>
                        <p class="text-center text-xs text-gray-500 mt-3">
                            <i class="fas fa-shield-alt mr-1"></i>Pagamento 100% seguro e criptografado
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <!-- Informações de Segurança -->
        <div class="mt-8 bg-gradient-to-r from-green-50 to-blue-50 rounded-2xl shadow-lg p-8 border-2 border-green-200">
            <div class="flex items-center space-x-3 mb-6">
                <div class="bg-gradient-to-br from-green-500 to-green-600 p-3 rounded-xl">
                    <i class="fas fa-shield-check text-white text-2xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900">Segurança e Privacidade</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl p-5 shadow-md hover:shadow-lg transition-shadow">
                    <div class="flex items-start space-x-3">
                        <div class="bg-green-100 p-3 rounded-lg">
                            <i class="fas fa-shield-alt text-green-600 text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">100% Seguro</h4>
                            <p class="text-sm text-gray-600">Ambiente protegido com certificado SSL</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-5 shadow-md hover:shadow-lg transition-shadow">
                    <div class="flex items-start space-x-3">
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <i class="fas fa-lock text-blue-600 text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Dados Criptografados</h4>
                            <p class="text-sm text-gray-600">Suas informações estão seguras</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-5 shadow-md hover:shadow-lg transition-shadow">
                    <div class="flex items-start space-x-3">
                        <div class="bg-purple-100 p-3 rounded-lg">
                            <i class="fas fa-undo text-purple-600 text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Flexível</h4>
                            <p class="text-sm text-gray-600">Cancele a qualquer momento</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Modal PIX -->
    <div id="modal-pix" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 animate-fade-in">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-6 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="bg-white p-2 rounded-lg">
                            <i class="fas fa-qrcode text-blue-600 text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-white">Pagamento PIX</h3>
                    </div>
                    <button onclick="fecharModalPix()" class="text-white hover:text-gray-200 transition-colors">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-8">
                <div class="text-center mb-6">
                    <div id="qr-code-container" class="bg-white p-4 rounded-xl inline-block border-4 border-blue-200 mb-4"></div>
                    <p class="text-gray-700 font-semibold mb-2">Escaneie o QR Code com seu app de pagamento</p>
                    <p class="text-sm text-gray-600">Ou use o código PIX copia-e-cola abaixo</p>
                </div>
                
                <div class="bg-gray-50 rounded-xl p-4 mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Código PIX:</label>
                    <div class="flex items-center space-x-2">
                        <input type="text" id="pix-code" readonly class="flex-1 px-4 py-3 border-2 border-gray-200 rounded-lg text-sm font-mono bg-white">
                        <button onclick="copiarCodigoPix()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg transition-colors">
                            <i class="fas fa-copy mr-2"></i>Copiar
                        </button>
                    </div>
                </div>
                
                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-clock text-yellow-600 text-xl mr-3 mt-1"></i>
                        <div>
                            <p class="text-yellow-800 font-semibold">Aguardando pagamento...</p>
                            <p class="text-yellow-700 text-sm mt-1">Seu plano será ativado automaticamente após a confirmação do pagamento.</p>
                        </div>
                    </div>
                </div>
                
                <div class="flex space-x-3">
                    <button onclick="verificarPagamento()" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-3 px-6 rounded-xl font-bold transition-colors">
                        <i class="fas fa-check-circle mr-2"></i>Já Paguei
                    </button>
                    <button onclick="fecharModalPix()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-3 px-6 rounded-xl font-bold transition-colors">
                        <i class="fas fa-times mr-2"></i>Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- Notificações Toast -->
    <div id="toast-container" class="fixed top-4 right-4 z-[100] space-y-3" style="max-width: 400px;"></div>
    
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

        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
        }

        // Mostrar/ocultar campos baseado na forma de pagamento
        document.querySelector('select[name="forma_pagamento"]').addEventListener('change', function() {
            const forma = this.value;
            const cartao = document.getElementById('dados-cartao');
            const pix = document.getElementById('dados-pix');
            
            // Ocultar todos com animação
            cartao.classList.add('hidden');
            pix.classList.add('hidden');
            
            // Mostrar o selecionado com animação
            setTimeout(() => {
                if (forma === 'cartao') {
                    cartao.classList.remove('hidden');
                } else if (forma === 'pix') {
                    pix.classList.remove('hidden');
                }
            }, 100);
        });

        // Nota: Campos de cartão removidos - agora usamos Checkout Pro do Mercado Pago

        // Variáveis globais
        let currentPaymentId = null;
        let checkPaymentInterval = null;

        // Função para criar notificação toast personalizada
        function showToast(message, type = 'success', duration = 5000) {
            const container = document.getElementById('toast-container');
            const toastId = 'toast-' + Date.now();
            
            // Definir cores e ícones baseados no tipo
            const types = {
                success: {
                    bg: 'bg-gradient-to-r from-green-500 to-emerald-600',
                    icon: 'fa-check-circle',
                    iconBg: 'bg-green-100',
                    iconColor: 'text-green-600'
                },
                error: {
                    bg: 'bg-gradient-to-r from-red-500 to-rose-600',
                    icon: 'fa-exclamation-circle',
                    iconBg: 'bg-red-100',
                    iconColor: 'text-red-600'
                },
                warning: {
                    bg: 'bg-gradient-to-r from-yellow-500 to-orange-600',
                    icon: 'fa-exclamation-triangle',
                    iconBg: 'bg-yellow-100',
                    iconColor: 'text-yellow-600'
                },
                info: {
                    bg: 'bg-gradient-to-r from-blue-500 to-indigo-600',
                    icon: 'fa-info-circle',
                    iconBg: 'bg-blue-100',
                    iconColor: 'text-blue-600'
                }
            };
            
            const config = types[type] || types.info;
            
            const toast = document.createElement('div');
            toast.id = toastId;
            toast.className = 'transform transition-all duration-300 ease-in-out';
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(400px)';
            
            toast.innerHTML = `
                <div class="${config.bg} rounded-xl shadow-2xl overflow-hidden">
                    <div class="p-4 flex items-start space-x-3">
                        <div class="${config.iconBg} p-2 rounded-lg">
                            <i class="fas ${config.icon} ${config.iconColor} text-xl"></i>
                        </div>
                        <div class="flex-1 pt-1">
                            <p class="text-white font-semibold text-sm leading-relaxed">${message}</p>
                        </div>
                        <button onclick="closeToast('${toastId}')" class="text-white hover:text-gray-200 transition-colors ml-2">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="h-1 bg-white bg-opacity-30">
                        <div class="h-full bg-white progress-bar" style="animation: progress ${duration}ms linear;"></div>
                    </div>
                </div>
            `;
            
            container.appendChild(toast);
            
            // Animar entrada
            setTimeout(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateX(0)';
            }, 10);
            
            // Auto remover após duração
            setTimeout(() => {
                closeToast(toastId);
            }, duration);
        }
        
        // Função para fechar toast
        function closeToast(toastId) {
            const toast = document.getElementById(toastId);
            if (toast) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }
        }
        
        // Adicionar CSS para animação da barra de progresso
        const style = document.createElement('style');
        style.textContent = `
            @keyframes progress {
                from { width: 100%; }
                to { width: 0%; }
            }
        `;
        document.head.appendChild(style);

        // Processar pagamento
        async function processarPagamento() {
            const forma_pagamento = document.querySelector('select[name="forma_pagamento"]').value;
            
            if (!forma_pagamento) {
                showToast('Por favor, selecione uma forma de pagamento.', 'warning');
                return;
            }

            const button = document.getElementById('btn-finalizar');
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processando...';
            button.disabled = true;

            try {
                if (forma_pagamento === 'cartao') {
                    await processarPagamentoCartao();
                } else if (forma_pagamento === 'pix') {
                    await processarPagamentoPix();
                }
            } catch (error) {
                console.error('Erro:', error);
                showToast('Erro ao processar pagamento. Tente novamente.', 'error', 7000);
                button.innerHTML = '<i class="fas fa-lock mr-2"></i>Finalizar Pagamento Seguro';
                button.disabled = false;
            }
        }

        // Processar pagamento com cartão (usando Checkout Pro - Link de Pagamento)
        async function processarPagamentoCartao() {
            try {
                showToast('Gerando link de pagamento seguro...', 'info', 2000);
                
                // Criar preferência de pagamento (Checkout Pro)
                const response = await fetch('api/mercadopago_create_preference.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        plano: '<?php echo $plano_selecionado; ?>',
                        valor: <?php echo $plano['preco']; ?>
                    })
                });

                const data = await response.json();
                console.log('Resposta da preferência:', data);

                if (data.success && data.init_point) {
                    showToast('✅ Redirecionando para página de pagamento...', 'success', 2000);
                    
                    // Redirecionar para a página de pagamento do Mercado Pago
                    setTimeout(() => {
                        window.location.href = data.init_point;
                    }, 1500);
                } else {
                    throw new Error(data.details || data.error || 'Erro ao criar link de pagamento');
                }
            } catch (error) {
                console.error('Erro ao processar cartão:', error);
                showToast('Erro: ' + error.message, 'error', 7000);
                const button = document.getElementById('btn-finalizar');
                button.innerHTML = '<i class="fas fa-lock mr-2"></i>Finalizar Pagamento Seguro';
                button.disabled = false;
            }
        }

        // Processar pagamento PIX
        async function processarPagamentoPix() {
            try {
                const response = await fetch('api/mercadopago_create_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        plano: '<?php echo $plano_selecionado; ?>',
                        forma_pagamento: 'pix',
                        valor: <?php echo $plano['preco']; ?>
                    })
                });

                const data = await response.json();
                console.log('Resposta PIX:', data);

                if (data.success) {
                    currentPaymentId = data.payment_id;
                    mostrarModalPix(data.pix);
                } else {
                    showToast('Erro: ' + (data.details || data.error), 'error', 7000);
                    const button = document.getElementById('btn-finalizar');
                    button.innerHTML = '<i class="fas fa-lock mr-2"></i>Finalizar Pagamento Seguro';
                    button.disabled = false;
                }
            } catch (error) {
                console.error('Erro ao processar PIX:', error);
                showToast('Erro ao processar PIX: ' + error.message, 'error', 7000);
                const button = document.getElementById('btn-finalizar');
                button.innerHTML = '<i class="fas fa-lock mr-2"></i>Finalizar Pagamento Seguro';
                button.disabled = false;
            }
        }


        // Mostrar modal PIX
        function mostrarModalPix(pixData) {
            const modal = document.getElementById('modal-pix');
            const qrContainer = document.getElementById('qr-code-container');
            const pixCodeInput = document.getElementById('pix-code');

            // Exibir QR Code
            if (pixData.qr_code_base64) {
                qrContainer.innerHTML = '<img src="data:image/png;base64,' + pixData.qr_code_base64 + '" alt="QR Code PIX" class="w-64 h-64">';
            } else {
                qrContainer.innerHTML = '<div class="text-gray-600">QR Code não disponível</div>';
            }

            // Exibir código copia-e-cola
            pixCodeInput.value = pixData.qr_code || '';

            modal.style.display = 'flex';
            modal.classList.remove('hidden');

            // Iniciar verificação automática do pagamento a cada 5 segundos
            checkPaymentInterval = setInterval(verificarPagamento, 5000);
        }

        // Fechar modal PIX
        function fecharModalPix() {
            const modal = document.getElementById('modal-pix');
            modal.style.display = 'none';
            modal.classList.add('hidden');
            
            if (checkPaymentInterval) {
                clearInterval(checkPaymentInterval);
                checkPaymentInterval = null;
            }
        }

        // Copiar código PIX
        function copiarCodigoPix() {
            const pixCodeInput = document.getElementById('pix-code');
            pixCodeInput.select();
            document.execCommand('copy');
            
            showToast('📋 Código PIX copiado! Cole no seu app de pagamento.', 'success', 3000);
        }

        // Verificar status do pagamento
        async function verificarPagamento() {
            if (!currentPaymentId) return;

            try {
                const response = await fetch('api/mercadopago_check_payment.php?payment_id=' + currentPaymentId);
                const data = await response.json();

                if (data.success) {
                    if (data.approved) {
                        if (checkPaymentInterval) {
                            clearInterval(checkPaymentInterval);
                        }
                        
                        // Fechar modal PIX
                        fecharModalPix();
                        
                        // Mostrar notificação de sucesso
                        showToast('🎉 Pagamento PIX aprovado! Seu plano foi ativado com sucesso!', 'success', 3000);
                        
                        // Redirecionar após 2 segundos
                        setTimeout(() => {
                            window.location.href = 'planos.php?success=1';
                        }, 2000);
                    }
                }
            } catch (error) {
                console.error('Erro ao verificar pagamento:', error);
            }
        }

    </script>

</body>
</html>
