<?php
/**
 * Página de Testes - Mercado Pago
 * Use esta página para testar a integração com o Mercado Pago
 */

require_once 'config/mercadopago.php';

// Verificar qual ambiente está sendo usado
$ambiente = MercadoPagoConfig::getAmbiente();
$access_token = MercadoPagoConfig::getAccessToken();
$public_key = MercadoPagoConfig::getPublicKey();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Mercado Pago - Odontfy</title>
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
        <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">
                <i class="fas fa-flask text-blue-600 mr-3"></i>Teste Mercado Pago
            </h1>
            
            <!-- Status do Ambiente -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Status da Configuração</h2>
                
                <div class="bg-<?php echo $ambiente === 'test' ? 'yellow' : 'green'; ?>-50 border-l-4 border-<?php echo $ambiente === 'test' ? 'yellow' : 'green'; ?>-500 p-4 mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-<?php echo $ambiente === 'test' ? 'flask' : 'check-circle'; ?> text-<?php echo $ambiente === 'test' ? 'yellow' : 'green'; ?>-600 text-2xl mr-3"></i>
                        <div>
                            <p class="font-bold text-<?php echo $ambiente === 'test' ? 'yellow' : 'green'; ?>-800">
                                Ambiente: <?php echo strtoupper($ambiente); ?>
                            </p>
                            <p class="text-sm text-<?php echo $ambiente === 'test' ? 'yellow' : 'green'; ?>-700">
                                <?php echo $ambiente === 'test' ? 'Modo de testes ativo - Use cartões de teste' : 'Modo de produção - Pagamentos reais'; ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm font-semibold text-gray-600 mb-2">Access Token:</p>
                        <p class="text-xs font-mono text-gray-800 break-all"><?php echo substr($access_token, 0, 20) . '...'; ?></p>
                        <span class="inline-block mt-2 px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Configurado ✓</span>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm font-semibold text-gray-600 mb-2">Public Key:</p>
                        <p class="text-xs font-mono text-gray-800 break-all"><?php echo substr($public_key, 0, 20) . '...'; ?></p>
                        <span class="inline-block mt-2 px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Configurado ✓</span>
                    </div>
                </div>
            </div>
            
            <!-- Dados de Teste -->
            <?php if ($ambiente === 'test'): ?>
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Cartões de Teste</h2>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-blue-800 mb-3">Use estes cartões para testar no ambiente de sandbox:</p>
                    
                    <div class="space-y-3">
                        <div class="bg-white p-3 rounded">
                            <p class="font-semibold text-gray-800">Mastercard - Aprovado</p>
                            <p class="text-sm text-gray-600 font-mono">5031 4332 1540 6351</p>
                            <p class="text-xs text-gray-500">CVV: 123 | Validade: 11/25</p>
                        </div>
                        
                        <div class="bg-white p-3 rounded">
                            <p class="font-semibold text-gray-800">Visa - Aprovado</p>
                            <p class="text-sm text-gray-600 font-mono">4509 9535 6623 3704</p>
                            <p class="text-xs text-gray-500">CVV: 123 | Validade: 11/25</p>
                        </div>
                        
                        <div class="bg-white p-3 rounded">
                            <p class="font-semibold text-gray-800">Visa - Rejeitado</p>
                            <p class="text-sm text-gray-600 font-mono">4013 5406 8274 6260</p>
                            <p class="text-xs text-gray-500">CVV: 123 | Validade: 11/25</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <p class="font-semibold text-purple-800 mb-2">PIX de Teste</p>
                    <p class="text-sm text-purple-700">No ambiente de teste, o pagamento PIX é aprovado automaticamente após alguns segundos.</p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Teste de Conexão -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Teste de Conexão com a API</h2>
                
                <button onclick="testarConexao()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-plug mr-2"></i>Testar Conexão
                </button>
                
                <div id="resultado-conexao" class="mt-4"></div>
            </div>
            
            <!-- Links Úteis -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Links Úteis</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="https://www.mercadopago.com.br/developers/panel" target="_blank" class="bg-blue-50 hover:bg-blue-100 p-4 rounded-lg border border-blue-200 transition-colors">
                        <i class="fas fa-external-link-alt text-blue-600 mr-2"></i>
                        <span class="font-semibold text-blue-800">Painel de Desenvolvedores</span>
                    </a>
                    
                    <a href="https://www.mercadopago.com.br/developers/pt/docs" target="_blank" class="bg-green-50 hover:bg-green-100 p-4 rounded-lg border border-green-200 transition-colors">
                        <i class="fas fa-book text-green-600 mr-2"></i>
                        <span class="font-semibold text-green-800">Documentação Oficial</span>
                    </a>
                    
                    <a href="https://www.mercadopago.com.br/developers/panel/notifications/webhooks" target="_blank" class="bg-purple-50 hover:bg-purple-100 p-4 rounded-lg border border-purple-200 transition-colors">
                        <i class="fas fa-bell text-purple-600 mr-2"></i>
                        <span class="font-semibold text-purple-800">Configurar Webhooks</span>
                    </a>
                    
                    <a href="https://status.mercadopago.com/" target="_blank" class="bg-yellow-50 hover:bg-yellow-100 p-4 rounded-lg border border-yellow-200 transition-colors">
                        <i class="fas fa-heartbeat text-yellow-600 mr-2"></i>
                        <span class="font-semibold text-yellow-800">Status da API</span>
                    </a>
                </div>
            </div>
            
            <!-- Logs -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Arquivos de Log</h2>
                
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600 mb-2">Logs do Webhook:</p>
                    <p class="text-xs font-mono text-gray-800">data/mercadopago_webhook_log.txt</p>
                </div>
            </div>
            
            <!-- Voltar -->
            <div class="text-center">
                <a href="planos.php" class="inline-block bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Voltar para Planos
                </a>
            </div>
        </div>
    </div>
    
    <script>
        async function testarConexao() {
            const resultado = document.getElementById('resultado-conexao');
            resultado.innerHTML = '<div class="bg-blue-50 border border-blue-200 rounded-lg p-4"><i class="fas fa-spinner fa-spin text-blue-600 mr-2"></i>Testando conexão...</div>';
            
            try {
                const response = await fetch('<?php echo MercadoPagoConfig::getApiUrl(); ?>/v1/payment_methods', {
                    headers: {
                        'Authorization': 'Bearer <?php echo $access_token; ?>'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    resultado.innerHTML = `
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <i class="fas fa-check-circle text-green-600 text-xl mr-2"></i>
                            <span class="font-semibold text-green-800">Conexão bem-sucedida!</span>
                            <p class="text-sm text-green-700 mt-2">API respondeu com ${data.length} métodos de pagamento disponíveis.</p>
                        </div>
                    `;
                } else {
                    resultado.innerHTML = `
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <i class="fas fa-times-circle text-red-600 text-xl mr-2"></i>
                            <span class="font-semibold text-red-800">Erro na conexão</span>
                            <p class="text-sm text-red-700 mt-2">Status: ${response.status}</p>
                        </div>
                    `;
                }
            } catch (error) {
                resultado.innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <i class="fas fa-times-circle text-red-600 text-xl mr-2"></i>
                        <span class="font-semibold text-red-800">Erro ao conectar</span>
                        <p class="text-sm text-red-700 mt-2">${error.message}</p>
                    </div>
                `;
            }
        }
    </script>
</body>
</html>

