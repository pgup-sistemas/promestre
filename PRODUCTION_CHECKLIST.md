# Checklist de Produção - Promestre

## ✅ Configurações já corrigidas

### 1. Arquivo .env.php
- [x] Criado com todas as variáveis de ambiente necessárias
- [x] Suporte a `getenv()` para fallback
- [x] Configurações de banco de dados
- [x] Configurações de email SMTP
- [x] Configurações da API Efi (Cobranças, PIX, Webhooks)
- [x] Configurações de SaaS (valores)

### 2. Arquivo .htaccess
- [x] Redirecionamento HTTP para HTTPS
- [x] Proteção de arquivos sensíveis (.env, .env.php, .htaccess, *.sql)
- [x] Headers de segurança (X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, CSP)
- [x] Configurações de PHP (desabilitar erros em produção)
- [x] Cache de assets (imagens, CSS, JS)
- [x] Compressão GZIP
- [x] Remoção de trailing slash

### 3. Arquivos config.php (raiz e includes/)
- [x] Removidas credenciais hardcoded (eram diferentes entre os arquivos!)
- [x] Carregamento centralizado via .env.php
- [x] Suporte a ambiente development/production
- [x] Error handling seguro
- [x] Configuração de charset utf8mb4
- [x] Prepared statements por padrão

### 4. Arquivo .env-example
- [x] Documentação completa de todas as variáveis
- [x] Exemplos de valores
- [x] Indicação de quais são obrigatórios

---

## ⚠️ Ações necessárias antes de ir para produção

### 1. Credenciais de Produção
> **CRÍTICO:** O arquivo `.env.php` ainda contém valores de exemplo. Você deve:

1. Editar `.env.php` e substituir:
   - `DB_PASS` pela senha real do banco
   - `SMTP_PASS` pela senha de app do Gmail
   - `EFI_CHARGES_CLIENT_ID` e `EFI_CHARGES_CLIENT_SECRET`
   - `EFI_PIX_CLIENT_ID` e `EFI_PIX_CLIENT_SECRET`
   - `EFI_WEBHOOK_SECRET` (gerar novo GUID)
   - `EFI_CERT_PASSWORD` (se houver certificado)

2. Gerar webhook secret:
   ```powershell
   [guid]::NewGuid().ToString("N")
   ```

### 2. Certificados SSL
- [ ] Verificar se SSL está configurado no servidor
- [ ] Testar HTTPS funcionando

### 3. Webhooks da Efi
- [ ] Configurar URLs de webhook na conta Efi:
  - `https://promestre.pageup.net.br/webhook_efiassinaturas.php`
  - `https://promestre.pageup.net.br/webhook_efibank.php`
  - `https://promestre.pageup.net.br/webhook_eficobrancas.php`
- [ ] Configurar secret no painel Efi

### 4. PHP Extensions Necessárias
- [ ] `pdo_mysql`
- [ ] `openssl` (para PIX)
- [ ] `mbstring`
- [ ] `curl`

### 5. Permissões de Arquivos
- [ ] `chmod 644` para arquivos .php
- [ ] `chmod 600` para .env.php (apenas leitura)
- [ ] `chmod 644` para .htaccess

### 6. Configurações do Servidor
- [ ] PHP 7.4+ ou 8.x
- [ ] `display_errors = Off`
- `log_errors = On`
- `error_reporting = 0` (produção)
- [ ] `session.cookie_httponly = 1`
- [ ] `session.cookie_secure = 1` (se HTTPS)

### 7. Banco de Dados
- [ ] Executar migrações em `database.sql`
- [ ] Executar atualizações em `database_updates.sql`
- [ ] Configurar usuário do banco com permissões limitadas

### 8. Email
- [ ] Configurar Gmail App Password ou serviço SMTP alternativo
- [ ] Testar envio de emails

---

## 📋 Variáveis de Ambiente Obrigatórias

| Variável | Descrição | Obrigatório |
|----------|-----------|-------------|
| `DB_HOST` | Host do MySQL | ✅ |
| `DB_NAME` | Nome do banco | ✅ |
| `DB_USER` | Usuário do banco | ✅ |
| `DB_PASS` | Senha do banco | ✅ |
| `SITE_URL` | URL do sistema | ✅ |
| `EFI_CHARGES_CLIENT_ID` | API Cobranças | ✅ |
| `EFI_CHARGES_CLIENT_SECRET` | API Cobranças | ✅ |
| `EFI_WEBHOOK_SECRET` | Assinatura de webhooks | ✅ |
| `SMTP_HOST` | Servidor SMTP | ❌ (fallback: log) |
| `SMTP_USER` | Usuário SMTP | ❌ |
| `SMTP_PASS` | Senha SMTP | ❌ |
| `EFI_PIX_*` | Configurações PIX | ❌ |

---

## 🔒 Checklist de Segurança

- [ ] HTTPS forçado em todas as páginas
- [ ] Arquivos sensíveis protegidos via .htaccess
- [ ] SQL Injection prevenido (PDO prepared statements)
- [ ] XSS prevenido (htmlspecialchars nas funções)
- [ ] Session security configurada
- [ ] Error reporting desabilitado em produção
- [ ] Cookies com HttpOnly e Secure
- [ ] CSP header configurado

---

## 🧪 Testes de Produção

1. **Teste de conexão:** Acessar qualquer página protegida
2. **Teste de HTTPS:** Verificar redirecionamento
3. **Teste de email:** Enviar email de teste
4. **Teste de webhook:** Simular chamada de webhook
5. **Teste de PIX:** Gerar cobrança PIX de teste
6. **Teste de assinatura:** Criar assinatura de teste
7. **Teste de login:** Fazer login e logout
8. **Teste de permissões:** Tentar acessar arquivos protegidos

---

## 📞 Suporte

Em caso de dúvidas sobre configuração:
- Documentação EfiPay: https://dev.efipay.com.br
- PHP Manual: https://www.php.net/manual/pt_BR/
