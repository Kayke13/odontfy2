# 🔔 Sistema de Notificações em Tempo Real

## Visão Geral

Sistema completo de notificações push que atualiza automaticamente a dashboard do dentista quando um novo agendamento é feito, sem necessidade de recarregar a página (F5).

---

## ✨ Funcionalidades

### 1. **Notificação Visual (Toast)**
- ✅ Toast animado que aparece no canto superior direito
- ✅ Mostra detalhes do novo agendamento:
  - Nome do paciente
  - Serviço solicitado
  - Data e horário
  - Telefone de contato
- ✅ Auto-fecha após 10 segundos
- ✅ Botão manual para fechar
- ✅ Design moderno com animações suaves

### 2. **Notificação Sonora**
- ✅ Som agradável de 3 notas musicais (Dó, Mi, Sol)
- ✅ Usa Web Audio API para máxima compatibilidade
- ✅ Fallback para navegadores antigos
- ✅ Volume controlado e não invasivo

### 3. **Badge de Notificação**
- ✅ Ícone de sino no cabeçalho
- ✅ Contador vermelho com animação pulsante
- ✅ Mostra quantidade de novos agendamentos
- ✅ Desaparece automaticamente após 30 segundos

### 4. **Atualização Automática**
- ✅ Estatísticas atualizam automaticamente:
  - Total de Agendamentos
  - Agendamentos Hoje
- ✅ Calendário recarrega automaticamente
- ✅ Animações de transição nos números
- ✅ Sem necessidade de refresh manual

---

## 🔧 Arquitetura Técnica

### Arquivos Criados/Modificados

1. **`api/check_new_bookings.php`** (NOVO)
   - Endpoint API para verificar novos agendamentos
   - Retorna apenas agendamentos criados após último check
   - Fornece estatísticas atualizadas
   - Formato JSON de resposta

2. **`dashboard.php`** (MODIFICADO)
   - Toast de notificação HTML
   - Badge de notificação no header
   - JavaScript para polling
   - Funções de atualização em tempo real

---

## 📊 Fluxo de Funcionamento

```
┌─────────────────────────────────────────────────────────┐
│  PACIENTE FAZ AGENDAMENTO (agendar.php)                │
│  ↓                                                       │
│  Salvo em data/agendamentos.json com timestamp         │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│  DASHBOARD DO DENTISTA (polling a cada 5 segundos)     │
│  ↓                                                       │
│  JavaScript: checkNewBookings()                         │
│  ↓                                                       │
│  Fetch API: api/check_new_bookings.php                 │
│  ↓                                                       │
│  Compara: last_check vs created_at                     │
└─────────────────────────────────────────────────────────┘
                          ↓
          ┌───────────────┴───────────────┐
          │ HÁ NOVOS AGENDAMENTOS?        │
          └───────────────┬───────────────┘
                          ↓
                ┌─────────┴─────────┐
                │       SIM         │
                └─────────┬─────────┘
                          ↓
    ┌────────────────────────────────────────┐
    │  1. Toca Som de Notificação            │
    │  2. Mostra Toast com Detalhes          │
    │  3. Atualiza Badge no Header           │
    │  4. Atualiza Estatísticas (animado)   │
    │  5. Recarrega Calendário               │
    └────────────────────────────────────────┘
```

---

## ⚙️ Configurações e Intervalos

### Intervalo de Verificação
```javascript
// Verifica novos agendamentos a cada 5 segundos
setInterval(() => {
    checkNewBookings();
}, 5000);
```

### Intervalo de Atualização do Calendário
```javascript
// Atualiza calendário a cada 30 segundos
setInterval(() => {
    loadCalendarData();
}, 30000);
```

### Tempo de Exibição da Notificação
```javascript
// Toast desaparece após 10 segundos
setTimeout(() => {
    closeNotification();
}, 10000);

// Badge desaparece após 30 segundos
setTimeout(() => {
    notificationBadge.classList.add('hidden');
}, 30000);
```

---

## 🎵 Sistema de Som

### Web Audio API
O sistema usa a Web Audio API nativa do navegador para criar um som agradável:

```javascript
const frequencies = [523.25, 659.25, 783.99]; // C5, E5, G5 (Dó, Mi, Sol)
```

**Características:**
- 3 notas em sequência
- Envelope suave (fade in/out)
- Duração de 0.15s por nota
- Volume controlado (30% do máximo)

### Fallback
Para navegadores sem suporte à Web Audio API, usa um arquivo de áudio base64 embutido.

---

## 📱 Responsividade

O sistema funciona perfeitamente em:
- ✅ Desktop
- ✅ Tablet
- ✅ Mobile

### Adaptações Mobile:
- Toast redimensiona automaticamente
- Badge permanece visível no header
- Animações otimizadas para performance

---

## 🔐 Segurança

### Autenticação
```php
// check_new_bookings.php
if (!Auth::isLoggedIn()) {
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}
```

### Validação de Dados
- Verifica sessão do dentista
- Filtra apenas agendamentos do dentista logado
- Sanitiza dados antes de enviar ao frontend

---

## 📊 Resposta da API

### Estrutura JSON
```json
{
    "success": true,
    "has_new": true,
    "new_count": 1,
    "new_bookings": [
        {
            "id": "abc123",
            "paciente_nome": "João Silva",
            "paciente_email": "joao@email.com",
            "paciente_telefone": "(11) 99999-9999",
            "servico": "Consulta",
            "data": "2025-10-26 14:00:00",
            "status": "pendente",
            "created_at": "2025-10-25 10:30:00"
        }
    ],
    "stats": {
        "total": 45,
        "hoje": 3
    },
    "proximos": [],
    "current_time": "2025-10-25 10:30:05"
}
```

---

## 🎨 Customização

### Alterar Intervalo de Verificação
No arquivo `dashboard.php`, linha ~900:
```javascript
// Altere de 5000 (5 segundos) para o valor desejado em milissegundos
setInterval(() => {
    checkNewBookings();
}, 5000); // ← Altere aqui
```

### Alterar Tempo do Toast
No arquivo `dashboard.php`, linha ~844:
```javascript
// Altere de 10000 (10 segundos) para o valor desejado
setTimeout(() => {
    closeNotification();
}, 10000); // ← Altere aqui
```

### Alterar Som da Notificação
No arquivo `dashboard.php`, linha ~759:
```javascript
// Altere as frequências para mudar as notas
const frequencies = [523.25, 659.25, 783.99]; // ← Altere aqui
```

**Exemplos de notas:**
- Dó (C5): 523.25 Hz
- Ré (D5): 587.33 Hz
- Mi (E5): 659.25 Hz
- Fá (F5): 698.46 Hz
- Sol (G5): 783.99 Hz
- Lá (A5): 880.00 Hz

---

## 🐛 Troubleshooting

### Notificações Não Aparecem
1. Verifique o console do navegador (F12)
2. Confirme que a API está respondendo: `api/check_new_bookings.php`
3. Verifique se o dentista está logado
4. Limpe o cache do navegador

### Som Não Toca
1. Verifique se o navegador permite autoplay de áudio
2. Alguns navegadores bloqueiam áudio até interação do usuário
3. Teste em um navegador diferente
4. Verifique o volume do sistema

### Estatísticas Não Atualizam
1. Verifique a estrutura HTML dos cards de estatísticas
2. Confirme que as classes CSS estão corretas
3. Abra o console e veja se há erros JavaScript

---

## 🚀 Testes

### Testar o Sistema
1. Abra a dashboard em uma aba: `dashboard.php`
2. Abra a página de agendamento em outra aba: `agendar.php?username=SEU_USERNAME`
3. Faça um novo agendamento
4. Aguarde até 5 segundos
5. Você deve ver:
   - ✅ Toast aparecer no canto superior direito
   - ✅ Som de notificação tocar
   - ✅ Badge aparecer no header
   - ✅ Números das estatísticas aumentarem

---

## 📈 Performance

### Otimizações Implementadas
- ✅ Polling inteligente (só busca novos)
- ✅ Timestamp tracking (evita reprocessamento)
- ✅ Debouncing de atualizações
- ✅ Lazy loading de dados
- ✅ Animações CSS (hardware accelerated)

### Consumo de Recursos
- **Bandwidth**: ~1-2 KB por request a cada 5s
- **CPU**: Mínimo (apenas durante animações)
- **Memória**: < 1 MB adicional

---

## 🎯 Próximas Melhorias Possíveis

1. **WebSockets** para notificações instantâneas
2. **Service Workers** para notificações de navegador
3. **Push Notifications** para mobile
4. **Histórico de notificações** visualizável
5. **Preferências de notificação** (ativar/desativar som)
6. **Notificações por email/SMS** para agendamentos

---

## 📝 Notas Importantes

- O sistema usa **polling** (não WebSockets) para máxima compatibilidade
- Funciona em **qualquer servidor** (não requer Node.js ou tecnologias especiais)
- **Não sobrecarrega** o servidor (requests leves e espaçados)
- **Compatível** com PHP 7.0+
- **Responsivo** e funciona em todos os dispositivos

---

## 👨‍💻 Suporte

Para dúvidas ou problemas:
1. Verifique este documento primeiro
2. Consulte o console do navegador (F12)
3. Revise os logs de erro do PHP
4. Teste em navegador diferente

---

**Sistema desenvolvido com ❤️ para OdontoFy**

