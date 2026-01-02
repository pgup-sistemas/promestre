<?php
require_once 'includes/config.php';
require_once 'includes/EfiCharges.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

requireActiveSystemSubscription();

$professor_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id) {
    redirect('mensalidades.php');
}

$stmt = $pdo->prepare("
    SELECT m.*, p.client_id_efi, p.client_secret_efi, p.ambiente_efi, a.nome as aluno_nome, a.email as aluno_email
    FROM mensalidades m
    JOIN professores p ON m.professor_id = p.id
    JOIN alunos a ON m.aluno_id = a.id
    WHERE m.id = ? AND m.professor_id = ?
");
$stmt->execute([$id, $professor_id]);
$dados = $stmt->fetch();

if (!$dados) {
    redirect('mensalidades.php');
}

$error = '';
$payment_url = '';

try {
    $env = !empty($dados['ambiente_efi']) ? $dados['ambiente_efi'] : (defined('EFI_ENV') ? EFI_ENV : 'production');

    $clientId = !empty($dados['client_id_efi']) ? $dados['client_id_efi'] : (defined('EFI_CHARGES_CLIENT_ID') ? EFI_CHARGES_CLIENT_ID : '');
    $clientSecret = !empty($dados['client_secret_efi']) ? $dados['client_secret_efi'] : (defined('EFI_CHARGES_CLIENT_SECRET') ? EFI_CHARGES_CLIENT_SECRET : '');

    if (empty($clientId) || empty($clientSecret)) {
        throw new Exception('Credenciais da Efí para Cobranças não configuradas.');
    }

    if ($dados['status'] === 'pago') {
        throw new Exception('Esta mensalidade já está paga.');
    }

    if (!empty($dados['efi_payment_url'])) {
        $payment_url = $dados['efi_payment_url'];
    } else {
        $efi = new EfiCharges($clientId, $clientSecret, $env);

        $valorCentavos = (int)round(((float)$dados['valor']) * 100);

        $payload = [
            'items' => [
                [
                    'name' => 'Mensalidade ' . SITE_NAME,
                    'value' => $valorCentavos,
                    'amount' => 1
                ]
            ],
            'metadata' => [
                'custom_id' => 'mensalidade_' . $dados['id'],
                'notification_url' => defined('EFI_WEBHOOK_URL') && EFI_WEBHOOK_URL ? EFI_WEBHOOK_URL : (SITE_URL . '/webhook_eficobrancas.php')
            ],
            'customer' => [
                'email' => !empty($dados['aluno_email']) ? $dados['aluno_email'] : $dados['id'] . '@' . parse_url(SITE_URL, PHP_URL_HOST)
            ],
            'settings' => [
                'payment_method' => 'credit_card'
            ]
        ];

        $resp = $efi->createPaymentLinkOneStep($payload);

        if (!isset($resp['data']['payment_url'])) {
            throw new Exception('Erro ao criar link de pagamento: ' . json_encode($resp));
        }

        $payment_url = $resp['data']['payment_url'];
        $efi_charge_id = $resp['data']['charge_id'] ?? null;

        $stmtUp = $pdo->prepare('UPDATE mensalidades SET efi_charge_id = ?, efi_payment_url = ? WHERE id = ?');
        $stmtUp->execute([$efi_charge_id, $payment_url, $dados['id']]);
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}

$page_title = 'Pagamento Cartão';
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-credit-card me-2"></i> Pagamento com Cartão</h1>
    <a href="mensalidades.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Voltar</a>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Dados do Pagamento</h6>
            </div>
            <div class="card-body p-4">

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php else: ?>
                    <div class="mb-3">
                        <div class="fw-bold">Aluno</div>
                        <div class="text-muted"><?php echo htmlspecialchars($dados['aluno_nome']); ?></div>
                    </div>

                    <div class="mb-4">
                        <div class="fw-bold">Valor</div>
                        <div class="fs-5">R$ <?php echo number_format($dados['valor'], 2, ',', '.'); ?></div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex">
                        <a class="btn btn-primary" href="<?php echo htmlspecialchars($payment_url); ?>" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-lock me-2"></i> Ir para Pagamento Seguro
                        </a>
                        <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars($payment_url); ?>" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-up-right-from-square me-2"></i> Abrir em Nova Aba
                        </a>
                    </div>

                    <div class="mt-3 text-muted small">
                        Após o pagamento, a confirmação ocorre via notificação da Efí (webhook).
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
