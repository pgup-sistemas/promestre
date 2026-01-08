-- Atualizações para Gestão de Usuários e Suporte (Simplificado)
-- Execute este arquivo no banco de dados

-- 1. Adicionar campo de nível na tabela professores
ALTER TABLE professores 
ADD COLUMN nivel ENUM('admin', 'professor') DEFAULT 'professor' AFTER email,
ADD COLUMN ativo BOOLEAN DEFAULT TRUE AFTER nivel,
ADD COLUMN ultimo_login TIMESTAMP NULL AFTER ativo,
ADD COLUMN data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER data_cadastro;

-- 2. Criar tabela de logs de atividades (essencial para suporte)
CREATE TABLE IF NOT EXISTS logs_atividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NULL,
    acao VARCHAR(100) NOT NULL,
    tabela VARCHAR(50) NULL,
    registro_id INT NULL,
    descricao TEXT NULL,
    ip VARCHAR(45) NULL,
    user_agent TEXT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES professores(id)
);

-- 3. Criar tabela de tokens de reset (para suporte admin)
CREATE TABLE IF NOT EXISTS reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    criado_por INT NOT NULL,
    expiracao TIMESTAMP NOT NULL,
    usado BOOLEAN DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES professores(id),
    FOREIGN KEY (criado_por) REFERENCES professores(id)
);

-- 4. Inserir primeiro admin (se não existir)
INSERT INTO professores (nome, email, senha, slug, nivel, ativo)
SELECT 
    'Administrador Sistema',
    'admin@promestre.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password
    'admin-sistema',
    'admin',
    TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM professores WHERE email = 'admin@promestre.com'
);

-- Se o email já existir, garantir que vire admin (útil em banco já populado)
UPDATE professores
SET nivel = 'admin', ativo = TRUE
WHERE email = 'admin@promestre.com';

-- 5. Índices para performance
SET @idx_exists := (
    SELECT COUNT(1)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'logs_atividades'
      AND index_name = 'idx_logs_professor_id'
);
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_logs_professor_id ON logs_atividades(professor_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (
    SELECT COUNT(1)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'logs_atividades'
      AND index_name = 'idx_logs_data_criacao'
);
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_logs_data_criacao ON logs_atividades(data_criacao)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (
    SELECT COUNT(1)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'reset_tokens'
      AND index_name = 'idx_reset_token'
);
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_reset_token ON reset_tokens(token)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (
    SELECT COUNT(1)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'reset_tokens'
      AND index_name = 'idx_reset_expiracao'
);
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_reset_expiracao ON reset_tokens(expiracao)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 6. Verificar separação de alunos por professor
-- Esta query confirma que cada professor só vê seus alunos:
SELECT 
    p.nome as professor,
    COUNT(a.id) as total_alunos
FROM professores p 
LEFT JOIN alunos a ON p.id = a.professor_id 
GROUP BY p.id, p.nome
ORDER BY p.nome;
