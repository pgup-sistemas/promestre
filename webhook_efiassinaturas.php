<?php

require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$headers = function_exists('getallheaders') ? getallheaders() : [];
$webhookSecretHeader = isset($headers['X-Webhook-Secret']) ? $headers['X-Webhook-Secret'] : (isset($headers['x-webhook-secret']) ? $headers['x-webhook-secret'] : null);

$webhookToken = isset($_GET['token']) ? (string)$_GET['token'] : null;

if (defined('EFI_WEBHOOK_SECRET') && EFI_WEBHOOK_SECRET) {
    $headerOk = ($webhookSecretHeader && hash_equals((string)EFI_WEBHOOK_SECRET, (string)$webhookSecretHeader));
    $tokenOk = ($webhookToken && hash_equals((string)EFI_WEBHOOK_SECRET, (string)$webhookToken));
    if (!$headerOk && !$tokenOk) {
        http_response_code(401);
        echo json_encode(['erro' => 'Não autorizado']);
        exit;
    }
}

$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['erro' => 'Payload inválido']);
    exit;
}

try {
    $subscriptionId = $data['data']['subscription_id'] ?? ($data['subscription_id'] ?? null);
    $status = $data['data']['status'] ?? ($data['status'] ?? null);
    $customId = $data['data']['custom_id'] ?? ($data['custom_id'] ?? null);

    $contratoId = null;
    if (is_string($customId) && preg_match('/^contrato_(\d+)$/', $customId, $m)) {
        $contratoId = (int)$m[1];
    }

    $assinaturaId = null;

    if (is_string($customId) && preg_match('/^assinatura_aluno_(\d+)$/', $customId, $m)) {
        $alunoId = (int)$m[1];
        $stmt = $pdo->prepare("SELECT id FROM assinaturas WHERE aluno_id = ? AND tipo = 'aluno' ORDER BY id DESC LIMIT 1");
        $stmt->execute([$alunoId]);
        $row = $stmt->fetch();
        if ($row) {
            $assinaturaId = (int)$row['id'];
        }
    }

    if (!$assinaturaId && is_string($customId) && preg_match('/^assinatura_sistema_(\d+)$/', $customId, $m)) {
        $professorId = (int)$m[1];
        $stmt = $pdo->prepare("SELECT id FROM assinaturas WHERE professor_id = ? AND tipo = 'sistema' ORDER BY id DESC LIMIT 1");
        $stmt->execute([$professorId]);
        $row = $stmt->fetch();
        if ($row) {
            $assinaturaId = (int)$row['id'];
        }
    }

    if (!$contratoId && $subscriptionId) {
        $stmt = $pdo->prepare('SELECT id FROM contratos_aluno WHERE efi_subscription_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$subscriptionId]);
        $row = $stmt->fetch();
        if ($row) {
            $contratoId = (int)$row['id'];
        }
    }

    if (!$assinaturaId && $subscriptionId) {
        $stmt = $pdo->prepare('SELECT id FROM assinaturas WHERE efi_subscription_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$subscriptionId]);
        $row = $stmt->fetch();
        if ($row) {
            $assinaturaId = (int)$row['id'];
        }
    }

    if ($assinaturaId) {
        $stmtUp = $pdo->prepare('UPDATE assinaturas SET status = ?, atualizado_em = NOW() WHERE id = ?');
        $stmtUp->execute([$status, $assinaturaId]);
    }

    if ($contratoId) {
        $stmtUp = $pdo->prepare('UPDATE contratos_aluno SET efi_payment_status = ?, atualizado_em = NOW() WHERE id = ?');
        $stmtUp->execute([$status, $contratoId]);

        $statusLower = strtolower((string)$status);
        if (in_array($statusLower, ['paid', 'settled', 'active'], true)) {
            $stmtPaid = $pdo->prepare("UPDATE contratos_aluno SET status = 'active', paid_at = COALESCE(paid_at, NOW()), atualizado_em = NOW() WHERE id = ?");
            $stmtPaid->execute([$contratoId]);
        } elseif (in_array($statusLower, ['canceled', 'cancelled'], true)) {
            $stmtCancel = $pdo->prepare("UPDATE contratos_aluno SET status = 'canceled', atualizado_em = NOW() WHERE id = ?");
            $stmtCancel->execute([$contratoId]);
        }
    }

    http_response_code(200);
    echo json_encode(['status' => 'ok']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao processar webhook', 'mensagem' => $e->getMessage()]);
}
