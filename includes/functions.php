<?php
/**
 * Funções essenciais do sistema Promestre
 */

/**
 * Verifica se usuário está logado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Limpa dados para evitar SQL Injection
 */
function clean($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Redireciona para outra página
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Define mensagem flash para exibir após redirect
 */
function setFlash($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Formata data para exibição
 */
function formatDate($date) {
    if (empty($date)) return '-';
    return date('d/m/Y', strtotime($date));
}

/**
 * Formata data e hora para exibição
 */
function formatDateTime($datetime) {
    if (empty($datetime)) return '-';
    return date('d/m/Y H:i', strtotime($datetime));
}

/**
 * Formata valor monetário
 */
function formatMoney($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

/**
 * Formata telefone
 */
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($phone) == 11) {
        return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 5) . '-' . substr($phone, 7);
    } elseif (strlen($phone) == 10) {
        return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 4) . '-' . substr($phone, 6);
    }
    
    return $phone;
}

/**
 * Formata CPF
 */
function formatCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) == 11) {
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9);
    }
    return $cpf;
}

/**
 * Gera slug a partir de texto
 */
function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

/**
 * Valida email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Gera string aleatória
 */
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
}

/**
 * Verifica se é AJAX request
 */
function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Log de erros
 */
function logError($message, $context = []) {
    $log = date('Y-m-d H:i:s') . ' - ' . $message;
    if (!empty($context)) {
        $log .= ' - Context: ' . json_encode($context);
    }
    error_log($log);
}

/**
 * Obter cor para nível de log
 */
function getLogLevelColor($action) {
    $colors = [
        'LOGIN' => 'success',
        'LOGOUT' => 'secondary',
        'RESET_PASSWORD' => 'warning',
        'TOGGLE_STATUS' => 'info',
        'DELETE_USER' => 'danger',
        'CREATE_USER' => 'primary',
        'UPDATE_USER' => 'info'
    ];
    
    return $colors[$action] ?? 'secondary';
}

/**
 * Verificar se assinatura do sistema está ativa
 */
function isSystemSubscriptionActive($professor_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM assinaturas 
            WHERE professor_id = ? 
            AND status = 'ativa' 
            AND data_fim >= NOW()
        ");
        $stmt->execute([$professor_id]);
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        return false;
    }
}
?>
