<?php
require_once 'includes/config.php';
require_once 'includes/EfiCharges.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

requireActiveSystemSubscription();

$professor_id = $_SESSION['user_id'];
$aluno_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$aluno_id) {
    redirect('alunos.php');
}

$stmt = $pdo->prepare('SELECT * FROM alunos WHERE id = ? AND professor_id = ?');
$stmt->execute([$aluno_id, $professor_id]);
$aluno = $stmt->fetch();

if (!$aluno) {
    redirect('alunos.php');
}

$error = '';
$success = '';
$payment_url = '';

$stmtSub = $pdo->prepare('SELECT * FROM assinaturas WHERE professor_id = ? AND aluno_id = ? AND tipo = \'aluno\' ORDER BY id DESC LIMIT 1');
$stmtSub->execute([$professor_id, $aluno_id]);
$assinatura = $stmtSub->fetch();

$valorPadrao = '';
$stmtLast = $pdo->prepare('SELECT valor FROM mensalidades WHERE professor_id = ? AND aluno_id = ? ORDER BY data_vencimento DESC LIMIT 1');
$stmtLast->execute([$professor_id, $aluno_id]);
$lastMens = $stmtLast->fetch();
if ($lastMens && isset($lastMens['valor'])) {
    $valorPadrao = (string)$lastMens['valor'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $valor = str_replace(',', '.', trim((string)($_POST['valor'] ?? '')));
        $valorFloat = (float)$valor;

        if ($valorFloat <= 0) {
            throw new Exception('Informe um valor mensal válido.');
        }

        $stmtEnv = $pdo->prepare('SELECT ambiente_efi FROM professores WHERE id = ?');
        $stmtEnv->execute([$professor_id]);
        $profEnv = $stmtEnv->fetch();

        $env = !empty($profEnv['ambiente_efi']) ? $profEnv['ambiente_efi'] : (defined('EFI_ENV') ? EFI_ENV : 'production');

        $clientId = defined('EFI_CHARGES_CLIENT_ID') ? EFI_CHARGES_CLIENT_ID : '';
        $clientSecret = defined('EFI_CHARGES_CLIENT_SECRET') ? EFI_CHARGES_CLIENT_SECRET : '';

        if (empty($clientId) || empty($clientSecret)) {
            throw new Exception('Credenciais da Efí (Cobranças) não configuradas no .env.');
        }

        $efi = new EfiCharges($clientId, $clientSecret, $env);

        $stmtPlan = $pdo->prepare('SELECT * FROM planos_assinatura WHERE professor_id = ? AND tipo = \'aluno\' LIMIT 1');
        $stmtPlan->execute([$professor_id]);
        $plano = $stmtPlan->fetch();

        if (!$plano) {
            $respPlan = $efi->createPlan([
                'name' => 'Mensalidade Alunos - ' . SITE_NAME,
                'interval' => 1,
                'repeats' => null
            ]);

            $planId = $respPlan['data']['plan_id'] ?? null;
            if (!$planId) {
                throw new Exception('Erro ao criar plano de assinatura: ' . json_encode($respPlan));
            }

            $stmtIns = $pdo->prepare('INSERT INTO planos_assinatura (professor_id, tipo, nome, intervalo_meses, repeats, efi_plan_id, status) VALUES (?, \'aluno\', ?, 1, NULL, ?, \'active\')');
            $stmtIns->execute([$professor_id, 'Mensalidade Alunos - ' . SITE_NAME, $planId]);

            $stmtPlan->execute([$professor_id]);
            $plano = $stmtPlan->fetch();
        }

        $planId = $plano['efi_plan_id'];
        $valorCentavos = (int)round($valorFloat * 100);

        $respLink = $efi->createPlanSubscriptionOneStepLink($planId, [
            'items' => [
                ['amount' => 1, 'name' => 'Mensalidade - ' . $aluno['nome'], 'value' => $valorCentavos]
            ],
            'metadata' => [
                'custom_id' => 'assinatura_aluno_' . $aluno_id,
                'notification_url' => defined('EFI_WEBHOOK_URL') && EFI_WEBHOOK_URL ? EFI_WEBHOOK_URL : (SITE_URL . '/webhook_efiassinaturas.php')
            ],
            'settings' => [
                'payment_method' => 'credit_card'
            ]
        ]);

        $subscriptionId = $respLink['data']['subscription_id'] ?? null;
        $payment_url = $respLink['data']['payment_url'] ?? null;
        $chargeId = $respLink['data']['charge']['id'] ?? null;

        if (!$subscriptionId || !$payment_url) {
            throw new Exception('Erro ao gerar link da assinatura: ' . json_encode($respLink));
        }

        $stmtSave = $pdo->prepare('INSERT INTO assinaturas (professor_id, aluno_id, tipo, plano_id, efi_subscription_id, efi_charge_id, efi_payment_url, valor, status) VALUES (?, ?, \'aluno\', ?, ?, ?, ?, ?, \'new\')');
        $stmtSave->execute([$professor_id, $aluno_id, $plano['id'], $subscriptionId, $chargeId, $payment_url, $valorFloat]);

        $success = 'Link de assinatura gerado com sucesso.';

        $stmtSub->execute([$professor_id, $aluno_id]);
        $assinatura = $stmtSub->fetch();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = 'Assinatura do Aluno';
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-repeat me-2"></i> Assinatura do Aluno</h1>
    <a href="alunos_detalhes.php?id=<?php echo $aluno_id; ?>" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Voltar</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success shadow-sm"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0">Gerar Link de Assinatura (Cartão)</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-muted small">Aluno</div>
                    <div class="fw-bold"><?php echo htmlspecialchars($aluno['nome']); ?></div>
                </div>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Valor mensal (R$)</label>
                        <input type="text" name="valor" class="form-control" value="<?php echo htmlspecialchars($valorPadrao); ?>" placeholder="Ex: 150.00" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-link me-2"></i> Gerar Link
                    </button>
                </form>

                <?php if (!empty($payment_url)): ?>
                    <hr>
                    <a class="btn btn-success" href="<?php echo htmlspecialchars($payment_url); ?>" target="_blank" rel="noopener noreferrer">
                        <i class="fas fa-lock me-2"></i> Abrir Pagamento Seguro
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0">Última Assinatura</h6>
            </div>
            <div class="card-body">
                <?php if (!$assinatura): ?>
                    <div class="text-muted">Nenhuma assinatura criada para este aluno.</div>
                <?php else: ?>
                    <div class="mb-2"><span class="text-muted small">Status:</span> <span class="fw-bold"><?php echo htmlspecialchars($assinatura['status']); ?></span></div>
                    <div class="mb-2"><span class="text-muted small">Valor:</span> <span class="fw-bold">R$ <?php echo number_format((float)$assinatura['valor'], 2, ',', '.'); ?></span></div>
                    <div class="mb-3"><span class="text-muted small">Criada em:</span> <span class="fw-bold"><?php echo htmlspecialchars($assinatura['criado_em']); ?></span></div>

                    <?php if (!empty($assinatura['efi_payment_url'])): ?>
                        <a class="btn btn-outline-primary" href="<?php echo htmlspecialchars($assinatura['efi_payment_url']); ?>" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-up-right-from-square me-2"></i> Abrir Link
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
