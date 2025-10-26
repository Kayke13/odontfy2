<?php
session_start();
require_once 'auth.php';

Auth::requireLogin();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Pendente - Odontfy</title>
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
    </style>
</head>
<body class="bg-gradient-to-br from-yellow-50 to-orange-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <div class="bg-white rounded-3xl shadow-2xl p-12 text-center">
            <!-- Ícone de Pendente -->
            <div class="mb-8 flex justify-center">
                <div class="bg-yellow-100 rounded-full p-8">
                    <i class="fas fa-clock text-yellow-600 text-6xl"></i>
                </div>
            </div>
            
            <!-- Título -->
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                ⏳ Pagamento Pendente
            </h1>
            
            <p class="text-xl text-gray-700 mb-8">
                Seu pagamento está sendo processado e aguarda confirmação.
            </p>
            
            <!-- Informações -->
            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-2xl p-6 mb-8 text-left">
                <div class="space-y-4">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-yellow-600 text-xl mr-3 mt-1"></i>
                        <div>
                            <p class="text-gray-800 font-semibold mb-2">O que acontece agora?</p>
                            <ul class="text-gray-700 text-sm space-y-2">
                                <li>• Se você pagou com <strong>boleto</strong>, aguarde a confirmação bancária (1-2 dias úteis)</li>
                                <li>• Se você pagou com <strong>cartão</strong>, estamos verificando com a operadora</li>
                                <li>• Você receberá uma notificação quando o pagamento for aprovado</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-8 text-left">
                <div class="flex items-start">
                    <i class="fas fa-bell text-blue-500 text-xl mr-3 mt-1"></i>
                    <div>
                        <p class="text-blue-800 font-semibold mb-1">Fique Tranquilo!</p>
                        <p class="text-blue-700 text-sm">
                            Assim que o pagamento for confirmado, seu plano será ativado automaticamente 
                            e você receberá um e-mail de confirmação.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Botões -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="dashboard.php" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white px-8 py-4 rounded-xl font-bold text-lg transition-all transform hover:scale-105 shadow-lg">
                    <i class="fas fa-home mr-2"></i>Ir para Dashboard
                </a>
                <a href="planos.php" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-8 py-4 rounded-xl font-bold text-lg transition-all">
                    <i class="fas fa-crown mr-2"></i>Ver Planos
                </a>
            </div>
        </div>
    </div>
</body>
</html>

