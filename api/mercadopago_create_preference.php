<?php
session_start();
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/mercadopago.php';
require_once dirname(__DIR__) . '/config/planos.php';

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
$valor = floatval($input['valor'] ?? 0);

if (empty($plano) || $valor <= 0) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos.']);
    exit;
}

// Configurar preferência de pagamento (Checkout Pro)
$preference_data = [
    'items' => [
        [
            'title' => 'Plano ' . ucfirst($plano) . ' - Odontfy',
            'description' => 'Assinatura do plano ' . ucfirst($plano),
            'quantity' => 1,
            'currency_id' => 'BRL',
            'unit_price' => floatval($valor)
        ]
    ],
    'payer' => [
        'name' => $dentista['nome'] ?? 'Cliente',
        'email' => $dentista['email'] ?? 'teste@teste.com'
    ],
    'back_urls' => [
        'success' => 'http://localhost/odontfy2/pagamento_sucesso.php?plano=' . $plano,
        'failure' => 'http://localhost/odontfy2/pagamento_falha.php',
        'pending' => 'http://localhost/odontfy2/pagamento_pendente.php'
    ],
    'payment_methods' => [
        'excluded_payment_methods' => [
            ['id' => 'pix'] // Exclui PIX especificamente
        ],
        'excluded_payment_types' => [
            ['id' => 'ticket'], // Exclui boleto
            ['id' => 'atm'], // Exclui caixas eletrônicos
            ['id' => 'bank_transfer'], // Exclui transferência
            ['id' => 'digital_wallet'], // Exclui carteiras digitais (saldo MP)
            ['id' => 'digital_currency'], // Exclui criptomoedas
            ['id' => 'debit_card'] // Exclui cartão de débito
        ],
        'installments' => 12, // Número máximo de parcelas
        'default_installments' => 1
    ],
    'binary_mode' => true, // Apenas aprovado ou rejeitado (ideal para cartão)
    'external_reference' => $dentista['id']
];

// Fazer requisição para criar preferência
$access_token = MercadoPagoConfig::getAccessToken();
$api_url = MercadoPagoConfig::getApiUrl() . '/checkout/preferences';

// Gerar Idempotency Key único
$idempotency_key = uniqid('odontfy_pref_', true) . '_' . time();

error_log("=== Criando Preferência de Pagamento ===");
error_log("URL: " . $api_url);
error_log("Preference Data: " . json_encode($preference_data, JSON_PRETTY_PRINT));

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preference_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $access_token,
    'X-Idempotency-Key: ' . $idempotency_key
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$curl_error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

error_log("HTTP Code: " . $http_code);
error_log("Response: " . $response);

if ($curl_error) {
    error_log("cURL Error: " . $curl_error);
    echo json_encode([
        'success' => false,
        'error' => 'Erro de conexão.',
        'details' => $curl_error
    ]);
    exit;
}

if ($http_code >= 200 && $http_code < 300) {
    $preference = json_decode($response, true);
    
    error_log("Preferência criada: " . $preference['id']);
    
    // Salvar preferência no banco de dados
    $preference_record = [
        'id' => uniqid('pref_'),
        'dentista_id' => $dentista['id'],
        'plano' => $plano,
        'valor' => $valor,
        'mercadopago_preference_id' => $preference['id'],
        'init_point' => $preference['init_point'],
        'sandbox_init_point' => $preference['sandbox_init_point'] ?? '',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Salvar no arquivo
    $preferences_file = dirname(__DIR__) . '/data/preferencias.json';
    $preferences = [];
    if (file_exists($preferences_file)) {
        $preferences = json_decode(file_get_contents($preferences_file), true) ?: [];
    }
    $preferences[] = $preference_record;
    file_put_contents($preferences_file, json_encode($preferences, JSON_PRETTY_PRINT));
    
    // Retornar o link de pagamento
    $init_point = MercadoPagoConfig::getAmbiente() === 'test' 
        ? ($preference['sandbox_init_point'] ?? $preference['init_point'])
        : $preference['init_point'];
    
    echo json_encode([
        'success' => true,
        'preference_id' => $preference['id'],
        'init_point' => $init_point
    ]);
} else {
    $error_response = json_decode($response, true);
    error_log("Erro ao criar preferência: " . print_r($error_response, true));
    
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao criar preferência de pagamento.',
        'details' => $error_response['message'] ?? 'Erro desconhecido',
        'debug' => [
            'http_code' => $http_code,
            'response' => $error_response
        ]
    ]);
}
?>

