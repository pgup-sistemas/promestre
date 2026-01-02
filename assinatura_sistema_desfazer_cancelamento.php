<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$professor_id = $_SESSION['user_id'];

try {
    $stmtSub = $pdo->prepare("SELECT * FROM assinaturas WHERE professor_id = ? AND tipo = 'sistema' ORDER BY id DESC LIMIT 1");
    $stmtSub->execute([$professor_id]);
    $assinatura = $stmtSub->fetch();

    if (!$assinatura) {
        setFlash('Nenhuma assinatura encontrada.', 'danger');
        redirect('assinatura_sistema.php');
    }

    if (empty($assinatura['cancel_at'])) {
        setFlash('Não existe cancelamento agendado para desfazer.', 'warning');
        redirect('assinatura_sistema.php');
    }

    if (!empty($assinatura['canceled_at'])) {
        setFlash('Esta assinatura já foi cancelada.', 'danger');
        redirect('assinatura_sistema.php');
    }

    $stmtUp = $pdo->prepare('UPDATE assinaturas SET cancel_requested_at = NULL, cancel_at = NULL, cancel_reason = NULL WHERE id = ?');
    $stmtUp->execute([$assinatura['id']]);

    setFlash('Cancelamento agendado removido. Sua assinatura continuará ativa.', 'success');
    redirect('assinatura_sistema.php');

} catch (Throwable $e) {
    setFlash('Erro ao desfazer cancelamento: ' . $e->getMessage(), 'danger');
    redirect('assinatura_sistema.php');
}
