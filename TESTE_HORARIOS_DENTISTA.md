# 🧪 Guia de Teste - Horários do Dentista

## 🎯 Objetivo
Verificar se os horários do dentista adicional estão sendo usados corretamente no agendamento.

---

## 📋 Pré-requisitos

### 1. Configure horários DIFERENTES para cada dentista:

**Consultório (Principal - Matheus Santos):**
- Segunda a Quinta: 08:00 - 18:00
- Sexta: 09:00 - 17:00 ✅ (DIFERENTE!)
- Sábado: 08:00 - 12:00

**Dentista Adicional (Kayke Lira):**
- Segunda a Quinta: 08:00 - 18:00
- Sexta: DESATIVADO ❌ (DIFERENTE!)
- Sábado: DESATIVADO ❌ (DIFERENTE!)
- Domingo: 08:00 - 18:00 ✅ (DIFERENTE!)

---

## 🧪 Teste 1: Dentista Principal (Baseline)

### Passos:
1. Acesse: `http://localhost/odontfy2/agendar.php?username=belinnato`
2. Preencha dados pessoais (Passo 1)
3. **Passo 2:** Selecione **Matheus Santos (Principal)**
4. **Passo 3:** Escolha um serviço
5. **Passo 4:** Escolha uma **SEXTA-FEIRA** (exemplo: 31/10/2025)
6. Clique em "Próximo"

### ✅ Resultado Esperado:
```
┌──────────────────────────────────────────┐
│ 👨‍⚕️ Dentista: Matheus Santos          │
├──────────────────────────────────────────┤
│ ℹ️ Horários do consultório principal    │
├──────────────────────────────────────────┤
│ Horários: 09:00 - 17:00                  │
│ (porque sexta é 09:00-17:00)             │
└──────────────────────────────────────────┘
```

### 🔍 Console (F12):
```
🔵 USANDO DENTISTA PRINCIPAL
🕐 FONTE DOS HORÁRIOS: consultorio_principal
Horários disponíveis: ["09:00", "09:30", "10:00", ..., "16:30"]
```

---

## 🧪 Teste 2: Dentista Adicional em SEXTA-FEIRA

### Passos:
1. Acesse: `http://localhost/odontfy2/agendar.php?username=belinnato`
2. Preencha dados pessoais (Passo 1)
3. **Passo 2:** Selecione **Kayke Lira** ⭐
4. **Passo 3:** Escolha um serviço
5. **Passo 4:** Escolha uma **SEXTA-FEIRA** (exemplo: 31/10/2025)
6. Clique em "Próximo"

### ✅ Resultado Esperado:
```
┌──────────────────────────────────────────┐
│ 👨‍⚕️ Dentista: Kayke Lira               │
├──────────────────────────────────────────┤
│ ℹ️ Horários específicos de Kayke Lira   │
├──────────────────────────────────────────┤
│ ❌ NENHUM HORÁRIO DISPONÍVEL             │
│ "Dentista não atende neste dia"          │
│ (porque sexta está DESATIVADA)           │
└──────────────────────────────────────────┘
```

### 🔍 Console (F12):
```
🟢 USANDO DENTISTA ADICIONAL: 68fd9d0ca3146
🕐 FONTE DOS HORÁRIOS: dentista_Kayke Lira
Horários disponíveis: []
Mensagem: Dentista não atende neste dia da semana
```

---

## 🧪 Teste 3: Dentista Adicional em QUINTA-FEIRA

### Passos:
1. Acesse: `http://localhost/odontfy2/agendar.php?username=belinnato`
2. Preencha dados pessoais (Passo 1)
3. **Passo 2:** Selecione **Kayke Lira** ⭐
4. **Passo 3:** Escolha um serviço
5. **Passo 4:** Escolha uma **QUINTA-FEIRA** (exemplo: 30/10/2025)
6. Clique em "Próximo"

### ✅ Resultado Esperado:
```
┌──────────────────────────────────────────┐
│ 👨‍⚕️ Dentista: Kayke Lira               │
├──────────────────────────────────────────┤
│ ℹ️ Horários específicos de Kayke Lira   │
├──────────────────────────────────────────┤
│ Horários: 08:00 - 18:00                  │
│ ["08:00", "08:30", ..., "17:30"]         │
└──────────────────────────────────────────┘
```

### 🔍 Console (F12):
```
🟢 USANDO DENTISTA ADICIONAL: 68fd9d0ca3146
🕐 FONTE DOS HORÁRIOS: dentista_Kayke Lira
Horários disponíveis: ["08:00", "08:30", "09:00", ..., "17:30"]
```

---

## 🧪 Teste 4: Dentista Adicional em DOMINGO

### Passos:
1. Acesse: `http://localhost/odontfy2/agendar.php?username=belinnato`
2. Preencha dados pessoais (Passo 1)
3. **Passo 2:** Selecione **Kayke Lira** ⭐
4. **Passo 3:** Escolha um serviço
5. **Passo 4:** Escolha um **DOMINGO** (exemplo: 02/11/2025)
6. Clique em "Próximo"

### ✅ Resultado Esperado:
```
┌──────────────────────────────────────────┐
│ 👨‍⚕️ Dentista: Kayke Lira               │
├──────────────────────────────────────────┤
│ ℹ️ Horários específicos de Kayke Lira   │
├──────────────────────────────────────────┤
│ Horários: 08:00 - 18:00                  │
│ (Kayke atende domingo!)                  │
└──────────────────────────────────────────┘
```

### 🔍 Console (F12):
```
🟢 USANDO DENTISTA ADICIONAL: 68fd9d0ca3146
🕐 FONTE DOS HORÁRIOS: dentista_Kayke Lira
Horários disponíveis: ["08:00", "08:30", "09:00", ..., "17:30"]
```

---

## 📊 Comparação Visual

### Consultório Principal vs Kayke Lira

| Dia        | Matheus (Principal) | Kayke Lira         | Diferença |
|------------|--------------------|--------------------|-----------|
| Segunda    | 08:00 - 18:00      | 08:00 - 18:00      | Igual     |
| Terça      | 08:00 - 18:00      | 08:00 - 18:00      | Igual     |
| Quarta     | 08:00 - 18:00      | 08:00 - 18:00      | Igual     |
| Quinta     | 08:00 - 18:00      | 08:00 - 18:00      | Igual     |
| **Sexta**  | **09:00 - 17:00**  | **❌ FECHADO**     | **DIFERENTE** |
| **Sábado** | **08:00 - 12:00**  | **❌ FECHADO**     | **DIFERENTE** |
| **Domingo**| **❌ FECHADO**     | **08:00 - 18:00**  | **DIFERENTE** |

---

## 🐛 Se NÃO Funcionar

### 1. Limpe o Cache do Navegador:
```
1. Pressione Ctrl + Shift + Delete
2. Marque "Cache" e "Cookies"
3. Limpe os dados
4. Recarregue a página (Ctrl + F5)
```

### 2. Verifique o Console (F12):
```javascript
// Procure por estas mensagens:
=== Buscando horários ===
Consultório ID: 68fd8441beece
Dentista escolhido: 68fd9d0ca3146  // ← Deve ser o ID, não "principal"
🟢 USANDO DENTISTA ADICIONAL: 68fd9d0ca3146
🕐 FONTE DOS HORÁRIOS: dentista_Kayke Lira
```

### 3. Verifique o Network (Aba Network no F12):
```
1. Abra F12
2. Vá na aba "Network"
3. Filtre por "get_dentista_available_times"
4. Clique na requisição
5. Veja os parâmetros:
   - consultorio_id: 68fd8441beece
   - dentista_escolhido: 68fd9d0ca3146 ✅ (ID correto)
   - data: 2025-10-30
```

### 4. Teste a API Diretamente:
```
Abra no navegador:
http://localhost/odontfy2/api/get_dentista_available_times.php?consultorio_id=68fd8441beece&dentista_escolhido=68fd9d0ca3146&data=2025-10-30

Verifique:
- "fonte_horarios": "dentista_Kayke Lira" ✅
- Horários corretos para quinta-feira
```

---

## ✅ Checklist de Verificação

- [ ] Horários do consultório configurados
- [ ] Horários do dentista adicional configurados DIFERENTES
- [ ] Cache do navegador limpo
- [ ] Console do navegador aberto (F12)
- [ ] Logs aparecem corretamente
- [ ] Box roxo mostra "Dentista: Kayke Lira"
- [ ] Box azul mostra fonte dos horários
- [ ] Horários diferentes entre dentistas
- [ ] Sexta-feira: Kayke não tem horários
- [ ] Domingo: Kayke tem horários

---

## 🎯 Resultado Final Esperado

✅ **Dentista Principal (Sexta)** → 09:00-17:00  
✅ **Kayke Lira (Sexta)** → Sem horários  
✅ **Dentista Principal (Domingo)** → Fechado  
✅ **Kayke Lira (Domingo)** → 08:00-18:00  

**Se todos os testes passarem, o sistema está funcionando corretamente!** 🎉

---

## 📝 Anotações

- Os horários são salvos em `data/dentistas_consultorio.json`
- A API está em `api/get_dentista_available_times.php`
- Os logs são detalhados no console
- Caixas visuais mostram o dentista e fonte dos horários

---

**Data do teste:** _____________  
**Resultado:** ☐ Passou ☐ Falhou  
**Observações:** _________________________________

