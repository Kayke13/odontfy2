<?php
session_start();
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/mercadopago.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit;
}

$dentista = Auth::getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$plano = $input['plano'] ?? '';
$forma_pagamento = $input['forma_pagamento'] ?? '';
$valor = floatval($input['valor'] ?? 0);

if (empty($plano) || empty($forma_pagamento) || $valor <= 0) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos.']);
    exit;
}

// Configurar dados do pagamento
$payment_data = [
    'transaction_amount' => $valor,
    'description' => "Plano {$plano} - Odontfy",
    'payer' => [
        'email' => $dentista['email'] ?? 'cliente@odontfy.com',
        'first_name' => $dentista['nome'] ?? 'Cliente',
        'last_name' => 'Odontfy',
        'identification' => [
            'type' => 'CPF',
            'number' => $dentista['cpf'] ?? '00000000000'
        ]
    ],
    'notification_url' => 'https://seudominio.com/api/mercadopago_webhook.php', // URL para receber notificações
    'external_reference' => $dentista['id'], // ID do dentista para referência
    'metadata' => [
        'dentista_id' => $dentista['id'],
        'plano' => $plano,
        'username' => $dentista['username']
    ]
];

// Configurar para cartão de crédito
if ($forma_pagamento === 'cartao') {
    $token = $input['token'] ?? '';
    $installments = intval($input['installments'] ?? 1);
    $payment_method_id = $input['payment_method_id'] ?? '';
    $issuer_id = $input['issuer_id'] ?? '';
    
    if (empty($token) || empty($payment_method_id)) {
        echo json_encode(['success' => false, 'error' => 'Token do cartão não fornecido.']);
        exit;
    }
    
    $payment_data['token'] = $token;
    $payment_data['installments'] = $installments;
    $payment_data['payment_method_id'] = $payment_method_id;
    
    if (!empty($issuer_id)) {
        $payment_data['issuer_id'] = $issuer_id;
    }
} else {
    // Para PIX e Boleto
    $payment_data['payment_method_id'] = $forma_pagamento;
}

// Fazer requisição para a API do Mercado Pago
$access_token = MercadoPagoConfig::getAccessToken();
$api_url = MercadoPagoConfig::getApiUrl() . '/v1/payments';

// Gerar Idempotency Key único para evitar duplicação
$idempotency_key = uniqid('odontfy_', true) . '_' . time();

// Log dos dados enviados
error_log("=== Mercado Pago Request ===");
error_log("URL: " . $api_url);
error_log("Idempotency Key: " . $idempotency_key);
error_log("Payment Data: " . json_encode($payment_data, JSON_PRETTY_PRINT));

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $access_token,
    'X-Idempotency-Key: ' . $idempotency_key
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desabilitar verificação SSL (apenas para desenvolvimento local)
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$curl_error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Log da resposta
error_log("HTTP Code: " . $http_code);
error_log("Response: " . $response);
if ($curl_error) {
    error_log("cURL Error: " . $curl_error);
}

// Verificar se houve erro no cURL
if ($curl_error) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro de conexão com Mercado Pago.',
        'details' => $curl_error,
        'debug' => [
            'curl_error' => $curl_error,
            'api_url' => $api_url
        ]
    ]);
    exit;
}

if ($http_code >= 200 && $http_code < 300) {
    $payment = json_decode($response, true);
    
    // Salvar informações do pagamento no banco de dados
    $payment_record = [
        'id' => uniqid('pay_'),
        'dentista_id' => $dentista['id'],
        'plano' => $plano,
        'valor' => $valor,
        'forma_pagamento' => $forma_pagamento,
        'mercadopago_payment_id' => $payment['id'],
        'status' => $payment['status'],
        'status_detail' => $payment['status_detail'] ?? '',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Salvar no arquivo de pagamentos
    $payments_file = dirname(__DIR__) . '/data/pagamentos.json';
    $payments = [];
    if (file_exists($payments_file)) {
        $payments = json_decode(file_get_contents($payments_file), true) ?: [];
    }
    $payments[] = $payment_record;
    file_put_contents($payments_file, json_encode($payments, JSON_PRETTY_PRINT));
    
    // Preparar resposta
    $response_data = [
        'success' => true,
        'payment_id' => $payment['id'],
        'status' => $payment['status'],
        'status_detail' => $payment['status_detail'] ?? '',
    ];
    
    // Se for PIX, adicionar informações do QR Code
    if ($forma_pagamento === 'pix' && isset($payment['point_of_interaction']['transaction_data'])) {
        $response_data['pix'] = [
            'qr_code' => $payment['point_of_interaction']['transaction_data']['qr_code'] ?? '',
            'qr_code_base64' => $payment['point_of_interaction']['transaction_data']['qr_code_base64'] ?? '',
            'ticket_url' => $payment['point_of_interaction']['transaction_data']['ticket_url'] ?? ''
        ];
    }
    
    // Se for boleto, adicionar URL do boleto
    if ($forma_pagamento === 'bolbradesco') {
        if (isset($payment['transaction_details']['external_resource_url'])) {
            $response_data['boleto_url'] = $payment['transaction_details']['external_resource_url'];
        } elseif (isset($payment['point_of_interaction']['transaction_data']['ticket_url'])) {
            $response_data['boleto_url'] = $payment['point_of_interaction']['transaction_data']['ticket_url'];
        }
    }
    
    echo json_encode($response_data);
} else {
    $error_response = json_decode($response, true);
    error_log("Erro Mercado Pago HTTP " . $http_code . ": " . print_r($error_response, true));
    
    $error_message = 'Erro ao processar pagamento.';
    $error_details = 'Erro desconhecido';
    
    if ($error_response) {
        if (isset($error_response['message'])) {
            $error_details = $error_response['message'];
        } elseif (isset($error_response['cause'])) {
            $error_details = json_encode($error_response['cause']);
        }
    }
    
    echo json_encode([
        'success' => false,
        'error' => $error_message,
        'details' => $error_details,
        'debug' => [
            'http_code' => $http_code,
            'response' => $error_response,
            'payment_method' => $forma_pagamento
        ]
    ]);
}
?>

