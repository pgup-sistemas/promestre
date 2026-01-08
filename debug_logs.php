<?php
/**
 * Script para Debug de Erros - Promestre
 * Execute este arquivo na RAIZ do projeto
 */

// Habilitar todos os erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Debug de Erros - Promestre</h1>";

echo "<h2>1. Informações do Servidor</h2>";
echo "<strong>PHP Version:</strong> " . PHP_VERSION . "<br>";
echo "<strong>Servidor:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "<strong>Request URI:</strong> " . $_SERVER['REQUEST_URI'] . "<br>";
echo "<strong>Current Dir:</strong> " . __DIR__ . "<br>";

echo "<h2>2. Verificação de Arquivos</h2>";
$files = [
    '.env.php' => __DIR__ . '/.env.php',
    'includes/config.php' => __DIR__ . '/includes/config.php',
    'includes/functions.php' => __DIR__ . '/includes/functions.php',
    'alunos.php' => __DIR__ . '/alunos.php'
];

foreach ($files as $name => $path) {
    if (file_exists($path)) {
        echo "✅ $name: EXISTE (" . filesize($path) . " bytes)<br>";
    } else {
        echo "❌ $name: NÃO ENCONTRADO em: $path<br>";
    }
}

echo "<h2>3. Teste de Conexão com Banco</h2>";
try {
    require_once __DIR__ . '/includes/config.php';
    echo "✅ Config carregado com sucesso<br>";
    
    if (!isset($pdo)) {
        echo "❌ PDO não definido<br>";
    } else {
        echo "✅ PDO definido<br>";
        
        // Testar query simples
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        echo "✅ Conexão ativa (test: " . $result['test'] . ")<br>";
        
        // Testar tabela alunos
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM alunos");
        $result = $stmt->fetch();
        echo "✅ Tabela alunos: " . $result['total'] . " registros<br>";
    }
} catch (Exception $e) {
    echo "<h3>❌ ERRO NA CONEXÃO:</h3>";
    echo "<strong>Mensagem:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Código:</strong> " . $e->getCode() . "<br>";
    echo "<strong>Arquivo:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Linha:</strong> " . $e->getLine() . "<br>";
}

echo "<h2>4. Teste da Página alunos.php</h2>";
try {
    // Simular sessão
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Test User';
    $_SESSION['user_email'] = 'test@promestre.com';
    $_SESSION['user_slug'] = 'test-user';
    
    echo "✅ Sessão simulada<br>";
    
    // Carregar página alunos.php
    ob_start();
    include __DIR__ . '/alunos.php';
    $output = ob_get_clean();
    
    echo "✅ alunos.php carregado sem erros<br>";
    echo "<strong>Tamanho do output:</strong> " . strlen($output) . " bytes<br>";
    
    if (strlen($output) > 0) {
        echo "<strong>Primeiros 200 chars do output:</strong><br>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
        echo htmlspecialchars(substr($output, 0, 200));
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ ERRO EM alunos.php:</h3>";
    echo "<strong>Mensagem:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Código:</strong> " . $e->getCode() . "<br>";
    echo "<strong>Arquivo:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Linha:</strong> " . $e->getLine() . "<br>";
    echo "<strong>Trace:</strong> <pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "<h3>❌ ERRO FATAL EM alunos.php:</h3>";
    echo "<strong>Mensagem:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Código:</strong> " . $e->getCode() . "<br>";
    echo "<strong>Arquivo:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Linha:</strong> " . $e->getLine() . "<br>";
    echo "<strong>Trace:</strong> <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>5. Constantes Definidas</h2>";
$constants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'SITE_URL', 'EFI_ENV'];
foreach ($constants as $const) {
    if (defined($const)) {
        $value = $const === 'DB_PASS' ? '***HIDDEN***' : constant($const);
        echo "$const: " . htmlspecialchars($value) . "<br>";
    } else {
        echo "$const: ❌ NÃO DEFINIDA<br>";
    }
}

echo "<hr>";
echo "<p><small>Execute este arquivo na raiz: https://promestre.pageup.net.br/debug_logs.php</small></p>";
?>
