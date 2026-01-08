-- Atualização para Sistema de Turmas e Recorrência

-- 1. Tabela de Turmas
CREATE TABLE IF NOT EXISTS turmas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cor VARCHAR(20) DEFAULT '#4e73df',
    descricao TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES professores(id)
);

-- 2. Tabela de Matrículas (Alunos na Turma)
CREATE TABLE IF NOT EXISTS turma_alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turma_id INT NOT NULL,
    aluno_id INT NOT NULL,
    data_entrada DATE,
    ativo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_turma_aluno (turma_id, aluno_id)
);

-- 3. Tabela de Presença Individual (Agenda <-> Alunos)
CREATE TABLE IF NOT EXISTS agenda_alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agenda_id INT NOT NULL,
    aluno_id INT NOT NULL,
    presenca ENUM('pendente', 'presente', 'ausente', 'justificada') DEFAULT 'pendente',
    observacao TEXT,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (agenda_id) REFERENCES agenda(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_agenda_aluno (agenda_id, aluno_id)
);

-- 4. Modificar Tabela Agenda (Colunas novas)
-- Nota: Executar estes comandos manualmente se o script PHP não tratar "IF EXISTS" para colunas
-- ALTER TABLE agenda ADD COLUMN turma_id INT NULL AFTER aluno_id;
-- ALTER TABLE agenda ADD COLUMN recorrencia_id VARCHAR(50) NULL;
-- ALTER TABLE agenda ADD COLUMN tipo_aula_id INT NULL;
-- ALTER TABLE agenda ADD CONSTRAINT fk_agenda_turma FOREIGN KEY (turma_id) REFERENCES turmas(id);
