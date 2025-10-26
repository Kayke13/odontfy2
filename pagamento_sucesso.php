<?php
session_start();
require_once 'auth.php';
require_once 'config/database.php';
require_once 'config/planos.php';

Auth::requireLogin();

$dentista = Auth::getCurrentUser();
$plano = $_GET['plano'] ?? '';
$payment_id = $_GET['payment_id'] ?? '';
$status = $_GET['status'] ?? '';

// Se o pagamento foi aprovado, ativar o plano
if ($status === 'approved' && !empty($plano)) {
    $dias = PlanosConfig::getDuracaoDias($plano);
    $data_fim = date('Y-m-d', strtotime("+{$dias} days"));
    
    Database::update('dentistas', $dentista['id'], [
        'assinatura_ativa' => true,
        'plano' => $plano,
        'plano_fim' => $data_fim,
        'teste_inicio' => null,
        'teste_fim' => null,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Aprovado - Odontfy</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/LOGO.ico">
        <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @keyframes checkmark {
            0% { stroke-dashoffset: 100; }
            100% { stroke-dashoffset: 0; }
        }
        @keyframes scaleIn {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }
        .checkmark-circle {
            animation: scaleIn 0.5s ease-out;
        }
        .checkmark-check {
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            animation: checkmark 0.8s ease-out 0.3s forwards;
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
<body class="bg-gradient-to-br from-green-50 to-emerald-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <div class="bg-white rounded-3xl shadow-2xl p-12 text-center">
            <!-- Ícone de Sucesso Animado -->
            <div class="mb-8 flex justify-center">
                <svg class="checkmark-circle" width="120" height="120" viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r="54" fill="#10b981" opacity="0.1"/>
                    <circle cx="60" cy="60" r="50" fill="none" stroke="#10b981" stroke-width="4"/>
                    <path class="checkmark-check" d="M35 60 L50 75 L85 40" fill="none" stroke="#10b981" stroke-width="6" stroke-linecap="round"/>
                </svg>
            </div>
            
            <!-- Título -->
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                🎉 Pagamento Aprovado!
            </h1>
            
            <p class="text-xl text-gray-700 mb-8">
                Seu plano <strong class="text-green-600"><?php echo ucfirst($plano); ?></strong> foi ativado com sucesso!
            </p>
            
            <!-- Detalhes -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl p-6 mb-8">
                <div class="space-y-3">
                    <?php if ($payment_id): ?>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">ID do Pagamento:</span>
                        <span class="font-mono text-sm text-gray-800"><?php echo htmlspecialchars($payment_id); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">✅ Aprovado</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Plano:</span>
                        <span class="font-semibold text-gray-800"><?php echo ucfirst($plano); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Mensagem Adicional -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-8 text-left">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-500 text-xl mr-3 mt-1"></i>
                    <div>
                        <p class="text-blue-800 font-semibold mb-1">Bem-vindo ao Odontfy!</p>
                        <p class="text-blue-700 text-sm">
                            Agora você tem acesso a todos os recursos do plano <?php echo ucfirst($plano); ?>. 
                            Aproveite para gerenciar seus agendamentos, clientes e muito mais!
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Botões de Ação -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="dashboard.php" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white px-8 py-4 rounded-xl font-bold text-lg transition-all transform hover:scale-105 shadow-lg">
                    <i class="fas fa-home mr-2"></i>Ir para Dashboard
                </a>
                <a href="planos.php" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-8 py-4 rounded-xl font-bold text-lg transition-all">
                    <i class="fas fa-crown mr-2"></i>Ver Meu Plano
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-redirecionar após 10 segundos
        setTimeout(() => {
            window.location.href = 'dashboard.php';
        }, 10000);
    </script>
</body>
</html>

