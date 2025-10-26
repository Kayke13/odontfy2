<?php
require_once 'config/mercadopago.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Cartão - Mercado Pago</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/LOGO.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- SDK Mercado Pago -->
    <script src="https://sdk.mercadopago.com/js/v2"></script>
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
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">
                <i class="fas fa-credit-card text-blue-600 mr-3"></i>Teste de Cartão
            </h1>
            
            <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-blue-800 mb-2"><strong>Public Key:</strong></p>
                <p class="text-sm font-mono text-blue-600"><?php echo MercadoPagoConfig::getPublicKey(); ?></p>
            </div>
            
            <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <p class="text-yellow-800 font-semibold mb-2">Cartão de Teste (Mastercard - Aprovado):</p>
                <p class="text-sm"><strong>Número:</strong> 5031 4332 1540 6351</p>
                <p class="text-sm"><strong>CVV:</strong> 123</p>
                <p class="text-sm"><strong>Validade:</strong> 11/25</p>
                <p class="text-sm"><strong>Nome:</strong> APRO</p>
            </div>
            
            <form id="form-cartao" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Número do Cartão</label>
                    <input type="text" id="cardNumber" placeholder="5031 4332 1540 6351" maxlength="19"
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Validade</label>
                        <input type="text" id="cardExpiration" placeholder="11/25" maxlength="5"
                               class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">CVV</label>
                        <input type="text" id="securityCode" placeholder="123" maxlength="4"
                               class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nome no Cartão</label>
                    <input type="text" id="cardholderName" placeholder="APRO"
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <button type="button" onclick="testarToken()" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 px-6 rounded-xl font-bold text-lg transition-all">
                    <i class="fas fa-vial mr-2"></i>Criar Token de Teste
                </button>
            </form>
            
            <div id="resultado" class="mt-6"></div>
            
            <div class="mt-6">
                <a href="planos.php" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i>Voltar para Planos
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Inicializar Mercado Pago SDK
        console.log('Inicializando Mercado Pago...');
        const mp = new MercadoPago('<?php echo MercadoPagoConfig::getPublicKey(); ?>', {
            locale: 'pt-BR'
        });
        console.log('Mercado Pago inicializado:', mp);
        
        // Formatação automática
        document.getElementById('cardNumber').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });
        
        document.getElementById('cardExpiration').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2, 4);
            }
            e.target.value = value;
        });
        
        document.getElementById('securityCode').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });
        
        async function testarToken() {
            const resultado = document.getElementById('resultado');
            resultado.innerHTML = '<div class="bg-blue-50 border border-blue-200 rounded-lg p-4"><i class="fas fa-spinner fa-spin mr-2"></i>Criando token...</div>';
            
            const numero = document.getElementById('cardNumber').value.replace(/\s/g, '');
            const validade = document.getElementById('cardExpiration').value;
            const cvv = document.getElementById('securityCode').value;
            const nome = document.getElementById('cardholderName').value;
            
            if (!numero || !validade || !cvv || !nome) {
                resultado.innerHTML = '<div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800">❌ Por favor, preencha todos os campos!</div>';
                return;
            }
            
            const [mes, ano] = validade.split('/');
            
            if (!mes || !ano || mes.length !== 2 || ano.length !== 2) {
                resultado.innerHTML = '<div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800">❌ Validade inválida! Use o formato MM/AA</div>';
                return;
            }
            
            try {
                const cardData = {
                    cardNumber: numero,
                    cardholderName: nome,
                    cardExpirationMonth: mes,
                    cardExpirationYear: '20' + ano,
                    securityCode: cvv,
                    identificationType: 'CPF',
                    identificationNumber: '00000000000'
                };
                
                console.log('Dados do cartão:', cardData);
                console.log('Chamando mp.createCardToken...');
                
                const token = await mp.createCardToken(cardData);
                
                console.log('Token recebido:', token);
                
                if (token && token.id) {
                    resultado.innerHTML = `
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <p class="text-green-800 font-semibold mb-4">✅ Token criado com sucesso!</p>
                            <div class="bg-white rounded p-3 mb-3">
                                <p class="text-sm font-semibold text-gray-700">Token ID:</p>
                                <p class="text-xs font-mono text-gray-600 break-all">${token.id}</p>
                            </div>
                            ${token.payment_method_id ? `
                            <div class="bg-white rounded p-3 mb-3">
                                <p class="text-sm font-semibold text-gray-700">Payment Method ID:</p>
                                <p class="text-xs font-mono text-gray-600">${token.payment_method_id}</p>
                            </div>
                            ` : ''}
                            ${token.issuer_id ? `
                            <div class="bg-white rounded p-3 mb-3">
                                <p class="text-sm font-semibold text-gray-700">Issuer ID:</p>
                                <p class="text-xs font-mono text-gray-600">${token.issuer_id}</p>
                            </div>
                            ` : ''}
                            <div class="bg-white rounded p-3">
                                <p class="text-sm font-semibold text-gray-700 mb-2">Resposta completa:</p>
                                <pre class="text-xs overflow-auto bg-gray-50 p-2 rounded">${JSON.stringify(token, null, 2)}</pre>
                            </div>
                        </div>
                    `;
                } else {
                    throw new Error('Token não retornou ID válido');
                }
            } catch (error) {
                console.error('Erro ao criar token:', error);
                resultado.innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-red-800 font-semibold mb-2">❌ Erro ao criar token</p>
                        <p class="text-sm text-red-700">${error.message || 'Erro desconhecido'}</p>
                        ${error.cause ? `<p class="text-xs text-red-600 mt-2">Causa: ${JSON.stringify(error.cause)}</p>` : ''}
                    </div>
                `;
            }
        }
    </script>
</body>
</html>

