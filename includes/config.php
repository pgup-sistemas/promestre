<?php
// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'promestre');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configurações do Sistema
define('SITE_NAME', 'Promestre');
define('SITE_URL', 'http://localhost/promestre');

if (file_exists(__DIR__ . '/env_loader.php')) {
    require_once __DIR__ . '/env_loader.php';
}

// Fallback legado: use apenas se não houver arquivo .env na raiz do projeto
if (!file_exists(__DIR__ . '/../.env') && file_exists(__DIR__ . '/.env.php')) {
    require __DIR__ . '/.env.php';
}
if (defined('SMTP_HOST')) {
    if (!defined('MAIL_SMTP_ENABLED')) define('MAIL_SMTP_ENABLED', true);
    if (!defined('MAIL_HOST')) define('MAIL_HOST', SMTP_HOST);
    if (!defined('MAIL_PORT')) define('MAIL_PORT', SMTP_PORT);
    if (!defined('MAIL_USER')) define('MAIL_USER', SMTP_USER);
    if (!defined('MAIL_PASS')) define('MAIL_PASS', SMTP_PASS);
    if (!defined('MAIL_FROM')) define('MAIL_FROM', EMAIL_FROM);
    if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', EMAIL_FROM_NAME);
    if (!defined('MAIL_SECURE')) define('MAIL_SECURE', 'tls');
} else {
    if (!defined('MAIL_SMTP_ENABLED')) define('MAIL_SMTP_ENABLED', false);
    if (!defined('MAIL_HOST')) define('MAIL_HOST', '');
    if (!defined('MAIL_PORT')) define('MAIL_PORT', 587);
    if (!defined('MAIL_USER')) define('MAIL_USER', '');
    if (!defined('MAIL_PASS')) define('MAIL_PASS', '');
    if (!defined('MAIL_FROM')) define('MAIL_FROM', 'no-reply@localhost');
    if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', SITE_NAME);
    if (!defined('MAIL_SECURE')) define('MAIL_SECURE', 'tls');
}

// Iniciar Sessão
session_start();

// Conexão com o Banco de Dados
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

// Funções Utilitárias

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

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
        return false;
    }
}

function requireActiveSystemSubscription() {
    if (!isLoggedIn()) {
        redirect('index.php');
    }

    if (!isSystemSubscriptionActive($_SESSION['user_id'])) {
        redirect('acesso_restrito.php');
    }
}

function redirect($url) {
    header("Location: " . SITE_URL . "/" . $url);
    exit;
}

function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
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

function setFlash($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function sendMail($to, $subject, $htmlBody, $textBody = null) {
    if (!MAIL_SMTP_ENABLED) {
        $log = "--- Email ---\nPara: " . $to . "\nAssunto: " . $subject . "\n" . ($textBody ?: strip_tags($htmlBody)) . "\n\n";
        file_put_contents(__DIR__ . '/../email_log.txt', $log, FILE_APPEND);
        return true;
    }
    try {
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            $autoload = __DIR__ . '/../vendor/autoload.php';
            if (file_exists($autoload)) {
                require $autoload;
            }
        }
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            $log = "--- Email ---\nPara: " . $to . "\nAssunto: " . $subject . "\n" . ($textBody ?: strip_tags($htmlBody)) . "\n\n";
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
?>
