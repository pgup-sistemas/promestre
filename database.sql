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
    foto VARCHAR(255),
    chave_pix VARCHAR(100),
    client_id_efi VARCHAR(255),
    client_secret_efi VARCHAR(255),
    certificado_efi VARCHAR(255),
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
    endereco TEXT,
    tipo_aula_id INT,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    observacoes TEXT,
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
    data_vencimento DATE NOT NULL,
    status ENUM('pendente', 'pago', 'atrasado', 'cancelado') DEFAULT 'pendente',
    data_pagamento DATE,
    forma_pagamento VARCHAR(50), -- pix, dinheiro, boleto
    txid_efi VARCHAR(255), -- Para integração com EfiBank
    link_pagamento TEXT,
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
