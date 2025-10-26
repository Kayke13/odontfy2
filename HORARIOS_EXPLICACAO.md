# 🕐 Sistema de Horários - Como Funciona

## 📋 Resumo

O sistema de horários no Odontfy funciona de forma inteligente, priorizando horários específicos dos dentistas quando configurados, e usando horários do consultório como fallback.

---

## 🏥 Configuração de Horários

### 1️⃣ Horários do Consultório (Principal)
**Configurado em:** `configuracoes.php` → Aba "Horários de Atendimento"

- São os horários padrão do consultório
- Usados quando:
  - O paciente seleciona o dentista principal
  - Dentista adicional não tem horários configurados
  - Como fallback em qualquer situação

**Exemplo:**
```
Segunda a Sexta: 08:00 - 18:00
Sábado: 08:00 - 12:00
Domingo: Fechado
```

### 2️⃣ Horários dos Dentistas Adicionais
**Configurado em:** `configuracoes.php` → Aba "Horários dos Dentistas"

- Horários específicos de cada dentista adicional
- Permitem flexibilidade na agenda de cada profissional
- Têm prioridade sobre os horários do consultório

**Exemplo:**
```
Dr. João (Dentista Adicional)
Segunda a Quinta: 09:00 - 17:00
Sexta: Não atende
```

---

## 🔄 Lógica de Prioridade

### Fluxo de Decisão:

```
┌─────────────────────────────────────┐
│ Paciente seleciona um dentista     │
└──────────┬──────────────────────────┘
           │
           ▼
    ┌──────────────┐
    │ É o dentista │
    │  principal?  │
    └──────┬───────┘
           │
      ┌────┴────┐
      │         │
    SIM        NÃO
      │         │
      │         ▼
      │   ┌─────────────────────┐
      │   │ Dentista tem        │
      │   │ horários próprios?  │
      │   └──────┬──────────────┘
      │          │
      │      ┌───┴───┐
      │      │       │
      │     SIM     NÃO
      │      │       │
      │      │       │
      ▼      ▼       ▼
   ┌─────┐ ┌───┐ ┌─────┐
   │  A  │ │ B │ │  A  │
   └─────┘ └───┘ └─────┘

A = Horários do Consultório
B = Horários do Dentista
```

---

## 💻 Implementação Técnica

### Arquivo: `api/get_dentista_available_times.php`

#### Casos de Uso:

1. **Dentista Principal Selecionado**
   ```php
   $fonte_horarios = 'consultorio_principal';
   $horarios_config = $dentista_principal['horarios'];
   ```

2. **Dentista Adicional COM horários configurados**
   ```php
   $fonte_horarios = 'dentista_NomeDoDentista';
   $horarios_config = $dentista_consultorio['horarios'];
   ```

3. **Dentista Adicional SEM horários configurados**
   ```php
   $fonte_horarios = 'consultorio_fallback_sem_horarios';
   $horarios_config = $dentista_principal['horarios'];
   ```

4. **Dentista Adicional com horários vazios**
   ```php
   $fonte_horarios = 'consultorio_fallback_horarios_vazios';
   $horarios_config = $dentista_principal['horarios'];
   ```

---

## 🎯 Experiência do Usuário

### Durante o Agendamento:

1. **Passo 2:** Paciente escolhe o dentista
2. **Passo 4:** Paciente escolhe a data
3. **Passo 5:** Sistema mostra horários disponíveis

**Indicador Visual:**
- 🔵 Box azul mostra de onde vêm os horários:
  - "Horários do consultório principal"
  - "Horários específicos de [Nome do Dentista]"
  - "Horários do consultório (dentista não tem horários configurados)"

### Console do Navegador (F12):

```javascript
=== Buscando horários ===
Consultório ID: 68fd8441beece
Dentista escolhido: 68fd9d0ca3146
Data: 2025-10-28
🕐 FONTE DOS HORÁRIOS: dentista_Kayke Lira
```

---

## 📊 Cenários Práticos

### Cenário 1: Consultório Sem Dentistas Adicionais
```
✅ Dentista Principal: 08:00 - 18:00 (seg-sex)
❌ Dentistas Adicionais: Nenhum

Resultado: Todos os agendamentos usam 08:00 - 18:00
```

### Cenário 2: Consultório Com 2 Dentistas
```
✅ Dr. Silva (Principal): 08:00 - 18:00 (seg-sex)
✅ Dra. Maria: 09:00 - 17:00 (seg-qui)
✅ Dr. João: 14:00 - 22:00 (seg-sáb)

Resultado:
- Agendamentos com Dr. Silva → 08:00 - 18:00
- Agendamentos com Dra. Maria → 09:00 - 17:00
- Agendamentos com Dr. João → 14:00 - 22:00
```

### Cenário 3: Dentista Sem Horários Configurados
```
✅ Dr. Silva (Principal): 08:00 - 18:00 (seg-sex)
⚠️  Dra. Ana: Sem horários configurados

Resultado:
- Agendamentos com Dra. Ana → 08:00 - 18:00 (usa do consultório)
```

---

## 🔍 Debug e Logs

### No Console do Navegador:

```javascript
// Informações detalhadas sobre a busca de horários
console.log('=== Buscando horários ===');
console.log('Consultório ID:', consultorioId);
console.log('Dentista escolhido:', dentistaEscolhido);
console.log('Data:', dateStr);
console.log('🕐 FONTE DOS HORÁRIOS:', fonte_horarios);
```

### Na Resposta da API:

```json
{
  "success": true,
  "horarios_disponiveis": ["08:00", "08:30", "09:00", ...],
  "fonte_horarios": "dentista_Kayke Lira",
  "debug": {
    "horarios_config": {...},
    "horario_dia_ativo": true,
    "total_horarios_possiveis": 20,
    "total_ocupados": 3,
    "fonte": "dentista_Kayke Lira"
  }
}
```

---

## ✅ Vantagens do Sistema

1. **Flexibilidade**: Cada dentista pode ter seu próprio horário
2. **Fallback Inteligente**: Nunca fica sem horários disponíveis
3. **Transparência**: Usuário sabe de onde vêm os horários
4. **Debug Fácil**: Logs detalhados para identificar problemas
5. **Manutenção Simples**: Centralizado e bem documentado

---

## 🛠️ Manutenção

### Para Configurar Horários:

1. **Consultório:**
   - Acesse: Configurações → Horários de Atendimento
   - Configure os dias e horários padrão

2. **Dentistas Adicionais:**
   - Acesse: Configurações → Horários dos Dentistas
   - Clique em "Configurar" no dentista desejado
   - Configure horários específicos

### Para Verificar Problemas:

1. Abra o Console do navegador (F12)
2. Faça um agendamento teste
3. Verifique os logs da API
4. Confirme a `fonte_horarios` retornada

---

## 📝 Notas Importantes

- ⚠️ Horários são salvos em formato 24h (HH:MM)
- ⚠️ Intervalos são de 30 minutos
- ⚠️ Horários ocupados são filtrados automaticamente
- ⚠️ Datas passadas não aparecem no calendário
- ✅ Sistema funciona tanto para plano teste quanto planos pagos

---

**Última atualização:** 26 de Outubro de 2025
**Versão:** 2.0

