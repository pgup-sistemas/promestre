<?php
require_once 'includes/config.php';
require_once 'includes/EfiCharges.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$professor_id = $_SESSION['user_id'];

try {
    $stmtSub = $pdo->prepare("SELECT * FROM assinaturas WHERE professor_id = ? AND tipo = 'sistema' ORDER BY id DESC LIMIT 1");
    $stmtSub->execute([$professor_id]);
    $assinatura = $stmtSub->fetch();

    if (!$assinatura || empty($assinatura['efi_subscription_id'])) {
        setFlash('Nenhuma assinatura ativa encontrada para cancelamento.', 'danger');
        redirect('assinatura_sistema.php');
    }

    if (!empty($assinatura['cancel_at'])) {
        setFlash('Cancelamento já está agendado para esta assinatura.', 'warning');
        redirect('assinatura_sistema.php');
    }

    $env = defined('EFI_ENV') ? EFI_ENV : 'production';
    $clientId = defined('EFI_CHARGES_CLIENT_ID') ? EFI_CHARGES_CLIENT_ID : '';
    $clientSecret = defined('EFI_CHARGES_CLIENT_SECRET') ? EFI_CHARGES_CLIENT_SECRET : '';

    if (empty($clientId) || empty($clientSecret)) {
        throw new Exception('Credenciais da Efí (Cobranças) não configuradas no .env.');
    }

    $efi = new EfiCharges($clientId, $clientSecret, $env);

    $resp = $efi->getSubscription($assinatura['efi_subscription_id']);
    $nextExpireAt = $resp['data']['next_expire_at'] ?? null;

    if (!$nextExpireAt) {
        throw new Exception('Não foi possível obter next_expire_at da assinatura na Efí.');
    }

    $cancelAt = date('Y-m-d', strtotime((string)$nextExpireAt));

    $stmtUp = $pdo->prepare('UPDATE assinaturas SET cancel_requested_at = NOW(), cancel_at = ?, paid_until = ?, cancel_reason = ? WHERE id = ?');
    $stmtUp->execute([$cancelAt, $cancelAt, 'cancel_at_period_end', $assinatura['id']]);

    setFlash('Cancelamento agendado para o fim do período (até ' . date('d/m/Y', strtotime($cancelAt)) . ').', 'success');
    redirect('assinatura_sistema.php');

} catch (Throwable $e) {
    setFlash('Erro ao agendar cancelamento: ' . $e->getMessage(), 'danger');
    redirect('assinatura_sistema.php');
}
