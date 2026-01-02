<?php
require_once 'includes/config.php';
require_once 'includes/EfiCharges.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$professor_id = $_SESSION['user_id'];
$page_title = 'Assinatura do Sistema';

$error = '';
$success = '';
$payment_url = '';

$stmtSub = $pdo->prepare("SELECT * FROM assinaturas WHERE professor_id = ? AND tipo = 'sistema' ORDER BY id DESC LIMIT 1");
$stmtSub->execute([$professor_id]);
$assinatura = $stmtSub->fetch();

$valorPadrao = defined('PROMESTRE_SAAS_VALOR_MENSAL') && PROMESTRE_SAAS_VALOR_MENSAL !== '' ? PROMESTRE_SAAS_VALOR_MENSAL : '49.90';
$valorMinimo = defined('PROMESTRE_SAAS_VALOR_MIN') && PROMESTRE_SAAS_VALOR_MIN !== '' ? PROMESTRE_SAAS_VALOR_MIN : '0.01';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $valor = str_replace(',', '.', trim((string)($_POST['valor'] ?? $valorPadrao)));
        $valorFloat = (float)$valor;
        $minFloat = (float)str_replace(',', '.', (string)$valorMinimo);
        if ($valorFloat <= 0) {
            throw new Exception('Informe um valor mensal válido.');
        }
        if ($valorFloat < $minFloat) {
            throw new Exception('Para testes, o valor mínimo permitido é R$ ' . number_format($minFloat, 2, ',', '.') . '.');
        }

        $env = defined('EFI_ENV') ? EFI_ENV : 'production';
        $clientId = defined('EFI_CHARGES_CLIENT_ID') ? EFI_CHARGES_CLIENT_ID : '';
        $clientSecret = defined('EFI_CHARGES_CLIENT_SECRET') ? EFI_CHARGES_CLIENT_SECRET : '';

        if (empty($clientId) || empty($clientSecret)) {
            throw new Exception('Credenciais da Efí (Cobranças) não configuradas no .env.');
        }

        $efi = new EfiCharges($clientId, $clientSecret, $env);

        $stmtPlan = $pdo->prepare("SELECT * FROM planos_assinatura WHERE professor_id IS NULL AND tipo = 'sistema' LIMIT 1");
        $stmtPlan->execute();
        $plano = $stmtPlan->fetch();

        if (!$plano) {
            $respPlan = $efi->createPlan([
                'name' => 'Promestre - Assinatura do Sistema',
                'interval' => 1,
                'repeats' => null
            ]);

            $planId = $respPlan['data']['plan_id'] ?? null;
            if (!$planId) {
                throw new Exception('Erro ao criar plano de assinatura: ' . json_encode($respPlan));
            }

            $stmtIns = $pdo->prepare("INSERT INTO planos_assinatura (professor_id, tipo, nome, intervalo_meses, repeats, efi_plan_id, status) VALUES (NULL, 'sistema', ?, 1, NULL, ?, 'active')");
            $stmtIns->execute(['Promestre - Assinatura do Sistema', $planId]);

            $stmtPlan->execute();
            $plano = $stmtPlan->fetch();
        }

        $planId = $plano['efi_plan_id'];
        $valorCentavos = (int)round($valorFloat * 100);

        $expireAt = (new DateTime('+7 days'))->format('Y-m-d');

        $notificationUrlCandidate = defined('EFI_WEBHOOK_URL') && EFI_WEBHOOK_URL
            ? (string)EFI_WEBHOOK_URL
            : (SITE_URL . '/webhook_efiassinaturas.php');

        $hasValidNotificationUrl = false;
        if (filter_var($notificationUrlCandidate, FILTER_VALIDATE_URL)) {
            $parts = parse_url($notificationUrlCandidate);
            $host = strtolower((string)($parts['host'] ?? ''));
            $scheme = strtolower((string)($parts['scheme'] ?? ''));
            $hasValidNotificationUrl = ($scheme === 'https') && !in_array($host, ['localhost', '127.0.0.1'], true);
        }

        $metadata = [
            'custom_id' => 'assinatura_sistema_' . $professor_id,
        ];
        if ($hasValidNotificationUrl) {
            $metadata['notification_url'] = $notificationUrlCandidate;
        }

        $respLink = $efi->createPlanSubscriptionOneStepLink($planId, [
            'items' => [
                ['amount' => 1, 'name' => 'Assinatura Promestre', 'value' => $valorCentavos]
            ],
            'metadata' => $metadata,
            'settings' => [
                'payment_method' => 'credit_card',
                'request_delivery_address' => false,
                'expire_at' => $expireAt
            ]
        ]);

        $subscriptionId = $respLink['data']['subscription_id'] ?? null;
        $payment_url = $respLink['data']['payment_url'] ?? null;
        $chargeId = $respLink['data']['charge']['id'] ?? null;

        if (!$subscriptionId || !$payment_url) {
            throw new Exception('Erro ao gerar link da assinatura: ' . json_encode($respLink));
        }

        $stmtSave = $pdo->prepare("INSERT INTO assinaturas (professor_id, tipo, plano_id, efi_subscription_id, efi_charge_id, efi_payment_url, valor, status) VALUES (?, 'sistema', ?, ?, ?, ?, ?, 'new')");
        $stmtSave->execute([$professor_id, $plano['id'], $subscriptionId, $chargeId, $payment_url, $valorFloat]);

        $success = 'Link de assinatura gerado com sucesso.';

        $stmtSub->execute([$professor_id]);
        $assinatura = $stmtSub->fetch();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

require_once 'includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
    <h1 class="h4 mb-0"><i class="fas fa-crown me-2"></i> Assinatura do Sistema</h1>
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success shadow-sm"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>


<ul class="nav nav-tabs" id="assinaturaTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-gerar" data-bs-toggle="tab" data-bs-target="#pane-gerar" type="button" role="tab" aria-controls="pane-gerar" aria-selected="true">
            Gerar link
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-status" data-bs-toggle="tab" data-bs-target="#pane-status" type="button" role="tab" aria-controls="pane-status" aria-selected="false">
            Status
        </button>
    </li>
</ul>

<div class="tab-content border border-top-0 rounded-bottom p-3" id="assinaturaTabsContent">
    <div class="tab-pane fade show active" id="pane-gerar" role="tabpanel" aria-labelledby="tab-gerar" tabindex="0">
        <div class="card shadow-sm">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0">Gerar Link de Assinatura (Cartão)</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Valor mensal (R$)</label>
                        <input type="text" name="valor" class="form-control" value="<?php echo htmlspecialchars($valorPadrao); ?>" required>
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

    <div class="tab-pane fade" id="pane-status" role="tabpanel" aria-labelledby="tab-status" tabindex="0">
        <div class="card shadow-sm">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0">Status da Assinatura</h6>
            </div>
            <div class="card-body">
                <?php if (!$assinatura): ?>
                    <div class="text-muted">Nenhuma assinatura do sistema encontrada para este usuário.</div>
                <?php else: ?>
                    <div class="mb-2"><span class="text-muted small">Status:</span> <span class="fw-bold"><?php echo htmlspecialchars($assinatura['status']); ?></span></div>
                    <div class="mb-2"><span class="text-muted small">Valor:</span> <span class="fw-bold">R$ <?php echo number_format((float)$assinatura['valor'], 2, ',', '.'); ?></span></div>
                    <div class="mb-3"><span class="text-muted small">Criada em:</span> <span class="fw-bold"><?php echo htmlspecialchars($assinatura['criado_em']); ?></span></div>

                    <?php if (!empty($assinatura['cancel_at']) && empty($assinatura['canceled_at'])): ?>
                        <div class="alert alert-warning">
                            Cancelamento agendado para o fim do período: <strong><?php echo date('d/m/Y', strtotime($assinatura['cancel_at'])); ?></strong>
                        </div>
                        <a class="btn btn-outline-secondary" href="assinatura_sistema_desfazer_cancelamento.php">
                            <i class="fas fa-rotate-left me-2"></i> Desfazer cancelamento
                        </a>
                    <?php else: ?>
                        <a class="btn btn-outline-danger" href="assinatura_sistema_cancelar.php">
                            <i class="fas fa-ban me-2"></i> Cancelar ao final do período
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($assinatura['efi_payment_url'])): ?>
                        <hr>
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
