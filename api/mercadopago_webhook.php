<?php
/**
 * Webhook do Mercado Pago
 * Este arquivo recebe notificações automáticas quando o status de um pagamento muda
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/mercadopago.php';
require_once dirname(__DIR__) . '/config/planos.php';

// Log da requisição para debug
$log_file = dirname(__DIR__) . '/data/mercadopago_webhook_log.txt';
$log_data = date('Y-m-d H:i:s') . " - " . file_get_contents('php://input') . "\n";
file_put_contents($log_file, $log_data, FILE_APPEND);

// Receber dados do webhook
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Mercado Pago envia notificações com type "payment"
if (isset($data['type']) && $data['type'] === 'payment') {
    $payment_id = $data['data']['id'] ?? null;
    
    if ($payment_id) {
        // Consultar detalhes do pagamento na API
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
                        $old_status = $p['status'];
                        $p['status'] = $payment['status'];
                        $p['status_detail'] = $payment['status_detail'] ?? '';
                        $p['updated_at'] = date('Y-m-d H:i:s');
                        
                        // Se o pagamento foi aprovado, ativar o plano
                        if ($payment['status'] === 'approved' && $old_status !== 'approved') {
                            $dentista_id = $p['dentista_id'];
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
                            
                            Database::update('dentistas', $dentista_id, $dados_atualizacao);
                            
                            // Log de sucesso
                            $log_data = date('Y-m-d H:i:s') . " - PLANO ATIVADO: Dentista {$dentista_id} - Plano {$plano_selecionado}\n";
                            file_put_contents($log_file, $log_data, FILE_APPEND);
                        }
                        
                        break;
                    }
                }
                
                file_put_contents($payments_file, json_encode($payments, JSON_PRETTY_PRINT));
            }
        }
    }
}

// Retornar 200 OK para o Mercado Pago
http_response_code(200);
echo json_encode(['success' => true]);
?>

