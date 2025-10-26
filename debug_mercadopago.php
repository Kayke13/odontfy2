<?php
session_start();
require_once 'auth.php';
require_once 'config/database.php';
require_once 'config/mercadopago.php';

Auth::requireLogin();
$dentista = Auth::getCurrentUser();

// Testar conexão com a API
$test_result = null;
$test_error = null;

if (isset($_GET['test'])) {
    $access_token = MercadoPagoConfig::getAccessToken();
    $api_url = MercadoPagoConfig::getApiUrl() . '/v1/payment_methods';
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        $test_error = "Erro cURL: " . $curl_error;
    } elseif ($http_code >= 200 && $http_code < 300) {
        $test_result = "✅ Conexão OK! API respondeu com código " . $http_code;
    } else {
        $test_error = "❌ Erro HTTP " . $http_code . ": " . $response;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Mercado Pago - Odontfy</title>
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
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">
                <i class="fas fa-bug text-red-600 mr-3"></i>Debug Mercado Pago
            </h1>
            
            <!-- Teste de Conexão -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">1. Teste de Conexão</h2>
                <a href="?test=1" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg inline-block">
                    <i class="fas fa-sync-alt mr-2"></i>Testar Conexão com API
                </a>
                
                <?php if ($test_result): ?>
                    <div class="mt-4 bg-green-50 border border-green-200 rounded-lg p-4">
                        <p class="text-green-800"><?php echo $test_result; ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($test_error): ?>
                    <div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-red-800"><?php echo $test_error; ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Configurações -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">2. Configurações Atuais</h2>
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="space-y-4">
                        <div>
                            <span class="font-semibold text-gray-700">Ambiente:</span>
                            <span class="ml-2 px-3 py-1 rounded-full text-sm <?php echo MercadoPagoConfig::getAmbiente() === 'test' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'; ?>">
                                <?php echo strtoupper(MercadoPagoConfig::getAmbiente()); ?>
                            </span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-700">API URL:</span>
                            <span class="ml-2 text-gray-600 font-mono text-sm"><?php echo MercadoPagoConfig::getApiUrl(); ?></span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-700">Access Token:</span>
                            <span class="ml-2 text-gray-600 font-mono text-sm"><?php echo substr(MercadoPagoConfig::getAccessToken(), 0, 30) . '...'; ?></span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-700">cURL instalado:</span>
                            <span class="ml-2 px-3 py-1 rounded-full text-sm <?php echo function_exists('curl_version') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo function_exists('curl_version') ? '✅ SIM' : '❌ NÃO'; ?>
                            </span>
                        </div>
                        <?php if (function_exists('curl_version')): 
                            $curl_info = curl_version();
                        ?>
                        <div>
                            <span class="font-semibold text-gray-700">Versão cURL:</span>
                            <span class="ml-2 text-gray-600"><?php echo $curl_info['version']; ?></span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-700">SSL Version:</span>
                            <span class="ml-2 text-gray-600"><?php echo $curl_info['ssl_version']; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Dados do Usuário -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">3. Dados do Usuário</h2>
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="space-y-2">
                        <div><span class="font-semibold">ID:</span> <span class="ml-2 font-mono"><?php echo $dentista['id']; ?></span></div>
                        <div><span class="font-semibold">Nome:</span> <span class="ml-2"><?php echo $dentista['nome'] ?? 'N/A'; ?></span></div>
                        <div><span class="font-semibold">Email:</span> <span class="ml-2"><?php echo $dentista['email'] ?? 'N/A'; ?></span></div>
                        <div><span class="font-semibold">CPF:</span> <span class="ml-2"><?php echo $dentista['cpf'] ?? 'N/A'; ?></span></div>
                    </div>
                </div>
            </div>
            
            <!-- Teste Manual PIX -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">4. Teste Manual de Pagamento PIX</h2>
                <button onclick="testarPagamentoPix()" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg">
                    <i class="fas fa-qrcode mr-2"></i>Criar Pagamento PIX de Teste (R$ 1,00)
                </button>
                <div id="resultado-pix" class="mt-4"></div>
            </div>
            
            <!-- Logs PHP -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">5. Logs PHP</h2>
                <p class="text-sm text-gray-600 mb-2">Verifique os logs do Apache/PHP em:</p>
                <div class="bg-gray-800 text-green-400 p-4 rounded-lg font-mono text-sm">
                    C:\xampp\apache\logs\error.log
                </div>
                <p class="text-sm text-gray-600 mt-2">Use o comando no terminal para ver logs em tempo real:</p>
                <div class="bg-gray-800 text-green-400 p-4 rounded-lg font-mono text-sm">
                    tail -f C:\xampp\apache\logs\error.log
                </div>
            </div>
            
            <!-- Console do Navegador -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">6. Console do Navegador</h2>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-blue-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        Pressione <kbd class="bg-white px-2 py-1 rounded border">F12</kbd> para abrir o DevTools e veja o console para mensagens de erro detalhadas.
                    </p>
                </div>
            </div>
            
            <div class="text-center">
                <a href="planos.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg inline-block">
                    <i class="fas fa-arrow-left mr-2"></i>Voltar para Planos
                </a>
            </div>
        </div>
    </div>
    
    <script>
        async function testarPagamentoPix() {
            const resultado = document.getElementById('resultado-pix');
            resultado.innerHTML = '<div class="bg-blue-50 border border-blue-200 rounded-lg p-4"><i class="fas fa-spinner fa-spin mr-2"></i>Criando pagamento...</div>';
            
            try {
                const response = await fetch('api/mercadopago_create_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        plano: 'mensal',
                        forma_pagamento: 'pix',
                        valor: 1.00
                    })
                });
                
                const data = await response.json();
                console.log('Resposta completa:', data);
                
                if (data.success) {
                    resultado.innerHTML = `
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <p class="text-green-800 font-semibold mb-2">✅ Pagamento criado com sucesso!</p>
                            <p class="text-sm text-gray-600 mb-2"><strong>Payment ID:</strong> ${data.payment_id}</p>
                            <p class="text-sm text-gray-600 mb-2"><strong>Status:</strong> ${data.status}</p>
                            ${data.pix && data.pix.qr_code ? `
                                <div class="mt-4">
                                    <p class="text-sm font-semibold mb-2">Código PIX:</p>
                                    <textarea class="w-full h-24 p-2 border rounded font-mono text-xs" readonly>${data.pix.qr_code}</textarea>
                                </div>
                            ` : ''}
                        </div>
                    `;
                } else {
                    resultado.innerHTML = `
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <p class="text-red-800 font-semibold mb-2">❌ Erro ao criar pagamento</p>
                            <p class="text-sm text-gray-600 mb-2"><strong>Erro:</strong> ${data.error}</p>
                            <p class="text-sm text-gray-600 mb-2"><strong>Detalhes:</strong> ${data.details || 'N/A'}</p>
                            ${data.debug ? `
                                <div class="mt-4 bg-white p-3 rounded">
                                    <p class="text-xs font-semibold mb-2">Debug Info:</p>
                                    <pre class="text-xs overflow-auto">${JSON.stringify(data.debug, null, 2)}</pre>
                                </div>
                            ` : ''}
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Erro:', error);
                resultado.innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-red-800 font-semibold mb-2">❌ Erro de conexão</p>
                        <p class="text-sm text-gray-600">${error.message}</p>
                    </div>
                `;
            }
        }
    </script>
</body>
</html>

