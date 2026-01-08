<?php
/**
 * Configuração do Include - Promestre
 * Carrega configurações do arquivo .env.php (compatibilidade)
 */

if (file_exists(__DIR__ . '/../.env.php')) {
    // Se existir .env.php (Produção), carregamos ele PRIMEIRO e ignoramos o .env.local se ele definir DB_HOST como localhost
    require_once __DIR__ . '/../.env.php';
}

if (file_exists(__DIR__ . '/../.env.local')) {
    // Só carrega .env.local se NÃO for produção ou se quisermos sobrescrever algo específico
    // Mas protegemos as variáveis críticas de banco se já foram definidas pelo .env.php
    
    $lines = file(__DIR__ . '/../.env.local', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($lines)) {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            if ($key === '') {
                continue;
            }
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            if (!defined($key)) {
                if ($key === 'SMTP_PORT') {
                    define($key, (int)$value);
                } else {
                    define($key, $value);
                }
            }
        }
    }
}

$hasLocalEnv = file_exists(__DIR__ . '/../.env.local');

// .env.php já foi carregado no início do arquivo para garantir precedência em produção
// if (file_exists(__DIR__ . '/../.env.php')) {
//    require_once __DIR__ . '/../.env.php';
// }

// Verificar se as configurações básicas estão definidas
if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
    die("Erro: Configurações do banco de dados não encontradas. Verifique o arquivo .env.php");
}

// ===== CONFIGURAÇÕES DO SISTEMA =====
if (!defined('SITE_NAME')) define('SITE_NAME', 'Promestre');
if (!defined('SITE_URL')) define('SITE_URL', 'https://promestre.pageup.net.br');

// ===== CONFIGURAÇÕES DE EMAIL =====
if (defined('SMTP_HOST') && SMTP_HOST !== '') {
    if (!defined('MAIL_SMTP_ENABLED')) define('MAIL_SMTP_ENABLED', true);
    if (!defined('MAIL_HOST')) define('MAIL_HOST', SMTP_HOST);
    if (!defined('MAIL_PORT')) define('MAIL_PORT', SMTP_PORT ?: 587);
    if (!defined('MAIL_USER')) define('MAIL_USER', SMTP_USER);
    if (!defined('MAIL_PASS')) define('MAIL_PASS', SMTP_PASS);
    if (!defined('MAIL_FROM')) define('MAIL_FROM', EMAIL_FROM ?: 'no-reply@' . $_SERVER['HTTP_HOST']);
    if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', EMAIL_FROM_NAME ?: SITE_NAME);
    if (!defined('MAIL_SECURE')) define('MAIL_SECURE', 'tls');
} else {
    if (!defined('MAIL_SMTP_ENABLED')) define('MAIL_SMTP_ENABLED', false);
    if (!defined('MAIL_HOST')) define('MAIL_HOST', '');
    if (!defined('MAIL_PORT')) define('MAIL_PORT', 587);
    if (!defined('MAIL_USER')) define('MAIL_USER', '');
    if (!defined('MAIL_PASS')) define('MAIL_PASS', '');
    if (!defined('MAIL_FROM')) define('MAIL_FROM', 'no-reply@' . $_SERVER['HTTP_HOST']);
    if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', SITE_NAME);
    if (!defined('MAIL_SECURE')) define('MAIL_SECURE', 'tls');
}

// ===== CONFIGURAÇÕES DE AMBIENTE =====
if (!defined('EFI_ENV')) define('EFI_ENV', 'development');

// Desabilitar erros em produção
if (defined('FORCE_DISPLAY_ERRORS') && FORCE_DISPLAY_ERRORS) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} elseif (EFI_ENV === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Iniciar Sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carregar funções essenciais
require_once __DIR__ . '/functions.php';

// Conexão com o Banco de Dados
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    if (EFI_ENV === 'production') {
        error_log("Erro de conexão: " . $e->getMessage());
        die("Erro na conexão com o banco de dados");
    } else {
        die("Erro na conexão com o banco de dados: " . $e->getMessage());
    }
}

// Funções Utilitárias

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('isSystemSubscriptionActive')) {
    function isSystemSubscriptionActive($professor_id = null) {
        global $pdo;

        if ($professor_id === null && isset($_SESSION['user_id'])) {
            $professor_id = $_SESSION['user_id'];
        }

        if (!$professor_id) {
            return false;
        }

        try {
            $stmt = $pdo->prepare("SELECT status, paid_until, cancel_at, canceled_at FROM assinaturas WHERE professor_id = ? AND tipo = 'sistema' ORDER BY id DESC LIMIT 1");
            $stmt->execute([$professor_id]);
            $row = $stmt->fetch();
            if (!$row) {
                return false;
            }

            if (!empty($row['canceled_at'])) {
                return false;
            }

            $hoje = new DateTime('today');

            if (!empty($row['cancel_at'])) {
                $cancelAt = DateTime::createFromFormat('Y-m-d', (string)$row['cancel_at']);
                if ($cancelAt && $hoje > $cancelAt) {
                    return false;
                }
            }

            if (!empty($row['paid_until'])) {
                $paidUntil = DateTime::createFromFormat('Y-m-d', (string)$row['paid_until']);
                if ($paidUntil && $hoje > $paidUntil) {
                    return false;
                }
            }

            $status = strtolower((string)$row['status']);
            return in_array($status, ['active', 'paid', 'settled'], true);
        } catch (Throwable $e) {
            error_log("Erro em isSystemSubscriptionActive: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('requireActiveSystemSubscription')) {
    function requireActiveSystemSubscription() {
        if (!isLoggedIn()) {
            redirect('index.php');
        }

        if (!isSystemSubscriptionActive($_SESSION['user_id'])) {
            redirect('acesso_restrito.php');
        }
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        // Se a URL já contém http/https, usa completa
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            header("Location: " . $url);
        } else {
            header("Location: " . SITE_URL . "/" . ltrim($url, '/'));
        }
        exit;
    }
}

if (!function_exists('clean')) {
    function clean($data) {
        if (is_array($data)) {
            return array_map('clean', $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Processar template de mensagem substituindo variáveis dinâmicas
 */
function processarTemplate($template, $dados = []) {
    $mensagem = $template;
    
    // Variáveis disponíveis
    $variaveis = [
        '[NOME]' => $dados['nome'] ?? '',
        '[VALOR]' => isset($dados['valor']) ? 'R$ ' . number_format($dados['valor'], 2, ',', '.') : '',
        '[DATA_VENCIMENTO]' => isset($dados['data_vencimento']) ? date('d/m/Y', strtotime($dados['data_vencimento'])) : '',
        '[PIX]' => $dados['pix'] ?? '',
        '[BOLETO]' => $dados['boleto'] ?? '',
        '[DATA_HOJE]' => date('d/m/Y'),
        '[HORA_HOJE]' => date('H:i')
    ];
    
    foreach ($variaveis as $var => $valor) {
        $mensagem = str_replace($var, $valor, $mensagem);
    }
    
    return $mensagem;
}

/**
 * Registrar notificação no histórico
 */
function registrarNotificacao($professor_id, $aluno_id, $mensalidade_id, $template_id, $tipo, $mensagem_template, $mensagem_enviada, $whatsapp) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO historico_notificacoes 
            (professor_id, aluno_id, mensalidade_id, template_id, tipo, mensagem_template, mensagem_enviada, whatsapp) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $professor_id,
            $aluno_id,
            $mensalidade_id,
            $template_id,
            $tipo,
            $mensagem_template,
            $mensagem_enviada,
            $whatsapp
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao registrar notificação: " . $e->getMessage());
        return false;
    }
}

/**
 * Gerar link WhatsApp com mensagem
 */
function gerarLinkWhatsApp($telefone, $mensagem) {
    // Formatar telefone (remover caracteres não numéricos)
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    
    // Adicionar código do país se não tiver
    if (strlen($telefone) == 10 || strlen($telefone) == 11) {
        $telefone = '55' . $telefone;
    }
    
    // URL encode da mensagem
    $mensagem_encoded = urlencode($mensagem);
    
    return "https://wa.me/{$telefone}?text={$mensagem_encoded}";
}

if (!function_exists('setFlash')) {
    function setFlash($message, $type = 'info') {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
}

function sendMail($to, $subject, $htmlBody, $textBody = null) {
    if (!MAIL_SMTP_ENABLED) {
        $log = "--- Email ---\nPara: " . $to . "\nAssunto: " . $subject . "\n" . ($textBody ?: strip_tags($htmlBody)) . "\n\n";
        file_put_contents(__DIR__ . '/../email_log.txt', $log, FILE_APPEND);
        return true;
    }
    
    try {
        // Carregar PHPMailer via Composer ou manual
        $phpmailerPath = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($phpmailerPath)) {
            require $phpmailerPath;
        } else {
            // Fallback para carregamento manual
            $mailerFiles = [
                __DIR__ . '/PHPMailer/src/Exception.php',
                __DIR__ . '/PHPMailer/src/PHPMailer.php',
                __DIR__ . '/PHPMailer/src/SMTP.php'
            ];
            foreach ($mailerFiles as $file) {
                if (file_exists($file)) {
                    require_once $file;
                }
            }
        }
        
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $log = "--- Email ---\nPara: " . $to . "\nAssunto: " . $subject . "\nPHPMailer não encontrado, salvando em log\n\n";
            file_put_contents(__DIR__ . '/../email_log.txt', $log, FILE_APPEND);
            return false;
        }
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USER;
        $mail->Password = MAIL_PASS;
        $mail->SMTPSecure = MAIL_SECURE;
        $mail->Port = MAIL_PORT;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody ?: strip_tags($htmlBody);
        $mail->send();
        return true;
    } catch (Throwable $e) {
        $log = "--- Email Falhou ---\nPara: " . $to . "\nAssunto: " . $subject . "\nErro: " . $e->getMessage() . "\n\n";
        file_put_contents(__DIR__ . '/../email_log.txt', $log, FILE_APPEND);
        return false;
    }
}

/**
 * Log de erros seguro para produção
 */
function safeLog($message, $level = 'INFO') {
    if (EFI_ENV !== 'production') {
        error_log("[$level] $message");
    } else {
        // Em produção, logs vão para o error_log do PHP
        error_log("[PROMESTRE-$level] " . substr($message, 0, 1000));
    }
}
