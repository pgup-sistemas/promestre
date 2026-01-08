# Promestre - Sistema de Gestão para Professores Autônomos

## Configuração

1. **Banco de Dados:**
   - Acesse o phpMyAdmin (http://localhost/phpmyadmin).
   - Crie um banco de dados chamado `promestre` (ou apenas importe o arquivo, pois ele tenta criar).
   - Importe o arquivo `database.sql` localizado na raiz do projeto.

2. **Configuração de Conexão:**
   - Verifique o arquivo `includes/config.php`.
   - As credenciais padrão estão configuradas para XAMPP:
     - Host: `localhost`
     - User: `root`
     - Pass: `` (vazio)
     - DB: `promestre`


3. **Acesso:**
   - Acesse o sistema pelo navegador: `http://localhost/promestre`

## Correções/Migrações importantes (para login/cadastro)

### Coluna `professores.slug`

O sistema utiliza `professores.slug` para:

- Login (salva `$_SESSION['user_slug']`)
- Links públicos no perfil (ex.: `agendar.php?p=SEU_SLUG`)
- Cadastro (`register.php` insere `slug`)

Se ao cadastrar aparecer o erro:

`SQLSTATE[42S22]: Column not found: 1054 Unknown column 'slug' in 'field list'`

Execute no phpMyAdmin (no banco `promestre`) o SQL abaixo:

```sql
ALTER TABLE professores ADD COLUMN slug VARCHAR(160) NULL;

UPDATE professores
SET slug = CONCAT(
  LOWER(REPLACE(REPLACE(REPLACE(REPLACE(nome,' ','-'),'--','-'),'--','-'),'--','-')),
  '-',
  SUBSTRING(MD5(CONCAT(id, '-', UNIX_TIMESTAMP())), 1, 6)
)
WHERE (slug IS NULL OR slug = '');

ALTER TABLE professores MODIFY slug VARCHAR(160) NOT NULL;
ALTER TABLE professores ADD UNIQUE INDEX idx_professores_slug (slug);
```

Observação: os scripts `database.sql` e `database_updates.sql` já foram atualizados para incluir essa coluna.

### Tabela `contratos_config`

Se ao acessar `contratos_config.php` aparecer o erro:

`SQLSTATE[42S02]: Base table or view not found: 1146 Table 'promestre.contratos_config' doesn't exist`

Crie a tabela executando no phpMyAdmin (no banco `promestre`):

```sql
CREATE TABLE IF NOT EXISTS contratos_config (
  id INT AUTO_INCREMENT PRIMARY KEY,
  professor_id INT NOT NULL,
  conteudo LONGTEXT NOT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_contratos_config_professor (professor_id),
  FOREIGN KEY (professor_id) REFERENCES professores(id)
);
```

## Acesso inicial

Não existe “usuário admin” implementado por coluna `is_admin` neste schema.

Para verificar se existem usuários cadastrados:

```sql
SELECT id, nome, email, slug
FROM professores
ORDER BY id ASC
LIMIT 50;
```

Para acessar o sistema:

- **Login**: `http://localhost/promestre/index.php`
- **Cadastro**: `http://localhost/promestre/register.php`

## Fluxo de login e redefinição de senha (como está no código)

### Login

Arquivo: `index.php`

- O formulário envia `email` e `password` via POST.
- O sistema busca o usuário em `professores` pelo e-mail.
- Valida a senha com `password_verify($password, $user['senha'])`.
- Em caso de sucesso, cria a sessão:

```text
$_SESSION['user_id']
$_SESSION['user_name']
$_SESSION['user_email']
$_SESSION['user_slug']
```

- Redireciona para `dashboard.php`.

### Esqueci minha senha

Arquivo: `esqueci_senha.php`

- O usuário informa o e-mail.
- Se o e-mail existir em `professores`, o sistema:
  - gera um token (`bin2hex(random_bytes(32))`)
  - grava em `recuperacao_senha` com expiração de 1 hora (usando `NOW()` do banco)
  - envia um e-mail com o link:
    `SITE_URL/redefinir_senha.php?token=...`

Observação:

- Se SMTP não estiver configurado, a função `sendMail()` (em `includes/config.php`) grava o conteúdo do e-mail em `email_log.txt` na raiz do projeto.

### Redefinir senha

Arquivo: `redefinir_senha.php`

- Valida se o `token` existe e ainda não expirou:
  - `SELECT * FROM recuperacao_senha WHERE token = ? AND expiracao > NOW()`
- Se válido, permite definir nova senha.
- Atualiza `professores.senha` com `password_hash()`.
- Remove o token usado da tabela `recuperacao_senha`.




## Credenciais Efí: mensalidades dos alunos vs assinatura SaaS do professor

O sistema possui dois contextos de cobrança diferentes:

### 1) Cobranças dos alunos (PIX / Cartão / Assinatura do aluno)

Essas cobranças são **por professor**. Cada professor configura suas credenciais na interface:

- Acesse: `Meu Perfil` (`perfil.php`)
- Configure os campos de integração Efí (Pix/certificado/credenciais)
- Essas configurações ficam salvas no banco na tabela `professores` (ex.: `chave_pix`, `client_id_efi`, `client_secret_efi`, `certificado_efi`, `ambiente_efi`)

Isso permite que **cada professor receba diretamente dos seus alunos**, com a própria conta Efí.

### 2) Assinatura SaaS do sistema (assinatura do professor)

Essa cobrança é **do Promestre para o professor** (SaaS). As credenciais são **globais do sistema** e devem ser configuradas no `.env`:

- `EFI_CHARGES_CLIENT_ID`
- `EFI_CHARGES_CLIENT_SECRET`
- `EFI_ENV`

A tela de assinatura do sistema é:

- `assinatura_sistema.php`

Para testes de valor (ex.: R$ 2,00), use:

- `PROMESTRE_SAAS_VALOR_MENSAL`
- `PROMESTRE_SAAS_VALOR_MIN`

## Funcionalidades Implementadas

- **Login/Cadastro:** Crie sua conta de professor.
- **Dashboard:** Visão geral de alunos e finanças.
- **Alunos:** Cadastro completo com link direto para WhatsApp.
- **Tipos de Aula:** Gerencie seus serviços e preços.
- **Financeiro:** 
  - Gere mensalidades individuais ou em lote.
  - Acompanhe pagamentos (Pendente, Pago, Atrasado).
  - Envie cobranças via WhatsApp.
- **Agenda:** Agende aulas e compromissos.
- **Perfil:** Configure seus dados e chave PIX.

## Tecnologias

- PHP 7.4+
- MySQL
- Bootstrap 5
- FontAwesome 6
# promestre
