<?php
/**
 * Configurações do Mercado Pago
 */
class MercadoPagoConfig {
    // Ambiente: 'test' ou 'production'
    private static $ambiente = 'production'; // Altere para 'test' para usar credenciais de teste
    
    // Credenciais de TESTE
    private static $test_access_token = 'APP_USR-3073480110301964-102520-e8257f8d5e5ba621d543c46c4411d878-1780663800';
    private static $test_public_key = 'APP_USR-5a62ff7c-67ad-4932-a75e-05409be03e1e';
    
    // Credenciais de PRODUÇÃO
    private static $prod_access_token = 'APP_USR-8876724775091948-102520-5da000d98bc2b144ecd798f770073f6d-1254617540';
    private static $prod_public_key = 'APP_USR-412a0960-f5ae-4dea-8ea3-18758664ad66';
    private static $client_id = '8876724775091948';
    private static $client_secret = 'DrT41NwaZDpCMPQj9Du9TbrP5SadFYCU';
    
    /**
     * Retorna o Access Token baseado no ambiente
     */
    public static function getAccessToken() {
        return self::$ambiente === 'test' ? self::$test_access_token : self::$prod_access_token;
    }
    
    /**
     * Retorna a Public Key baseado no ambiente
     */
    public static function getPublicKey() {
        return self::$ambiente === 'test' ? self::$test_public_key : self::$prod_public_key;
    }
    
    /**
     * Retorna o Client ID
     */
    public static function getClientId() {
        return self::$client_id;
    }
    
    /**
     * Retorna o Client Secret
     */
    public static function getClientSecret() {
        return self::$client_secret;
    }
    
    /**
     * Retorna o ambiente atual
     */
    public static function getAmbiente() {
        return self::$ambiente;
    }
    
    /**
     * Define o ambiente
     */
    public static function setAmbiente($ambiente) {
        if (in_array($ambiente, ['test', 'production'])) {
            self::$ambiente = $ambiente;
        }
    }
    
    /**
     * Retorna a URL da API do Mercado Pago
     */
    public static function getApiUrl() {
        return 'https://api.mercadopago.com';
    }
}
?>

