# 💳 Integração Mercado Pago - Odontfy

## 📋 Documentação Completa da Integração

Esta documentação explica como foi implementada a integração com o Mercado Pago usando o **Checkout Transparente (API)**.

---

## 🎯 O que foi implementado

### ✅ Funcionalidades

1. **PIX** 📱
   - Geração automática de QR Code
   - Código copia-e-cola
   - Verificação automática de pagamento a cada 5 segundos
   - Ativação automática do plano após confirmação

2. **Cartão de Crédito** 💳
   - Processamento direto via API
   - Validação de campos
   - Formatação automática (número, validade, CVV)

3. **Boleto Bancário** 🏦
   - Geração de boleto
   - Link para visualização/impressão
   - Ativação automática após confirmação (via webhook)

4. **Webhook** 🔔
   - Recebe notificações automáticas do Mercado Pago
   - Atualiza status dos pagamentos
   - Ativa planos automaticamente

---

## 📁 Arquivos Criados

### 1. `config/mercadopago.php`
Arquivo de configuração com credenciais do Mercado Pago.

**Configurações:**
- Credenciais de **teste** e **produção**
- Ambiente configurável (`test` ou `production`)
- Access Token, Public Key, Client ID e Client Secret

**Como usar:**
```php
// Para alternar entre teste e produção
// Edite a linha 8 do arquivo config/mercadopago.php:
private static $ambiente = 'production'; // ou 'test'
```

### 2. `api/mercadopago_create_payment.php`
Cria o pagamento na API do Mercado Pago.

**Funções:**
- Recebe dados do plano e forma de pagamento
- Faz requisição para API do Mercado Pago
- Retorna informações do pagamento (QR Code PIX, URL do boleto, etc)
- Salva registro do pagamento em `data/pagamentos.json`

### 3. `api/mercadopago_check_payment.php`
Verifica o status de um pagamento.

**Funções:**
- Consulta status na API do Mercado Pago
- Atualiza status local
- Ativa o plano se o pagamento foi aprovado

### 4. `api/mercadopago_webhook.php`
Recebe notificações automáticas do Mercado Pago.

**Funções:**
- Recebe POST do Mercado Pago quando status muda
- Consulta detalhes do pagamento
- Ativa plano automaticamente quando aprovado
- Registra logs em `data/mercadopago_webhook_log.txt`

### 5. `pagamento.php` (atualizado)
Página de pagamento com interface moderna.

**Novidades:**
- Modal para exibir QR Code PIX
- Modal para boleto
- Verificação automática de pagamento
- Integração completa com API

### 6. `data/pagamentos.json`
Armazena todos os pagamentos processados.

**Estrutura:**
```json
[
  {
    "id": "pay_123456",
    "dentista_id": "dentista_id",
    "plano": "mensal",
    "valor": 99.90,
    "forma_pagamento": "pix",
    "mercadopago_payment_id": "1234567890",
    "status": "approved",
    "status_detail": "accredited",
    "created_at": "2025-10-26 10:00:00",
    "updated_at": "2025-10-26 10:05:00"
  }
]
```

---

## 🔧 Configuração Inicial

### 1. Ambiente de Teste

Para usar o ambiente de **teste** (sandbox), edite `config/mercadopago.php`:

```php
private static $ambiente = 'test';
```

**Credenciais de Teste:**
- Access Token: `APP_USR-3073480110301964-102520-e8257f8d5e5ba621d543c46c4411d878-1780663800`

### 2. Ambiente de Produção

Para usar o ambiente de **produção**, edite `config/mercadopago.php`:

```php
private static $ambiente = 'production';
```

**Credenciais de Produção:**
- Access Token: `APP_USR-8876724775091948-102520-5da000d98bc2b144ecd798f770073f6d-1254617540`
- Public Key: `APP_USR-412a0960-f5ae-4dea-8ea3-18758664ad66`

### 3. Configurar Webhook no Mercado Pago

Para receber notificações automáticas:

1. Acesse: https://www.mercadopago.com.br/developers/panel/notifications/webhooks
2. Cadastre a URL do seu webhook:
   ```
   https://seudominio.com/api/mercadopago_webhook.php
   ```
3. Selecione o evento: **Pagamentos**

**⚠️ IMPORTANTE:** No arquivo `api/mercadopago_create_payment.php` (linha 43), altere:
```php
'notification_url' => 'https://SEUDOMINIO.com/api/mercadopago_webhook.php',
```

---

## 🎨 Interface do Usuário

### Modal PIX
Quando o usuário escolhe PIX:
- ✅ QR Code é exibido automaticamente
- ✅ Código copia-e-cola disponível
- ✅ Botão "Copiar" para facilitar
- ✅ Verificação automática a cada 5 segundos
- ✅ Botão "Já Paguei" para verificação manual

### Modal Boleto
Quando o usuário escolhe Boleto:
- ✅ Link para visualizar/imprimir
- ✅ Informação sobre prazo de ativação (2 dias úteis)

---

## 🔄 Fluxo de Pagamento

### PIX
```
1. Usuário seleciona PIX e clica em "Finalizar Pagamento"
2. Sistema chama api/mercadopago_create_payment.php
3. Mercado Pago retorna QR Code e código copia-e-cola
4. Modal PIX é exibido
5. Sistema verifica pagamento a cada 5 segundos
6. Quando aprovado, plano é ativado automaticamente
7. Usuário é redirecionado para página de planos
```

### Cartão de Crédito
```
1. Usuário preenche dados do cartão
2. Sistema valida campos
3. Chama api/mercadopago_create_payment.php
4. Se aprovado, ativa plano imediatamente
5. Redireciona para página de planos
```

### Boleto
```
1. Usuário seleciona Boleto
2. Sistema gera boleto via API
3. Modal com link do boleto é exibido
4. Usuário paga o boleto
5. Webhook recebe notificação de aprovação
6. Plano é ativado automaticamente
```

---

## 🐛 Debug e Logs

### Logs do Webhook
Todos os webhooks são registrados em:
```
data/mercadopago_webhook_log.txt
```

**Exemplo de log:**
```
2025-10-26 10:00:00 - {"type":"payment","data":{"id":"1234567890"}}
2025-10-26 10:05:00 - PLANO ATIVADO: Dentista abc123 - Plano mensal
```

### Como testar

1. **Teste PIX no Sandbox:**
   - Use o ambiente de teste
   - PIX de teste é aprovado automaticamente após alguns segundos

2. **Teste Cartão no Sandbox:**
   - Número: `5031 4332 1540 6351`
   - CVV: `123`
   - Validade: `11/25`

3. **Verificar logs:**
   ```bash
   tail -f data/mercadopago_webhook_log.txt
   ```

---

## 📊 Status de Pagamento

O Mercado Pago retorna os seguintes status:

| Status | Descrição |
|--------|-----------|
| `pending` | Pagamento pendente |
| `approved` | Pagamento aprovado ✅ |
| `authorized` | Pagamento autorizado (aguardando captura) |
| `in_process` | Pagamento em processamento |
| `in_mediation` | Pagamento em mediação/disputa |
| `rejected` | Pagamento rejeitado ❌ |
| `cancelled` | Pagamento cancelado |
| `refunded` | Pagamento reembolsado |
| `charged_back` | Pagamento com chargeback |

---

## 🔐 Segurança

✅ **Implementado:**
- Comunicação via HTTPS
- Tokens de acesso seguros
- Validação de usuário logado
- Verificação de status na API oficial

⚠️ **Recomendações:**
- Mantenha as credenciais em arquivo `.env` (não suba para Git)
- Use HTTPS em produção
- Configure webhook com autenticação

---

## 🚀 Como Usar

### Para o Dentista (Frontend):

1. Acesse `planos.php`
2. Escolha um plano
3. Clique em "Assinar Plano"
4. Preencha os dados de pagamento
5. Clique em "Finalizar Pagamento Seguro"
6. Para PIX: Escaneie o QR Code ou copie o código
7. Para Boleto: Clique em "Visualizar Boleto"
8. Para Cartão: Aguarde aprovação automática

### Para o Desenvolvedor:

**Ativar modo de teste:**
```php
// config/mercadopago.php
private static $ambiente = 'test';
```

**Ativar modo de produção:**
```php
// config/mercadopago.php
private static $ambiente = 'production';
```

**Atualizar URL do webhook:**
```php
// api/mercadopago_create_payment.php (linha 43)
'notification_url' => 'https://SEUDOMINIO.com/api/mercadopago_webhook.php',
```

---

## 📞 Suporte

- **Documentação Oficial:** https://www.mercadopago.com.br/developers/pt/docs
- **Painel de Desenvolvedores:** https://www.mercadopago.com.br/developers/panel
- **Status da API:** https://status.mercadopago.com/

---

## ✅ Checklist de Implementação

- [x] Configuração de credenciais
- [x] Criação de pagamento via API
- [x] Geração de QR Code PIX
- [x] Modal para exibir PIX
- [x] Verificação automática de pagamento
- [x] Geração de boleto
- [x] Modal para boleto
- [x] Processamento de cartão
- [x] Webhook para notificações
- [x] Ativação automática de planos
- [x] Logs de debug
- [x] Interface moderna e responsiva

---

**Implementado com ❤️ para o Odontfy**

Data: 26/10/2025

