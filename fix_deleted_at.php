<?php
/**
 * Script para adicionar coluna deleted_at na tabela alunos
 */

require_once 'includes/config.php';

echo "<h1>🔧 Adicionando coluna deleted_at</h1>";

try {
    // Adicionar coluna deleted_at na tabela alunos
    $sql = "ALTER TABLE alunos ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL";
    $pdo->exec($sql);
    echo "✅ Coluna deleted_at adicionada com sucesso na tabela alunos<br>";
    
    // Verificar se foi adicionada
    $stmt = $pdo->query("DESCRIBE alunos");
    $columns = $stmt->fetchAll();
    
    echo "<h3>Estrutura atual da tabela alunos:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>✅ Teste da query alunos.php:</h3>";
    
    // Testar a query do alunos.php
    $professor_id = 1;
    $sql = "SELECT a.*, t.nome as tipo_aula_nome, t.cor as tipo_aula_cor 
            FROM alunos a 
            LEFT JOIN tipos_aula t ON a.tipo_aula_id = t.id 
            WHERE a.professor_id = ? AND a.deleted_at IS NULL";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$professor_id]);
    $alunos = $stmt->fetchAll();
    
    echo "✅ Query executada com sucesso!<br>";
    echo "✅ " . count($alunos) . " alunos encontrados<br>";
    
    echo "<h2>🎉 PROBLEMA RESOLVIDO!</h2>";
    echo "<p><a href='alunos.php'>Testar página alunos.php</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Erro:</h3>";
    echo "<strong>Mensagem:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Código:</strong> " . $e->getCode() . "<br>";
    
    // Se a coluna já existe
    if (strpos($e->getMessage(), 'duplicate column name') !== false) {
        echo "<h3>✅ A coluna deleted_at já existe!</h3>";
        echo "<p>O problema pode ser outro. Teste a página <a href='alunos.php'>alunos.php</a></p>";
    }
}
?>
