# 💳 Integração Completa Mercado Pago - Odontfy

## ✅ Formas de Pagamento Implementadas

### 1. PIX 📱
- ✅ Geração de QR Code
- ✅ Código copia-e-cola
- ✅ Modal bonito
- ✅ Verificação automática a cada 5 segundos
- ✅ Notificação toast quando aprovado
- ✅ Ativação automática do plano

### 2. Cartão de Crédito 💳
- ✅ Tokenização segura via SDK do Mercado Pago
- ✅ Validação de campos
- ✅ Formatação automática
- ✅ Processamento imediato
- ✅ Notificações para cada status (aprovado, pendente, recusado)
- ✅ Ativação automática quando aprovado

### 3. Boleto Bancário 🏦
- ✅ Geração de boleto
- ✅ Modal com link para visualizar/imprimir
- ✅ Ativação automática via webhook após pagamento

---

## 🔐 Segurança do Cartão de Crédito

### Como funciona a tokenização?

1. **Usuário preenche dados do cartão**
2. **Frontend cria um token** usando SDK do Mercado Pago
   - Dados do cartão NUNCA passam pelo seu servidor
   - Token tem validade curta (alguns minutos)
3. **Token é enviado ao backend**
4. **Backend usa token para processar pagamento**
5. **Mercado Pago retorna resultado**

### Vantagens:

- ✅ **PCI DSS Compliant** - Você não armazena dados de cartão
- ✅ **Seguro** - Dados sensíveis ficam apenas com Mercado Pago
- ✅ **Rápido** - Aprovação em segundos
- ✅ **Confiável** - SDK oficial do Mercado Pago

---

## 🎯 Fluxo de Pagamento

### PIX
```
1. Usuário seleciona PIX
2. Clica em "Finalizar Pagamento"
3. Sistema cria pagamento via API
4. Mercado Pago retorna QR Code
5. Modal exibe QR Code e código
6. Sistema verifica pagamento a cada 5s
7. Quando aprovado:
   - Fecha modal
   - Mostra notificação toast
   - Ativa plano automaticamente
   - Redireciona para planos
```

### Cartão de Crédito
```
1. Usuário preenche dados do cartão
2. Clica em "Finalizar Pagamento"
3. SDK do Mercado Pago cria token do cartão
4. Token + dados são enviados para API
5. API processa pagamento com token
6. Mercado Pago retorna status:
   
   ✅ APROVADO:
   - Notificação verde "Pagamento aprovado!"
   - Ativa plano imediatamente
   - Redireciona para planos
   
   ⏳ PENDENTE:
   - Notificação azul "Pagamento pendente"
   - Aguarda confirmação (webhook ativa depois)
   
   ❌ RECUSADO:
   - Notificação vermelha com motivo
   - Permite tentar novamente
```

### Boleto
```
1. Usuário seleciona Boleto
2. Clica em "Finalizar Pagamento"
3. Sistema gera boleto via API
4. Modal exibe link do boleto
5. Usuário visualiza/imprime boleto
6. Após pagamento (1-2 dias):
   - Webhook recebe notificação
   - Plano é ativado automaticamente
```

---

## 🧪 Como Testar

### Ambiente de Teste (Sandbox)

Edite `config/mercadopago.php` linha 8:
```php
private static $ambiente = 'test';
```

### Cartões de Teste:

**Mastercard - Aprovado:**
```
Número: 5031 4332 1540 6351
CVV: 123
Validade: 11/25
Nome: APRO
```

**Visa - Aprovado:**
```
Número: 4509 9535 6623 3704
CVV: 123
Validade: 11/25
Nome: APRO
```

**Mastercard - Recusado:**
```
Número: 5031 4332 1540 6351
CVV: 123
Validade: 11/25
Nome: OTHE
```

### PIX de Teste:
- No ambiente de teste, o PIX é aprovado automaticamente após alguns segundos

### Boleto de Teste:
- No ambiente de teste, o boleto é gerado normalmente
- Você pode simular o pagamento manualmente no painel do Mercado Pago

---

## 📊 Status de Pagamento

| Status | Descrição | O que acontece |
|--------|-----------|----------------|
| `approved` | ✅ Aprovado | Plano ativado imediatamente |
| `pending` | ⏳ Pendente | Aguarda confirmação (ex: boleto) |
| `authorized` | 🔒 Autorizado | Aguarda captura |
| `in_process` | ⚙️ Processando | Aguarda processamento |
| `rejected` | ❌ Recusado | Não aprovado (saldo insuf., dados inválidos) |
| `cancelled` | 🚫 Cancelado | Pagamento cancelado |
| `refunded` | 💸 Reembolsado | Valor devolvido |

---

## 🎨 Notificações Toast

Substituímos todos os `alert()` por notificações modernas:

### Tipos:

**Success (Verde)** ✅
- Pagamento aprovado
- Código PIX copiado
- Plano ativado

**Error (Vermelho)** ❌
- Erro ao processar
- Pagamento recusado
- Problemas de conexão

**Warning (Amarelo)** ⚠️
- Campos não preenchidos
- Validações

**Info (Azul)** ℹ️
- Pagamento pendente
- Informações gerais

### Recursos:
- ✅ Animação suave (entrada da direita)
- ✅ Barra de progresso
- ✅ Auto-close após 5 segundos
- ✅ Botão fechar manual
- ✅ Empilhamento de múltiplas notificações

---

## 🔧 Configuração do SDK

O SDK do Mercado Pago é carregado automaticamente:

```html
<script src="https://sdk.mercadopago.com/js/v2"></script>
```

E inicializado com sua Public Key:

```javascript
const mp = new MercadoPago('SUA_PUBLIC_KEY', {
    locale: 'pt-BR'
});
```

---

## 🐛 Troubleshooting

### Erro: "Token do cartão não fornecido"
- Verifique se o SDK está carregado
- Confira se a Public Key está correta
- Veja o console do navegador para erros do SDK

### Erro: "Payment method not found"
- Certifique-se de usar cartões válidos
- No teste, use os cartões fornecidos acima

### Erro: "Header X-Idempotency-Key can't be null"
- ✅ Já corrigido! Adicionamos automaticamente

### PIX não gera QR Code
- Verifique as credenciais
- Confirme que está no ambiente correto (test/production)
- Veja os logs em `C:\xampp\apache\logs\error.log`

### Boleto não abre
- Verifique se o `boleto_url` está sendo retornado
- Veja o console do navegador
- Confira os logs da API

---

## 📝 Estrutura de Código

### Frontend (`pagamento.php`):

1. **SDK do Mercado Pago** - Carregado no `<head>`
2. **Inicialização** - `const mp = new MercadoPago()`
3. **Processamento:**
   - `processarPagamento()` - Principal
   - `processarPagamentoCartao()` - Tokeniza e envia
   - `processarPagamentoPix()` - Cria PIX
   - `processarPagamentoBoleto()` - Gera boleto
4. **Notificações:** - `showToast()` - Exibe notificações
5. **Modais:** - Modal PIX e Modal Boleto

### Backend (`api/mercadopago_create_payment.php`):

1. **Validação** - Verifica dados recebidos
2. **Configuração:**
   - Para **cartão**: Usa token + payment_method_id
   - Para **PIX/Boleto**: Usa payment_method_id direto
3. **Requisição** - Envia para API do Mercado Pago
4. **Resposta:**
   - Salva em `data/pagamentos.json`
   - Retorna dados (QR Code, boleto_url, status)

---

## ✅ Checklist de Implementação

### Cartão de Crédito:
- [x] SDK do Mercado Pago carregado
- [x] Inicialização do SDK
- [x] Tokenização de cartão
- [x] Validação de campos
- [x] Formatação automática
- [x] Processamento via API
- [x] Tratamento de status (aprovado/pendente/recusado)
- [x] Notificações toast
- [x] Ativação automática do plano

### PIX:
- [x] Criação de pagamento
- [x] Geração de QR Code
- [x] Modal bonito
- [x] Código copia-e-cola
- [x] Botão copiar
- [x] Verificação automática
- [x] Notificação de aprovação
- [x] Ativação automática

### Boleto:
- [x] Geração de boleto
- [x] Modal com link
- [x] Webhook para ativação
- [x] Notificações

### Geral:
- [x] Notificações toast personalizadas
- [x] Animações suaves
- [x] Tratamento de erros
- [x] Logs detalhados
- [x] Ambiente test/production
- [x] Idempotency Key

---

## 🚀 Para Produção

1. **Altere o ambiente:**
```php
// config/mercadopago.php linha 8
private static $ambiente = 'production';
```

2. **Configure o webhook:**
- Acesse: https://www.mercadopago.com.br/developers/panel/notifications/webhooks
- Cadastre: `https://SEUDOMINIO.com/api/mercadopago_webhook.php`

3. **Atualize notification_url:**
```php
// api/mercadopago_create_payment.php linha 45
'notification_url' => 'https://SEUDOMINIO.com/api/mercadopago_webhook.php',
```

4. **Teste com cartão real** (valores baixos primeiro)

5. **Monitore os logs:**
```
data/mercadopago_webhook_log.txt
C:\xampp\apache\logs\error.log
```

---

## 🎉 Pronto!

Agora você tem um sistema completo de pagamentos com:

- ✅ **PIX** - Instantâneo
- ✅ **Cartão** - Aprovação imediata
- ✅ **Boleto** - Para quem prefere

Tudo seguro, bonito e funcional! 🚀

**Data de implementação:** 26/10/2025

