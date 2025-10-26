<?php

class PlanoStatus {
    
    /**
     * Verifica o status completo do plano de um dentista
     */
    public static function verificarStatus($dentista) {
        $assinatura_ativa = $dentista['assinatura_ativa'] ?? false;
        $plano_fim = $dentista['plano_fim'] ?? null;
        $plano_atual = $dentista['plano'] ?? 'teste';
        $teste_inicio = $dentista['teste_inicio'] ?? null;
        $teste_fim = $dentista['teste_fim'] ?? null;
        $created_at = $dentista['created_at'] ?? date('Y-m-d H:i:s');
        
        $status = [
            'plano_atual' => $plano_atual,
            'assinatura_ativa' => false,
            'assinatura_expirada' => false,
            'em_teste' => false,
            'teste_expirado' => false,
            'status_geral' => 'inativo',
            'dias_restantes' => 0,
            'dias_expirados' => 0,
            'pode_usar_sistema' => false,
            'limite_servicos' => 5,
            'recursos_disponiveis' => []
        ];
        
        // VERIFICAÇÃO PRIORITÁRIA: Se o plano está marcado como "expirado"
        if ($plano_atual === 'expirado') {
            $status['status_geral'] = 'expirado';
            $status['pode_usar_sistema'] = false;
            $status['assinatura_expirada'] = true;
            $status['teste_expirado'] = true;
            
            // Calcular dias expirados baseado na última data disponível
            if ($plano_fim) {
                $status['dias_expirados'] = max(1, ceil((time() - strtotime($plano_fim)) / (24 * 60 * 60)));
            } elseif ($teste_fim) {
                $status['dias_expirados'] = max(1, ceil((time() - strtotime($teste_fim)) / (24 * 60 * 60)));
            } else {
                $status['dias_expirados'] = 0;
            }
            
            return $status;
        }
        
        // Verificar se a assinatura expirou
        if ($plano_fim && strtotime($plano_fim) < time()) {
            $status['assinatura_expirada'] = true;
            $status['dias_expirados'] = ceil((time() - strtotime($plano_fim)) / (24 * 60 * 60));
        }
        
        // Se tem assinatura ativa e não expirou
        if ($assinatura_ativa && !$status['assinatura_expirada']) {
            $status['assinatura_ativa'] = true;
            $status['status_geral'] = 'ativo';
            $status['pode_usar_sistema'] = true;
            $status['limite_servicos'] = 999; // Ilimitado
            $status['recursos_disponiveis'] = self::getRecursosPlano($plano_atual);
            
            // Calcular dias restantes da assinatura
            if ($plano_fim) {
                $status['dias_restantes'] = ceil((strtotime($plano_fim) - time()) / (24 * 60 * 60));
            }
        }
        // Se não tem assinatura ativa, verificar período de teste
        else {
            // Calcular datas de teste se necessário
            if (!$teste_inicio) {
                $teste_inicio = date('Y-m-d', strtotime($created_at));
            }
            if (!$teste_fim) {
                $teste_fim = date('Y-m-d', strtotime($teste_inicio . ' +7 days'));
            }
            
            // Se tem teste_fim mas não tem teste_inicio, calcular o início baseado no fim
            if ($teste_fim && !$dentista['teste_inicio']) {
                $teste_inicio = date('Y-m-d', strtotime($teste_fim . ' -7 days'));
            }
            
            $teste_fim_timestamp = strtotime($teste_fim);
            $hoje = time();
            
            if ($teste_fim_timestamp > $hoje) {
                // Ainda no período de teste
                $status['em_teste'] = true;
                $status['status_geral'] = 'teste';
                $status['pode_usar_sistema'] = true;
                $status['dias_restantes'] = ceil(($teste_fim_timestamp - $hoje) / (24 * 60 * 60));
                $status['limite_servicos'] = 5;
                $status['recursos_disponiveis'] = self::getRecursosPlano('teste');
            } else {
                // Período de teste expirado
                $status['teste_expirado'] = true;
                $status['status_geral'] = 'expirado';
                $status['pode_usar_sistema'] = false;
                $status['dias_expirados'] = ceil(($hoje - $teste_fim_timestamp) / (24 * 60 * 60));
            }
        }
        
        return $status;
    }
    
    /**
     * Retorna os recursos disponíveis para cada plano
     */
    private static function getRecursosPlano($plano) {
        $recursos = [
            'teste' => [
                'agendamentos_ilimitados' => true,
                'servicos_limitados' => 5,
                'relatorios_basicos' => true,
                'suporte_basico' => true
            ],
            'mensal' => [
                'agendamentos_ilimitados' => true,
                'servicos_ilimitados' => true,
                'relatorios_avancados' => true,
                'suporte_prioritario' => true,
                'controle_financeiro' => true,
                'multiplos_dentistas' => true
            ],
            'anual' => [
                'agendamentos_ilimitados' => true,
                'servicos_ilimitados' => true,
                'relatorios_avancados' => true,
                'suporte_prioritario' => true,
                'controle_financeiro' => true,
                'multiplos_dentistas' => true,
                'backup_automatico' => true,
                'integracao_calendario' => true
            ]
        ];
        
        return $recursos[$plano] ?? $recursos['teste'];
    }
    
    /**
     * Verifica se o dentista pode usar uma funcionalidade específica
     */
    public static function podeUsarFuncionalidade($dentista, $funcionalidade) {
        $status = self::verificarStatus($dentista);
        
        if (!$status['pode_usar_sistema']) {
            return false;
        }
        
        $recursos = $status['recursos_disponiveis'];
        
        switch ($funcionalidade) {
            case 'agendamentos':
                return $recursos['agendamentos_ilimitados'] ?? false;
            case 'servicos_ilimitados':
                return $recursos['servicos_ilimitados'] ?? false;
            case 'relatorios_avancados':
                return $recursos['relatorios_avancados'] ?? false;
            case 'controle_financeiro':
                return $recursos['controle_financeiro'] ?? false;
            case 'multiplos_dentistas':
                return $recursos['multiplos_dentistas'] ?? false;
            case 'backup_automatico':
                return $recursos['backup_automatico'] ?? false;
            case 'integracao_calendario':
                return $recursos['integracao_calendario'] ?? false;
            default:
                return false;
        }
    }
    
    /**
     * Retorna mensagem de status para exibição
     */
    public static function getMensagemStatus($status) {
        switch ($status['status_geral']) {
            case 'ativo':
                return [
                    'tipo' => 'success',
                    'icone' => 'check-circle',
                    'titulo' => 'Plano Ativo',
                    'mensagem' => 'Sua assinatura está ativa. Todos os recursos estão disponíveis.',
                    'dias' => $status['dias_restantes'] > 0 ? "{$status['dias_restantes']} dias restantes" : 'Renovação automática'
                ];
            case 'teste':
                return [
                    'tipo' => 'warning',
                    'icone' => 'clock',
                    'titulo' => 'Período de Teste',
                    'mensagem' => 'Você está no período de teste. Alguns recursos são limitados.',
                    'dias' => "{$status['dias_restantes']} dias restantes"
                ];
            case 'expirado':
                return [
                    'tipo' => 'error',
                    'icone' => 'exclamation-triangle',
                    'titulo' => 'Plano Expirado',
                    'mensagem' => 'Seu plano expirou. Assine ou renove para continuar usando o sistema.',
                    'dias' => $status['dias_expirados'] > 0 ? "Expirado há {$status['dias_expirados']} dias" : "Plano bloqueado"
                ];
            default:
                return [
                    'tipo' => 'info',
                    'icone' => 'info-circle',
                    'titulo' => 'Status Indefinido',
                    'mensagem' => 'Entre em contato com o suporte.',
                    'dias' => ''
                ];
        }
    }
    
    /**
     * Aplica restrições baseadas no status do plano
     */
    public static function aplicarRestricoes($dentista) {
        $status = self::verificarStatus($dentista);
        
        // Se não pode usar o sistema, redirecionar
        if (!$status['pode_usar_sistema']) {
            if ($status['status_geral'] === 'expirado') {
                header('Location: planos.php?expirado=1');
            } else {
                header('Location: planos.php');
            }
            exit;
        }
        
        return $status;
    }
}
