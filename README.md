# 🦷 Odontfy - Mini SaaS de Agendamento Odontológico

Sistema completo de agendamento online para consultórios odontológicos, desenvolvido em PHP puro para testes rápidos.

## 🚀 Funcionalidades

### Para Dentistas/Clínicas
- ✅ Cadastro e login seguro
- ✅ Dashboard com estatísticas
- ✅ Configuração de horários de atendimento
- ✅ Gerenciamento de serviços
- ✅ Link personalizado para agendamento
- ✅ Visualização de todos os agendamentos
- ✅ Confirmação/cancelamento de consultas
- ✅ Período de teste grátis de 7 dias

### Para Pacientes
- ✅ Acesso à página pública da clínica
- ✅ Seleção de serviço, data e horário
- ✅ Formulário de agendamento intuitivo
- ✅ Confirmação de agendamento
- ✅ Interface responsiva e moderna

## 🛠️ Tecnologias Utilizadas

- **Backend**: PHP 7.4+
- **Frontend**: HTML5, CSS3, JavaScript
- **Styling**: Tailwind CSS (CDN)
- **Ícones**: Font Awesome
- **Armazenamento**: Arquivos JSON (simulação de banco)
- **Servidor**: Apache (XAMPP)

## 📁 Estrutura do Projeto

```
odontfy/
├── config/
│   └── database.php          # Sistema de banco simulado
├── data/                     # Arquivos JSON (criados automaticamente)
│   ├── dentistas.json
│   ├── agendamentos.json
│   └── configuracoes.json
├── index.php                 # Landing page
├── register.php             # Cadastro de dentistas
├── login.php                # Login de dentistas
├── logout.php               # Logout
├── dashboard.php            # Painel do dentista
├── agendamentos.php         # Lista de agendamentos
├── agendar.php              # Página pública de agendamento
├── confirmar.php            # Confirmação de agendamento
├── 404.php                  # Página de erro
├── auth.php                 # Sistema de autenticação
├── .htaccess               # Configuração de rotas
└── README.md               # Este arquivo
```

## 🚀 Como Executar

### Pré-requisitos
- XAMPP (Apache + PHP)
- Navegador web moderno

### Instalação
1. Clone ou baixe o projeto
2. Coloque na pasta `htdocs` do XAMPP
3. Inicie o Apache no XAMPP
4. Acesse `http://localhost/odontfy`

### Primeiro Uso
1. Acesse a página inicial
2. Clique em "Cadastrar" para criar uma conta de dentista
3. Preencha os dados da clínica
4. Acesse o dashboard para configurar horários e serviços
5. Compartilhe seu link personalizado com pacientes

## 🎯 Fluxo de Uso

### Para Dentistas
1. **Cadastro**: Criar conta com dados da clínica
2. **Configuração**: Definir horários e serviços
3. **Link**: Compartilhar link personalizado
4. **Gestão**: Acompanhar agendamentos no dashboard

### Para Pacientes
1. **Acesso**: Usar link da clínica (ex: `odontfy.com/sua-clinica`)
2. **Agendamento**: Preencher formulário com dados pessoais
3. **Confirmação**: Receber confirmação do agendamento
4. **Aguardar**: Ser notificado sobre confirmação da clínica

## 🔧 Configurações

### Horários de Atendimento
- Configuração por dia da semana
- Horário de início e fim
- Ativação/desativação por dia

### Serviços
- Nome do serviço
- Duração em minutos
- Preço (opcional)
- Status ativo/inativo

## 📊 Recursos do Dashboard

- **Estatísticas**: Total de agendamentos, agendamentos do dia
- **Próximos Agendamentos**: Lista dos próximos 5 agendamentos
- **Ações Rápidas**: Links para configurações e relatórios
- **Link Compartilhável**: Link personalizado para agendamentos

## 🎨 Design

- Interface moderna e responsiva
- Cores principais: Azul (#00B4D8) e branco
- Tipografia: Sistema de fontes do sistema
- Ícones: Font Awesome
- Layout: Tailwind CSS

## 🔒 Segurança

- Autenticação por sessões PHP
- Validação de dados de entrada
- Sanitização de outputs
- Proteção contra XSS básica

## 📈 Próximas Funcionalidades

- [ ] Integração com Stripe/Mercado Pago
- [ ] Notificações por email
- [ ] Notificações por WhatsApp
- [ ] Relatórios avançados
- [ ] Sistema de assinatura automática
- [ ] Painel administrativo
- [ ] Banco de dados real (MySQL/PostgreSQL)

## 🐛 Solução de Problemas

### Erro 500
- Verifique se o Apache está rodando
- Confirme as permissões da pasta `data/`

### Página não carrega
- Verifique se o arquivo `.htaccess` está presente
- Confirme se o mod_rewrite está habilitado

### Dados não salvam
- Verifique as permissões da pasta `data/`
- Confirme se o PHP tem permissão de escrita

## 📞 Suporte

Para dúvidas ou problemas:
1. Verifique este README
2. Confirme se todos os pré-requisitos estão instalados
3. Verifique os logs de erro do Apache

## 📄 Licença

Este projeto é para fins educacionais e de demonstração.

---

**Desenvolvido com ❤️ para simplificar agendamentos odontológicos**
