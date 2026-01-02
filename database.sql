-- Banco de dados para o sistema Promestre

CREATE DATABASE IF NOT EXISTS promestre;
USE promestre;

-- Tabela de Professores (Usuários do sistema)
CREATE TABLE IF NOT EXISTS professores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    slug VARCHAR(160) NOT NULL UNIQUE,
    foto VARCHAR(255),
    chave_pix VARCHAR(100),
    client_id_efi VARCHAR(255),
    client_secret_efi VARCHAR(255),
    certificado_efi VARCHAR(255),
    dia_vencimento_padrao INT DEFAULT 10,
    taxa_multa DECIMAL(5,2) DEFAULT 2.00,
    taxa_juros DECIMAL(5,2) DEFAULT 1.00,
    validade_pix_horas INT DEFAULT 24,
    validade_boleto_dias INT DEFAULT 7,
    ambiente_efi ENUM('sandbox', 'production') DEFAULT 'sandbox',
    webhook_url VARCHAR(500) NULL,
    webhook_secret VARCHAR(255) NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Tipos de Aula
CREATE TABLE IF NOT EXISTS tipos_aula (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    nome VARCHAR(50) NOT NULL,
    preco_padrao DECIMAL(10, 2) NOT NULL,
    descricao TEXT,
    cor VARCHAR(20) DEFAULT '#007bff',
    ativo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (professor_id) REFERENCES professores(id)
);

-- Tabela de Alunos
CREATE TABLE IF NOT EXISTS alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    telefone VARCHAR(20) NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    cpf VARCHAR(14),
    data_nascimento DATE,
    possui_responsavel BOOLEAN DEFAULT FALSE,
    responsavel_nome VARCHAR(100) NULL,
    responsavel_cpf VARCHAR(14) NULL,
    responsavel_email VARCHAR(100) NULL,
    responsavel_telefone VARCHAR(20) NULL,
    responsavel_whatsapp VARCHAR(20) NULL,
    responsavel_parentesco VARCHAR(50) NULL,
    endereco TEXT,
    tipo_aula_id INT,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    observacoes TEXT,
    deleted_at TIMESTAMP NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES professores(id),
    FOREIGN KEY (tipo_aula_id) REFERENCES tipos_aula(id)
);

-- Tabela de Mensalidades
CREATE TABLE IF NOT EXISTS mensalidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    aluno_id INT NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    valor_original DECIMAL(10,2) NULL,
    valor_final DECIMAL(10,2) NULL,
    valor_multa DECIMAL(10,2) DEFAULT 0.00,
    valor_juros DECIMAL(10,2) DEFAULT 0.00,
    dias_atraso INT DEFAULT 0,
    data_vencimento DATE NOT NULL,
    status ENUM('pendente', 'pago', 'atrasado', 'cancelado') DEFAULT 'pendente',
    data_pagamento DATE,
    forma_pagamento VARCHAR(50), -- pix, dinheiro, boleto
    txid_efi VARCHAR(255), -- Para integração com EfiBank
    pix_expira_em TIMESTAMP NULL,
    link_pagamento TEXT,
    boleto_url VARCHAR(500) NULL,
    boleto_barcode VARCHAR(255) NULL,
    boleto_expira_em DATE NULL,
    efi_charge_id VARCHAR(255) NULL,
    efi_payment_url TEXT NULL,
    efi_payment_status VARCHAR(50) NULL,
    observacoes TEXT,
    FOREIGN KEY (professor_id) REFERENCES professores(id),
    FOREIGN KEY (aluno_id) REFERENCES alunos(id)
);

-- Tabela de Agenda (Simplificada para MVP)
CREATE TABLE IF NOT EXISTS agenda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    aluno_id INT,
    titulo VARCHAR(100) NOT NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NOT NULL,
    status ENUM('agendado', 'realizado', 'cancelado') DEFAULT 'agendado',
    presenca ENUM('presente', 'ausente', 'justificada') NULL,
    data_presenca TIMESTAMP NULL,
    observacoes TEXT,
    FOREIGN KEY (professor_id) REFERENCES professores(id),
    FOREIGN KEY (aluno_id) REFERENCES alunos(id)
);

-- Tabela de Recuperação de Senha
CREATE TABLE IF NOT EXISTS recuperacao_senha (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expiracao DATETIME NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de configuração de modelo de contrato
CREATE TABLE IF NOT EXISTS contratos_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    conteudo LONGTEXT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_contratos_config_professor (professor_id),
    FOREIGN KEY (professor_id) REFERENCES professores(id)
);

-- Contratos do aluno (vigência + forma de pagamento + integração Efí)
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
    boleto_url VARCHAR(500) NULL,
    boleto_barcode VARCHAR(255) NULL,
    boleto_pdf_url VARCHAR(500) NULL,
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

-- Tabela para logs de webhook EfiBank
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

-- Tabela para templates de mensagem WhatsApp
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

-- Tabela para histórico de notificações enviadas
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

-- Tabelas de planos e assinaturas (recorrência)
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
    paid_until DATE NULL,
    cancel_requested_at TIMESTAMP NULL,
    cancel_at DATE NULL,
    canceled_at TIMESTAMP NULL,
    cancel_reason VARCHAR(255) NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plano_id) REFERENCES planos_assinatura(id),
    INDEX idx_prof (professor_id),
    INDEX idx_aluno (aluno_id),
    INDEX idx_efi_sub (efi_subscription_id)
);
