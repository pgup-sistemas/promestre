<?php
require_once 'includes/config.php';
require_once 'includes/EfiCharges.php';

$token = isset($_GET['token']) ? (string)$_GET['token'] : '';
$secret = defined('EFI_WEBHOOK_SECRET') ? (string)EFI_WEBHOOK_SECRET : '';

if ($secret) {
    if (!$token || !hash_equals($secret, $token)) {
        http_response_code(401);
        echo "unauthorized";
        exit;
    }
}

try {
    $env = defined('EFI_ENV') ? EFI_ENV : 'production';
    $clientId = defined('EFI_CHARGES_CLIENT_ID') ? EFI_CHARGES_CLIENT_ID : '';
    $clientSecret = defined('EFI_CHARGES_CLIENT_SECRET') ? EFI_CHARGES_CLIENT_SECRET : '';

    if (empty($clientId) || empty($clientSecret)) {
        throw new Exception('Credenciais da Efí (Cobranças) não configuradas no .env.');
    }

    $efi = new EfiCharges($clientId, $clientSecret, $env);

    $stmt = $pdo->query("SELECT id, efi_subscription_id FROM assinaturas WHERE tipo = 'sistema' AND cancel_at IS NOT NULL AND cancel_at <= CURDATE() AND canceled_at IS NULL AND efi_subscription_id IS NOT NULL AND efi_subscription_id <> ''");
    $rows = $stmt->fetchAll();

    $processed = 0;
    $errors = 0;

    foreach ($rows as $row) {
        try {
            $efi->cancelSubscription($row['efi_subscription_id']);
            $stmtUp = $pdo->prepare("UPDATE assinaturas SET status = 'canceled', canceled_at = NOW(), atualizado_em = NOW() WHERE id = ?");
            $stmtUp->execute([(int)$row['id']]);
            $processed++;
        } catch (Throwable $e) {
            $errors++;
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['processed' => $processed, 'errors' => $errors]);

} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
