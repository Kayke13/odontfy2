<?php
/**
 * Configurações centralizadas dos planos
 */

class PlanosConfig {
    private static $configuracoes = null;
    
    /**
     * Carrega as configurações dos planos do arquivo de configurações
     */
    public static function carregar() {
        if (self::$configuracoes === null) {
            $configuracoes_file = 'data/configuracoes.json';
            
            // Configurações padrão
            $configuracoes_default = [
                'planos' => [
                    'mensal' => [
                        'nome' => 'Plano Mensal',
                        'preco' => 97.00,
                        'descricao' => 'Acesso completo por 30 dias',
                        'recursos' => ['Agendamentos ilimitados', 'Gestão de clientes', 'Relatórios básicos'],
                        'duracao_dias' => 30
                    ],
                    'semestral' => [
                        'nome' => 'Plano Semestral',
                        'preco' => 485.00,
                        'descricao' => 'Acesso completo por 6 meses (1 mês grátis)',
                        'recursos' => ['Agendamentos ilimitados', 'Gestão de clientes', 'Relatórios intermediários', 'Suporte por email'],
                        'duracao_dias' => 180
                    ],
                    'anual' => [
                        'nome' => 'Plano Anual',
                        'preco' => 970.00,
                        'descricao' => 'Acesso completo por 12 meses (2 meses grátis)',
                        'recursos' => ['Agendamentos ilimitados', 'Gestão de clientes', 'Relatórios avançados', 'Suporte prioritário'],
                        'duracao_dias' => 365
                    ]
                ]
            ];
            
            if (file_exists($configuracoes_file)) {
                $configuracoes_salvas = json_decode(file_get_contents($configuracoes_file), true) ?: [];
                self::$configuracoes = array_merge($configuracoes_default, $configuracoes_salvas);
            } else {
                self::$configuracoes = $configuracoes_default;
            }
        }
        
        return self::$configuracoes;
    }
    
    /**
     * Retorna os planos configurados
     */
    public static function getPlanos() {
        $config = self::carregar();
        return $config['planos'] ?? [];
    }
    
    /**
     * Retorna um plano específico
     */
    public static function getPlano($plano_key) {
        $planos = self::getPlanos();
        return $planos[$plano_key] ?? null;
    }
    
    /**
     * Retorna a duração em dias de um plano
     */
    public static function getDuracaoDias($plano_key) {
        $plano = self::getPlano($plano_key);
        return $plano['duracao_dias'] ?? 30;
    }
    
    /**
     * Retorna o preço de um plano
     */
    public static function getPreco($plano_key) {
        $plano = self::getPlano($plano_key);
        return $plano['preco'] ?? 0;
    }
    
    /**
     * Retorna o nome de um plano
     */
    public static function getNome($plano_key) {
        $plano = self::getPlano($plano_key);
        return $plano['nome'] ?? ucfirst($plano_key);
    }
    
    /**
     * Retorna a descrição de um plano
     */
    public static function getDescricao($plano_key) {
        $plano = self::getPlano($plano_key);
        return $plano['descricao'] ?? '';
    }
    
    /**
     * Retorna os recursos de um plano
     */
    public static function getRecursos($plano_key) {
        $plano = self::getPlano($plano_key);
        return $plano['recursos'] ?? [];
    }
    
    /**
     * Verifica e atualiza planos expirados
     * Deve ser chamado antes de verificar o status do plano
     */
    public static function verificarEAtualizarPlanosExpirados($dentista) {
        $hoje = date('Y-m-d');
        $precisa_atualizar = false;
        $update_data = [];
        
        // Verificar expiração de plano pago
        if (!empty($dentista['assinatura_ativa']) && !empty($dentista['plano_fim'])) {
            if ($dentista['plano_fim'] < $hoje) {
                $update_data['plano'] = 'expirado';
                $update_data['assinatura_ativa'] = false;
                $precisa_atualizar = true;
            }
        }
        
        // Verificar expiração de período de teste
        if (empty($dentista['assinatura_ativa']) && !empty($dentista['teste_fim'])) {
            if ($dentista['teste_fim'] < $hoje && $dentista['plano'] !== 'expirado') {
                $update_data['plano'] = 'expirado';
                $precisa_atualizar = true;
            }
        }
        
        // Atualizar no banco se necessário
        if ($precisa_atualizar) {
            require_once __DIR__ . '/database.php';
            Database::update('dentistas', $dentista['id'], $update_data);
            
            // Atualizar o objeto dentista local
            foreach ($update_data as $key => $value) {
                $dentista[$key] = $value;
            }
        }
        
        return $dentista;
    }
}
?>
