-- Atualizações do Banco de Dados para novas funcionalidades

USE promestre;

-- 1. Tabela para logs de webhook EfiBank
CREATE TABLE IF NOT EXISTS webhook_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento VARCHAR(50) NOT NULL,
    txid VARCHAR(255),
    payload TEXT NOT NULL,
    assinatura VARCHAR(255),
    processado BOOLEAN DEFAULT FALSE,
    mensalidade_id INT,
    professor_id INT,
    mensagem_erro TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processado_em TIMESTAMP NULL,
    FOREIGN KEY (mensalidade_id) REFERENCES mensalidades(id),
    FOREIGN KEY (professor_id) REFERENCES professores(id),
    INDEX idx_txid (txid),
    INDEX idx_processado (processado)
);

-- 2. Tabela para templates de mensagem WhatsApp
CREATE TABLE IF NOT EXISTS templates_mensagem (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('cobranca', 'lembrete', 'agradecimento', 'aviso', 'personalizado') NOT NULL,
    template TEXT NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES professores(id),
    INDEX idx_professor_tipo (professor_id, tipo)
);

-- 3. Tabela para histórico de notificações enviadas
CREATE TABLE IF NOT EXISTS historico_notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    aluno_id INT,
    mensalidade_id INT,
    template_id INT,
    tipo ENUM('cobranca', 'lembrete', 'agradecimento', 'aviso', 'personalizado') NOT NULL,
    mensagem_template TEXT NOT NULL,
    mensagem_enviada TEXT NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    enviado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES professores(id),
    FOREIGN KEY (aluno_id) REFERENCES alunos(id),
    FOREIGN KEY (mensalidade_id) REFERENCES mensalidades(id),
    FOREIGN KEY (template_id) REFERENCES templates_mensagem(id),
    INDEX idx_professor_enviado (professor_id, enviado_em),
    INDEX idx_aluno_enviado (aluno_id, enviado_em)
);

-- 3b. Tabelas de planos e assinaturas (recorrência)
CREATE TABLE IF NOT EXISTS planos_assinatura (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NULL,
    tipo ENUM('aluno', 'sistema') NOT NULL,
    nome VARCHAR(150) NOT NULL,
    intervalo_meses INT NOT NULL DEFAULT 1,
    repeats INT NULL,
    efi_plan_id VARCHAR(50) NULL,
    status VARCHAR(50) DEFAULT 'active',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_prof_tipo (professor_id, tipo)
);

CREATE TABLE IF NOT EXISTS assinaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NULL,
    aluno_id INT NULL,
    tipo ENUM('aluno', 'sistema') NOT NULL,
    plano_id INT NULL,
    efi_subscription_id VARCHAR(50) NULL,
    efi_charge_id VARCHAR(50) NULL,
    efi_payment_url TEXT NULL,
    valor DECIMAL(10,2) NULL,
    status VARCHAR(50) DEFAULT 'new',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plano_id) REFERENCES planos_assinatura(id),
    INDEX idx_prof (professor_id),
    INDEX idx_aluno (aluno_id),
    INDEX idx_efi_sub (efi_subscription_id)
);

-- 3c. Campos de cancelamento agendado (SaaS) e controle de período pago
ALTER TABLE assinaturas
ADD COLUMN IF NOT EXISTS paid_until DATE NULL,
ADD COLUMN IF NOT EXISTS cancel_requested_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS cancel_at DATE NULL,
ADD COLUMN IF NOT EXISTS canceled_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS cancel_reason VARCHAR(255) NULL;

-- 4. Adicionar campo de presença na tabela agenda (se não existir)
ALTER TABLE agenda 
ADD COLUMN IF NOT EXISTS presenca ENUM('presente', 'ausente', 'justificada') NULL,
ADD COLUMN IF NOT EXISTS data_presenca TIMESTAMP NULL;

-- 4b. Tabela de configuração de modelo de contrato
CREATE TABLE IF NOT EXISTS contratos_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    conteudo LONGTEXT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_contratos_config_professor (professor_id),
    FOREIGN KEY (professor_id) REFERENCES professores(id)
);

-- 4c. Contratos do aluno (vigência + forma de pagamento + integração Efí)
CREATE TABLE IF NOT EXISTS contratos_aluno (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    aluno_id INT NOT NULL,
    status ENUM('draft', 'confirmed', 'paid', 'active', 'completed', 'canceled') NOT NULL DEFAULT 'draft',
    data_inicio DATE NOT NULL,
    duracao_meses INT NOT NULL,
    parcelas INT NOT NULL,
    desconto_avista_percent DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    valor_mensal DECIMAL(10,2) NOT NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    valor_avista DECIMAL(10,2) NOT NULL,
    valor_parcela DECIMAL(10,2) NOT NULL,
    forma_pagamento ENUM('pix_avista', 'boleto_avista', 'cartao_avista', 'cartao_recorrente') NOT NULL,
    efi_subscription_id VARCHAR(50) NULL,
    efi_charge_id VARCHAR(255) NULL,
    efi_payment_url TEXT NULL,
    efi_payment_status VARCHAR(50) NULL,
    txid_efi VARCHAR(255) NULL,
    link_pagamento TEXT NULL,
    paid_at TIMESTAMP NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES professores(id),
    FOREIGN KEY (aluno_id) REFERENCES alunos(id),
    INDEX idx_prof_aluno_status (professor_id, aluno_id, status),
    INDEX idx_txid (txid_efi),
    INDEX idx_efi_sub (efi_subscription_id),
    INDEX idx_efi_charge (efi_charge_id)
);

ALTER TABLE contratos_aluno
ADD COLUMN IF NOT EXISTS boleto_url VARCHAR(500) NULL,
ADD COLUMN IF NOT EXISTS boleto_barcode VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS boleto_pdf_url VARCHAR(500) NULL;

-- 5. Adicionar campos de configuração financeira na tabela professores
ALTER TABLE professores
ADD COLUMN IF NOT EXISTS dia_vencimento_padrao INT DEFAULT 10,
ADD COLUMN IF NOT EXISTS taxa_multa DECIMAL(5,2) DEFAULT 2.00,
ADD COLUMN IF NOT EXISTS taxa_juros DECIMAL(5,2) DEFAULT 1.00,
ADD COLUMN IF NOT EXISTS validade_pix_horas INT DEFAULT 24,
ADD COLUMN IF NOT EXISTS validade_boleto_dias INT DEFAULT 7,
ADD COLUMN IF NOT EXISTS ambiente_efi ENUM('sandbox', 'production') DEFAULT 'sandbox',
ADD COLUMN IF NOT EXISTS webhook_url VARCHAR(500) NULL,
ADD COLUMN IF NOT EXISTS webhook_secret VARCHAR(255) NULL;

ALTER TABLE alunos
ADD COLUMN IF NOT EXISTS possui_responsavel BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS responsavel_nome VARCHAR(100) NULL,
ADD COLUMN IF NOT EXISTS responsavel_cpf VARCHAR(14) NULL,
ADD COLUMN IF NOT EXISTS responsavel_email VARCHAR(100) NULL,
ADD COLUMN IF NOT EXISTS responsavel_telefone VARCHAR(20) NULL,
ADD COLUMN IF NOT EXISTS responsavel_whatsapp VARCHAR(20) NULL,
ADD COLUMN IF NOT EXISTS responsavel_parentesco VARCHAR(50) NULL;

-- 5a. Soft delete de alunos
ALTER TABLE alunos
ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL;

-- 5b. Adicionar slug em professores (usado nos links públicos e login)
ALTER TABLE professores
ADD COLUMN IF NOT EXISTS slug VARCHAR(160) NULL;

UPDATE professores
SET slug = CONCAT(
    LOWER(
        TRIM(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(nome, ' ', '-'),
                    '--', '-'),
                '--', '-'),
            '--', '-')
        )
    ),
    '-',
    SUBSTRING(MD5(CONCAT(id, '-', UNIX_TIMESTAMP())), 1, 6)
)
WHERE (slug IS NULL OR slug = '');

ALTER TABLE professores
ADD UNIQUE INDEX IF NOT EXISTS idx_professores_slug (slug);

-- 6. Adicionar campos necessários em mensalidades
ALTER TABLE mensalidades
ADD COLUMN IF NOT EXISTS valor_original DECIMAL(10,2) NULL,
ADD COLUMN IF NOT EXISTS valor_final DECIMAL(10,2) NULL,
ADD COLUMN IF NOT EXISTS valor_multa DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS valor_juros DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS dias_atraso INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS pix_expira_em TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS boleto_url VARCHAR(500) NULL,
ADD COLUMN IF NOT EXISTS boleto_barcode VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS boleto_expira_em DATE NULL,
ADD COLUMN IF NOT EXISTS efi_charge_id VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS efi_payment_url TEXT NULL,
ADD COLUMN IF NOT EXISTS efi_payment_status VARCHAR(50) NULL;

-- Inserir templates padrão para cada professor existente
INSERT INTO templates_mensagem (professor_id, nome, tipo, template, ativo)
SELECT 
    id,
    'Cobrança de Mensalidade',
    'cobranca',
    'Olá [NOME]! Sua mensalidade de [VALOR] está com vencimento em [DATA_VENCIMENTO]. Para pagar via PIX, copie o código: [PIX]',
    TRUE
FROM professores
WHERE NOT EXISTS (
    SELECT 1 FROM templates_mensagem WHERE tipo = 'cobranca' AND professor_id = professores.id
);

INSERT INTO templates_mensagem (professor_id, nome, tipo, template, ativo)
SELECT 
    id,
    'Lembrete de Vencimento',
    'lembrete',
    'Olá [NOME]! Lembrando que sua mensalidade de [VALOR] vence em [DATA_VENCIMENTO]. PIX: [PIX]',
    TRUE
FROM professores
WHERE NOT EXISTS (
    SELECT 1 FROM templates_mensagem WHERE tipo = 'lembrete' AND professor_id = professores.id
);

INSERT INTO templates_mensagem (professor_id, nome, tipo, template, ativo)
SELECT 
    id,
    'Agradecimento pelo Pagamento',
    'agradecimento',
    'Olá [NOME]! Recebemos seu pagamento de [VALOR]. Obrigado!',
    TRUE
FROM professores
WHERE NOT EXISTS (
    SELECT 1 FROM templates_mensagem WHERE tipo = 'agradecimento' AND professor_id = professores.id
);

