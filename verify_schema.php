<?php
require_once 'includes/config.php';

function tableExists($pdo, $table) {
    try {
        $result = $pdo->query("SELECT 1 FROM $table LIMIT 1");
    } catch (Exception $e) {
        return false;
    }
    return $result !== false;
}

function columnExists($pdo, $table, $column) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    return $stmt->fetchColumn() > 0;
}

echo "<h3>Verificando Schema...</h3>";

// Check Tables
$tables = ['turmas', 'turma_alunos', 'agenda_alunos'];
foreach ($tables as $t) {
    if (tableExists($pdo, $t)) {
        echo "Tabela '$t' OK.<br>";
    } else {
        echo "Tabela '$t' FALTOU. Execute o SQL de criação novamente.<br>";
    }
}

// Check Columns in Agenda
$cols = ['turma_id', 'recorrencia_id', 'tipo_aula_id'];
foreach ($cols as $c) {
    if (columnExists($pdo, 'agenda', $c)) {
        echo "Coluna 'agenda.$c' OK.<br>";
    } else {
        echo "Coluna 'agenda.$c' FALTOU. Adicionando...<br>";
        try {
            if ($c == 'turma_id') $sql = "ALTER TABLE agenda ADD COLUMN turma_id INT NULL AFTER aluno_id";
            if ($c == 'recorrencia_id') $sql = "ALTER TABLE agenda ADD COLUMN recorrencia_id VARCHAR(50) NULL";
            if ($c == 'tipo_aula_id') $sql = "ALTER TABLE agenda ADD COLUMN tipo_aula_id INT NULL";
            
            $pdo->exec($sql);
            echo "Adicionado com sucesso.<br>";
        } catch (Exception $e) {
            echo "Erro: " . $e->getMessage() . "<br>";
        }
    }
}

// Check FK
try {
    // Only try to add FK if turma_id exists
    if (columnExists($pdo, 'agenda', 'turma_id')) {
        $pdo->exec("ALTER TABLE agenda ADD CONSTRAINT fk_agenda_turma FOREIGN KEY (turma_id) REFERENCES turmas(id)");
        echo "FK fk_agenda_turma OK/Adicionada.<br>";
    }
} catch (Exception $e) {
    // Error 1215: Cannot add foreign key constraint (usually means it exists or types mismatch)
    // Error 1061: Duplicate key name
    echo "FK Check: " . $e->getMessage() . " (Provavelmente já existe)<br>";
}

echo "Verificação concluída.";
?>
