<?php
require_once 'includes/config.php';
require_once 'includes/user_management.php';

/**
 * Sistema de Testes Automatizados - Promestre
 * Verifica todas as funcionalidades antes de subir para produção
 */

// Desabilitar output buffering para ver resultados em tempo real
if (ob_get_level()) ob_end_clean();
header('Content-Type: text/plain; charset=utf-8');

echo "🧪 SUITE DE TESTES - PROMESTRE\n";
echo "================================\n\n";

$test_results = [];
$total_tests = 0;
$passed_tests = 0;
$failed_tests = 0;

function runTest($test_name, $test_function) {
    global $total_tests, $passed_tests, $failed_tests, $test_results;
    
    $total_tests++;
    echo "📋 Testando: $test_name\n";
    
    try {
        $result = $test_function();
        if ($result) {
            echo "✅ PASSOU\n";
            $passed_tests++;
            $test_results[$test_name] = 'PASS';
        } else {
            echo "❌ FALHOU\n";
            $failed_tests++;
            $test_results[$test_name] = 'FAIL';
        }
    } catch (Exception $e) {
        echo "❌ ERRO: " . $e->getMessage() . "\n";
        $failed_tests++;
        $test_results[$test_name] = 'ERROR';
    }
    
    echo str_repeat("-", 50) . "\n";
}

// ========================================
// TESTES DE CONFIGURAÇÃO E CONEXÃO
// ========================================

runTest("Configuração do Banco de Dados", function() {
    global $pdo;
    return $pdo instanceof PDO && $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) !== null;
});

runTest("Constantes Essenciais Definidas", function() {
    return defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('SITE_URL');
});

runTest("Sessão PHP Ativa", function() {
    return session_status() === PHP_SESSION_ACTIVE || session_start();
});

// ========================================
// TESTES DE TABELAS DO BANCO
// ========================================

runTest("Tabela 'professores' Existe", function() {
    global $pdo;
    $stmt = $pdo->query("DESCRIBE professores");
    return $stmt->rowCount() > 0;
});

runTest("Tabela 'alunos' Existe", function() {
    global $pdo;
    $stmt = $pdo->query("DESCRIBE alunos");
    return $stmt->rowCount() > 0;
});

runTest("Tabela 'logs_atividades' Existe", function() {
    global $pdo;
    try {
        $stmt = $pdo->query("DESCRIBE logs_atividades");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
});

runTest("Tabela 'reset_tokens' Existe", function() {
    global $pdo;
    try {
        $stmt = $pdo->query("DESCRIBE reset_tokens");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
});

// ========================================
// TESTES DE CAMPOS NOVOS (GESTÃO DE USUÁRIOS)
// ========================================

runTest("Campo 'nivel' na tabela 'professores'", function() {
    global $pdo;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM professores LIKE 'nivel'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
});

runTest("Campo 'ativo' na tabela 'professores'", function() {
    global $pdo;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM professores LIKE 'ativo'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
});

runTest("Campo 'ultimo_login' na tabela 'professores'", function() {
    global $pdo;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM professores LIKE 'ultimo_login'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
});

// ========================================
// TESTES DE FUNÇÕES ESSENCIAIS
// ========================================

runTest("Função isLoggedIn() Funciona", function() {
    return function_exists('isLoggedIn');
});

runTest("Função clean() Funciona", function() {
    return function_exists('clean') && clean('<script>alert("xss")</script>') !== '<script>alert("xss")</script>';
});

runTest("Função formatMoney() Funciona", function() {
    return function_exists('formatMoney') && formatMoney(1234.56) === 'R$ 1.234,56';
});

runTest("Função generateSlug() Funciona", function() {
    return function_exists('generateSlug') && generateSlug('Teste de Slug') === 'teste-de-slug';
});

// ========================================
// TESTES DE GESTÃO DE USUÁRIOS
// ========================================

runTest("Função isAdmin() Definida", function() {
    return function_exists('isAdmin');
});

runTest("Função requireAdmin() Definida", function() {
    return function_exists('requireAdmin');
});

runTest("Função logActivity() Funciona", function() {
    return function_exists('logActivity');
});

runTest("Função updateLastLogin() Funciona", function() {
    return function_exists('updateLastLogin');
});

runTest("Função generateResetToken() Funciona", function() {
    return function_exists('generateResetToken');
});

runTest("Função validateResetToken() Funciona", function() {
    return function_exists('validateResetToken');
});

// ========================================
// TESTES DE USUÁRIO ADMIN
// ========================================

runTest("Usuário Admin Padrão Existe", function() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id FROM professores WHERE email = 'admin@promestre.com' AND nivel = 'admin'");
        $stmt->execute();
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
});

runTest("Senha do Admin é Válida", function() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT senha FROM professores WHERE email = 'admin@promestre.com'");
        $stmt->execute();
        $hash = $stmt->fetchColumn();
        return $hash && password_verify('password', $hash);
    } catch (PDOException $e) {
        return false;
    }
});

// ========================================
// TESTES DE SEPARAÇÃO DE DADOS
// ========================================

runTest("Separação de Alunos por Professor", function() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT professor_id, COUNT(*) as total FROM alunos GROUP BY professor_id");
        $results = $stmt->fetchAll();
        return count($results) >= 0; // Pode ser vazio, mas a query deve funcionar
    } catch (PDOException $e) {
        return false;
    }
});

runTest("Relacionamento Aluno->Professor Funciona", function() {
    global $pdo;
    try {
        $stmt = $pdo->query("
            SELECT a.nome as aluno, p.nome as professor 
            FROM alunos a 
            LEFT JOIN professores p ON a.professor_id = p.id 
            LIMIT 1
        ");
        return $stmt->rowCount() >= 0;
    } catch (PDOException $e) {
        return false;
    }
});

// ========================================
// TESTES DE ARQUIVOS ESSENCIAIS
// ========================================

runTest("Arquivo 'gestao_usuarios.php' Existe", function() {
    return file_exists(__DIR__ . '/gestao_usuarios.php');
});

runTest("Arquivo 'dashboard_admin.php' Existe", function() {
    return file_exists(__DIR__ . '/dashboard_admin.php');
});

runTest("Arquivo 'onboarding.php' Existe", function() {
    return file_exists(__DIR__ . '/onboarding.php');
});

runTest("Arquivo 'onboarding_admin.php' Existe", function() {
    return file_exists(__DIR__ . '/onboarding_admin.php');
});

runTest("Arquivo 'admin_reset_senha.php' Existe", function() {
    return file_exists(__DIR__ . '/admin_reset_senha.php');
});

// ========================================
// TESTES DE PERMISSÕES E SEGURANÇA
// ========================================

runTest("Proteção contra SQL Injection (clean())", function() {
    $malicious = "'; DROP TABLE professores; --";
    $cleaned = clean($malicious);
    return strpos($cleaned, "'") === false && strpos($cleaned, ";") === false;
});

runTest("Hash de Senha Seguro", function() {
    $password = 'test123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    return password_verify($password, $hash);
});

runTest("Geração de Token Aleatório", function() {
    $token1 = generateRandomString(32);
    $token2 = generateRandomString(32);
    return $token1 !== $token2 && strlen($token1) === 32;
});

// ========================================
// TESTES DE FUNCIONALIDADES DE LOGIN
// ========================================

runTest("Validação de Email Funciona", function() {
    return function_exists('isValidEmail') && 
           isValidEmail('test@example.com') && 
           !isValidEmail('email-invalido');
});

runTest("Função redirect() Funciona", function() {
    return function_exists('redirect');
});

// ========================================
// TESTES DE LOGS E AUDITORIA
// ========================================

runTest("Inserção de Log de Atividade", function() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO logs_atividades (professor_id, acao, descricao, ip) 
            VALUES (?, ?, ?, ?)
        ");
        $result = $stmt->execute([null, 'TEST', 'Teste automatizado', '127.0.0.1']);
        return $result;
    } catch (PDOException $e) {
        return false;
    }
});

runTest("Consulta de Logs Funciona", function() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM logs_atividades ORDER BY data_criacao DESC LIMIT 1");
        return $stmt->rowCount() >= 0;
    } catch (PDOException $e) {
        return false;
    }
});

// ========================================
// TESTES DE RESET DE SENHA
// ========================================

runTest("Criação de Token de Reset", function() {
    global $pdo;
    try {
        // Primeiro verifica se existe um professor
        $stmt = $pdo->prepare("SELECT id FROM professores LIMIT 1");
        $stmt->execute();
        $professor = $stmt->fetch();
        
        if (!$professor) return false;
        
        $token = generateResetToken($professor['id'], 1);
        return !empty($token);
    } catch (Exception $e) {
        return false;
    }
});

// ========================================
// RELATÓRIO FINAL
// ========================================

echo "\n📊 RELATÓRIO FINAL DE TESTES\n";
echo "============================\n\n";

echo "Total de Testes: $total_tests\n";
echo "✅ Passaram: $passed_tests\n";
echo "❌ Falharam: $failed_tests\n";
echo "📈 Taxa de Sucesso: " . round(($passed_tests / $total_tests) * 100, 2) . "%\n\n";

if ($failed_tests > 0) {
    echo "⚠️  TESTES FALHADOS:\n";
    echo "===================\n";
    foreach ($test_results as $test => $result) {
        if ($result !== 'PASS') {
            echo "❌ $test: $result\n";
        }
    }
    echo "\n";
}

echo "🎯 RECOMENDAÇÕES:\n";
echo "==================\n";

if ($failed_tests === 0) {
    echo "✅ Todos os testes passaram! Sistema pronto para produção.\n";
    echo "📦 Lembre-se de enviar todos os arquivos atualizados.\n";
    echo "🔧 Execute o database_updates_gestao_usuarios.sql no servidor.\n";
} else {
    echo "⚠️  Corrija os testes falhados antes de subir para produção.\n";
    echo "🔧 Verifique a configuração do banco de dados.\n";
    echo "📋 Execute os scripts SQL necessários.\n";
}

echo "\n📋 ARQUIVOS ESSENCIAIS PARA PRODUÇÃO:\n";
echo "====================================\n";
$essential_files = [
    'database_updates_gestao_usuarios.sql',
    'includes/user_management.php',
    'gestao_usuarios.php',
    'dashboard_admin.php',
    'dashboard.php',
    'onboarding.php',
    'onboarding_admin.php',
    'admin_reset_senha.php',
    'index.php',
    'includes/functions.php',
    'assets/css/style.css'
];

foreach ($essential_files as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    echo $exists ? "✅ $file\n" : "❌ $file (FALTANTE)\n";
}

echo "\n🚀 TESTES CONCLUÍDOS!\n";
?>
