<?php
/**
 * Webhook Efí Cobranças - Recebe notificações de pagamento (cartão/link)
 * Endpoint: /webhook_eficobrancas.php
 * Método: POST
 */

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
    $event = $data['event'] ?? ($data['evento'] ?? 'desconhecido');
    $chargeId = $data['data']['charge_id'] ?? ($data['charge_id'] ?? null);
    $status = $data['data']['status'] ?? ($data['status'] ?? null);
    $customId = $data['data']['custom_id'] ?? ($data['custom_id'] ?? null);

    $contratoId = null;
    if (is_string($customId) && preg_match('/^contrato_(\d+)$/', $customId, $m)) {
        $contratoId = (int)$m[1];
    }

    if (!$contratoId && $chargeId) {
        $stmt = $pdo->prepare('SELECT id FROM contratos_aluno WHERE efi_charge_id = ? LIMIT 1');
        $stmt->execute([$chargeId]);
        $row = $stmt->fetch();
        if ($row) {
            $contratoId = (int)$row['id'];
        }
    }

    $mensalidadeId = null;
    if (is_string($customId) && preg_match('/^mensalidade_(\d+)$/', $customId, $m)) {
        $mensalidadeId = (int)$m[1];
    }

    if (!$mensalidadeId && $chargeId) {
        $stmt = $pdo->prepare('SELECT id FROM mensalidades WHERE efi_charge_id = ? LIMIT 1');
        $stmt->execute([$chargeId]);
        $row = $stmt->fetch();
        if ($row) {
            $mensalidadeId = (int)$row['id'];
        }
    }

    if ($contratoId) {
        $stmtUp = $pdo->prepare('UPDATE contratos_aluno SET efi_payment_status = ?, atualizado_em = NOW() WHERE id = ?');
        $stmtUp->execute([$status, $contratoId]);

        if (in_array($status, ['paid', 'settled'], true)) {
            $stmtPaid = $pdo->prepare("UPDATE contratos_aluno SET status = 'paid', paid_at = NOW(), atualizado_em = NOW() WHERE id = ?");
            $stmtPaid->execute([$contratoId]);
        }
    }

    if ($mensalidadeId) {
        $stmtUp = $pdo->prepare('UPDATE mensalidades SET efi_payment_status = ? WHERE id = ?');
        $stmtUp->execute([$status, $mensalidadeId]);

        if (in_array($status, ['paid', 'settled'], true)) {
            $stmtPaid = $pdo->prepare("UPDATE mensalidades SET status = 'pago', data_pagamento = ?, forma_pagamento = 'cartao', observacoes = CONCAT(COALESCE(observacoes, ''), '\nPago via cartão (Efí) em ', ?) WHERE id = ?");
            $stmtPaid->execute([
                date('Y-m-d'),
                date('Y-m-d H:i:s'),
                $mensalidadeId
            ]);
        }
    }

    http_response_code(200);
    echo json_encode(['status' => 'ok']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao processar webhook', 'mensagem' => $e->getMessage()]);
}
