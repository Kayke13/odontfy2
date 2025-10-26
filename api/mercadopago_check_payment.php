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
$payment_id = $_GET['payment_id'] ?? '';

if (empty($payment_id)) {
    echo json_encode(['success' => false, 'error' => 'ID do pagamento não fornecido.']);
    exit;
}

// Consultar status na API do Mercado Pago
$access_token = MercadoPagoConfig::getAccessToken();
$api_url = MercadoPagoConfig::getApiUrl() . '/v1/payments/' . $payment_id;

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $access_token
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code >= 200 && $http_code < 300) {
    $payment = json_decode($response, true);
    
    // Atualizar status no arquivo local
    $payments_file = dirname(__DIR__) . '/data/pagamentos.json';
    if (file_exists($payments_file)) {
        $payments = json_decode(file_get_contents($payments_file), true) ?: [];
        
        foreach ($payments as &$p) {
            if ($p['mercadopago_payment_id'] == $payment_id) {
                $p['status'] = $payment['status'];
                $p['status_detail'] = $payment['status_detail'] ?? '';
                $p['updated_at'] = date('Y-m-d H:i:s');
                
                // Se o pagamento foi aprovado, ativar o plano
                if ($payment['status'] === 'approved' && $p['dentista_id'] === $dentista['id']) {
                    $plano_selecionado = $p['plano'];
                    $dias = PlanosConfig::getDuracaoDias($plano_selecionado);
                    $data_fim = date('Y-m-d', strtotime("+{$dias} days"));
                    
                    // Atualizar dados do dentista
                    $dados_atualizacao = [
                        'assinatura_ativa' => true,
                        'plano' => $plano_selecionado,
                        'plano_fim' => $data_fim,
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    // Remover dados de teste
                    $dados_atualizacao['teste_inicio'] = null;
                    $dados_atualizacao['teste_fim'] = null;
                    
                    Database::update('dentistas', $dentista['id'], $dados_atualizacao);
                }
                
                break;
            }
        }
        
        file_put_contents($payments_file, json_encode($payments, JSON_PRETTY_PRINT));
    }
    
    echo json_encode([
        'success' => true,
        'status' => $payment['status'],
        'status_detail' => $payment['status_detail'] ?? '',
        'approved' => $payment['status'] === 'approved'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao consultar pagamento.'
    ]);
}
?>

