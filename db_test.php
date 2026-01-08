<?php
// Script de teste de conexão com o banco de dados
// Carrega configurações
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnóstico de Conexão com Banco de Dados</h1>";

$envFile = __DIR__ . '/.env.php';
if (file_exists($envFile)) {
    echo "<p>Carregando arquivo .env.php...</p>";
    require_once $envFile;
} else {
    echo "<p style='color:red'>Arquivo .env.php não encontrado!</p>";
}

// Verifica se .env.local existe (pode causar conflito)
if (file_exists(__DIR__ . '/.env.local')) {
    echo "<p style='color:orange'>ALERTA: Arquivo .env.local encontrado. Isso pode estar sobrescrevendo as configurações de produção para 'localhost'.</p>";
}

// Exibe configurações (mas mascara a senha)
echo "<h2>Configurações Carregadas:</h2>";
echo "<ul>";
echo "<li>Host: " . (defined('DB_HOST') ? DB_HOST : 'Não definido') . "</li>";
echo "<li>Database: " . (defined('DB_NAME') ? DB_NAME : 'Não definido') . "</li>";
echo "<li>User: " . (defined('DB_USER') ? DB_USER : 'Não definido') . "</li>";
echo "<li>Password: " . (defined('DB_PASS') ? str_repeat('*', strlen(DB_PASS)) : 'Não definido') . "</li>";
echo "</ul>";

// Tenta conectar
echo "<h2>Tentando Conexão...</h2>";
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
    echo "<p style='color:green; font-weight:bold'>SUCESSO: Conexão estabelecida com sucesso!</p>";
} catch (PDOException $e) {
    echo "<p style='color:red; font-weight:bold'>ERRO: Falha na conexão.</p>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    
    echo "<h3>Sugestões:</h3>";
    echo "<ul>";
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "<li>Senha ou usuário incorretos. Verifique as credenciais.</li>";
        echo "<li><strong>Importante:</strong> Verifique se o usuário do banco tem permissão de acesso a partir deste servidor.</li>";
        echo "<li>IP do Servidor Web (que está tentando conectar): " . $_SERVER['SERVER_ADDR'] . "</li>";
    }
    if (strpos($e->getMessage(), '2002') !== false) {
        echo "<li>Erro de host/rede. Se o host for 'localhost', tente '127.0.0.1' ou verifique se o servidor MySQL está rodando.</li>";
        echo "<li>Se estiver em produção (Locaweb/KingHost), verifique se o host está correto (não use localhost).</li>";
    }
    echo "</ul>";
}
?>