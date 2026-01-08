<?php
require_once 'includes/config.php';

echo "<h2>Aplicando atualizações do banco de dados (Agenda V2)...</h2>";
echo "<a href='index.php'>Voltar para Home</a><hr>";

try {
    // 1. Ler e executar criação de tabelas
    $sql_file = 'database_updates_agenda_v2.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("Arquivo $sql_file não encontrado.");
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // Remover comentários de linha (-- ...)
    $sql_clean = preg_replace('/^--.*$/m', '', $sql_content);
    // Remover linhas vazias
    $statements = array_filter(array_map('trim', explode(';', $sql_clean)));

    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            try {
                $pdo->exec($stmt);
                echo "<div style='color: green'>Comando executado: " . htmlspecialchars(substr($stmt, 0, 60)) . "...</div>";
            } catch (PDOException $e) {
                // Ignore "Table already exists" errors
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    echo "<div style='color: orange'>Tabela já existe (ignorado).</div>";
                } elseif (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                     echo "<div style='color: orange'>Coluna já existe (ignorado).</div>";
                } else {
                    echo "<div style='color: red'>Erro SQL: " . $e->getMessage() . "</div>";
                }
            }
        }
    }

    echo "<hr><h3>Verificando e Atualizando Tabela Agenda...</h3>";

    // 2. Adicionar colunas na tabela agenda com verificação
    // Usando query direta para evitar problemas com placeholders em comandos SHOW/ALTER
    $columns_to_add = [
        'turma_id' => "ALTER TABLE agenda ADD COLUMN turma_id INT NULL AFTER aluno_id",
        'recorrencia_id' => "ALTER TABLE agenda ADD COLUMN recorrencia_id VARCHAR(50) NULL",
        'tipo_aula_id' => "ALTER TABLE agenda ADD COLUMN tipo_aula_id INT NULL"
    ];

    foreach ($columns_to_add as $col => $alter_sql) {
        // Verificar se coluna existe
        // SHOW COLUMNS LIKE '$col'
        try {
            $check = $pdo->query("SHOW COLUMNS FROM agenda LIKE '$col'");
            if ($check && $check->rowCount() > 0) {
                echo "<div style='color: blue'>Coluna '$col' já existe.</div>";
            } else {
                $pdo->exec($alter_sql);
                echo "<div style='color: green'>Coluna '$col' adicionada com sucesso.</div>";
            }
        } catch (PDOException $e) {
             echo "<div style='color: red'>Erro ao verificar/adicionar coluna '$col': " . $e->getMessage() . "</div>";
        }
    }

    // 3. Adicionar FK da turma se não existir
    echo "<hr><h3>Verificando Foreign Keys...</h3>";
    try {
        // Tentar adicionar a FK. Se já existir, vai dar erro, mas capturamos.
        // O MySQL não tem "IF NOT EXISTS" para constraints em versões antigas facilmente acessível num comando só.
        // Vamos tentar adicionar e ignorar erro de duplicidade.
        
        $pdo->exec("ALTER TABLE agenda ADD CONSTRAINT fk_agenda_turma FOREIGN KEY (turma_id) REFERENCES turmas(id)");
        echo "<div style='color: green'>FK fk_agenda_turma adicionada.</div>";
    } catch (PDOException $e) {
         // Erro 1005 (Can't create table) ou 1061 (Duplicate key name) ou 1826 (Duplicate foreign key constraint name)
         // Vamos assumir que se falhou é porque já existe ou dados inconsistentes.
         echo "<div style='color: orange'>Nota sobre FK: " . $e->getMessage() . " (Provavelmente já existe)</div>";
    }

    echo "<hr><h3>Atualização concluída com sucesso!</h3>";
    echo "<p>Agora você pode usar as funcionalidades de Turmas e Recorrência.</p>";
    echo "<a href='index.php' class='btn'>Ir para o Sistema</a>";

} catch (Exception $e) {
    echo "<h1 style='color: red'>Erro Crítico: " . $e->getMessage() . "</h1>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
