<?php
require_once 'includes/config.php';
require_once 'includes/EfiCharges.php';
require_once 'includes/EfiPay.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

requireActiveSystemSubscription();

$professor_id = $_SESSION['user_id'];
$aluno_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$aluno_id) {
    redirect('alunos.php');
}

$stmtAluno = $pdo->prepare("SELECT a.*, t.preco_padrao, t.nome AS tipo_aula_nome FROM alunos a LEFT JOIN tipos_aula t ON a.tipo_aula_id = t.id WHERE a.id = ? AND a.professor_id = ? AND a.deleted_at IS NULL");
$stmtAluno->execute([$aluno_id, $professor_id]);
$aluno = $stmtAluno->fetch();

if (!$aluno) {
    redirect('alunos.php');
}

$descontoPercent = 10.00;

$error = '';
$success = '';

$stmtLast = $pdo->prepare("SELECT * FROM contratos_aluno WHERE professor_id = ? AND aluno_id = ? ORDER BY id DESC LIMIT 1");
$stmtLast->execute([$professor_id, $aluno_id]);
$contrato = $stmtLast->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    try {
        if ($action === 'save_draft') {
            $data_inicio = (string)($_POST['data_inicio'] ?? '');
            $duracao_meses = (int)($_POST['duracao_meses'] ?? 0);
            $parcelas = (int)($_POST['parcelas'] ?? 0);
            $forma_pagamento = (string)($_POST['forma_pagamento'] ?? '');

            if (!$data_inicio || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_inicio)) {
                throw new Exception('Informe uma data de início válida.');
            }
            if ($duracao_meses <= 0) {
                throw new Exception('Informe a duração (meses) válida.');
            }
            if ($parcelas <= 0) {
                throw new Exception('Informe a quantidade de parcelas válida.');
            }
            if ($parcelas > $duracao_meses) {
                throw new Exception('As parcelas não podem ser maiores que a duração do contrato (meses).');
            }
            if (!in_array($forma_pagamento, ['pix_avista', 'boleto_avista', 'cartao_avista', 'cartao_recorrente'], true)) {
                throw new Exception('Selecione uma forma de pagamento válida.');
            }

            $valorMensal = (float)($aluno['preco_padrao'] ?? 0);
            if ($valorMensal <= 0) {
                throw new Exception('O aluno precisa ter um Tipo de Aula com preço padrão para gerar contrato.');
            }

            $valorTotal = round($valorMensal * $duracao_meses, 2);
            $valorAvista = round($valorTotal * (1 - ($descontoPercent / 100)), 2);
            $valorParcela = round($valorTotal / $parcelas, 2);

            if ($contrato && in_array($contrato['status'], ['draft'], true)) {
                $stmtUp = $pdo->prepare("UPDATE contratos_aluno SET data_inicio = ?, duracao_meses = ?, parcelas = ?, valor_mensal = ?, valor_total = ?, valor_avista = ?, valor_parcela = ?, forma_pagamento = ?, desconto_avista_percent = ?, atualizado_em = NOW() WHERE id = ?");
                $stmtUp->execute([$data_inicio, $duracao_meses, $parcelas, $valorMensal, $valorTotal, $valorAvista, $valorParcela, $forma_pagamento, $descontoPercent, $contrato['id']]);
            } else {
                $stmtIns = $pdo->prepare("INSERT INTO contratos_aluno (professor_id, aluno_id, status, data_inicio, duracao_meses, parcelas, desconto_avista_percent, valor_mensal, valor_total, valor_avista, valor_parcela, forma_pagamento) VALUES (?, ?, 'draft', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtIns->execute([$professor_id, $aluno_id, $data_inicio, $duracao_meses, $parcelas, $descontoPercent, $valorMensal, $valorTotal, $valorAvista, $valorParcela, $forma_pagamento]);
            }

            $success = 'Contrato (rascunho) salvo com sucesso.';
            $stmtLast->execute([$professor_id, $aluno_id]);
            $contrato = $stmtLast->fetch();
        }

        if ($action === 'confirm') {
            if (!$contrato || $contrato['status'] !== 'draft') {
                throw new Exception('Você precisa ter um contrato em rascunho para confirmar.');
            }

            if (!empty($contrato['efi_payment_url']) || !empty($contrato['txid_efi']) || !empty($contrato['efi_subscription_id'])) {
                throw new Exception('Este contrato já possui cobrança gerada.');
            }

            $forma = (string)$contrato['forma_pagamento'];

            $stmtConf = $pdo->prepare("UPDATE contratos_aluno SET status = 'confirmed', atualizado_em = NOW() WHERE id = ?");
            $stmtConf->execute([$contrato['id']]);

            $env = defined('EFI_ENV') ? EFI_ENV : 'production';

            if ($forma === 'cartao_recorrente') {
                $clientId = defined('EFI_CHARGES_CLIENT_ID') ? EFI_CHARGES_CLIENT_ID : '';
                $clientSecret = defined('EFI_CHARGES_CLIENT_SECRET') ? EFI_CHARGES_CLIENT_SECRET : '';
                if (empty($clientId) || empty($clientSecret)) {
                    throw new Exception('Credenciais da Efí (Cobranças) não configuradas no .env.');
                }

                $efi = new EfiCharges($clientId, $clientSecret, $env);

                $stmtPlan = $pdo->prepare("SELECT * FROM planos_assinatura WHERE professor_id = ? AND tipo = 'aluno' AND intervalo_meses = 1 AND repeats = ? LIMIT 1");
                $stmtPlan->execute([$professor_id, $contrato['parcelas']]);
                $plano = $stmtPlan->fetch();

                if (!$plano) {
                    $respPlan = $efi->createPlan([
                        'name' => 'Contrato Aluno - ' . SITE_NAME . ' (' . (int)$contrato['parcelas'] . 'x)',
                        'interval' => 1,
                        'repeats' => (int)$contrato['parcelas']
                    ]);

                    $planId = $respPlan['data']['plan_id'] ?? null;
                    if (!$planId) {
                        throw new Exception('Erro ao criar plano de assinatura: ' . json_encode($respPlan));
                    }

                    $stmtInsPlan = $pdo->prepare("INSERT INTO planos_assinatura (professor_id, tipo, nome, intervalo_meses, repeats, efi_plan_id, status) VALUES (?, 'aluno', ?, 1, ?, ?, 'active')");
                    $stmtInsPlan->execute([$professor_id, 'Contrato Aluno - ' . SITE_NAME . ' (' . (int)$contrato['parcelas'] . 'x)', (int)$contrato['parcelas'], $planId]);

                    $stmtPlan->execute([$professor_id, $contrato['parcelas']]);
                    $plano = $stmtPlan->fetch();
                }

                $planId = $plano['efi_plan_id'];
                $valorCentavos = (int)round(((float)$contrato['valor_parcela']) * 100);

                $respLink = $efi->createPlanSubscriptionOneStepLink($planId, [
                    'items' => [
                        ['amount' => 1, 'name' => 'Contrato - ' . $aluno['nome'], 'value' => $valorCentavos]
                    ],
                    'metadata' => [
                        'custom_id' => 'contrato_' . $contrato['id'],
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

                $stmtUpC = $pdo->prepare("UPDATE contratos_aluno SET efi_subscription_id = ?, efi_charge_id = ?, efi_payment_url = ?, status = 'active', atualizado_em = NOW() WHERE id = ?");
                $stmtUpC->execute([$subscriptionId, $chargeId, $payment_url, $contrato['id']]);

                $success = 'Contrato confirmado e assinatura (cartão recorrente) criada com sucesso.';

            } else {
                // Pagamento à vista com desconto fixo (10%)
                $valor = (float)$contrato['valor_avista'];

                if ($forma === 'cartao_avista') {
                    $clientId = defined('EFI_CHARGES_CLIENT_ID') ? EFI_CHARGES_CLIENT_ID : '';
                    $clientSecret = defined('EFI_CHARGES_CLIENT_SECRET') ? EFI_CHARGES_CLIENT_SECRET : '';
                    if (empty($clientId) || empty($clientSecret)) {
                        throw new Exception('Credenciais da Efí (Cobranças) não configuradas no .env.');
                    }

                    $efi = new EfiCharges($clientId, $clientSecret, $env);
                    $valorCentavos = (int)round($valor * 100);

                    $payload = [
                        'items' => [
                            ['name' => 'Contrato à vista - ' . $aluno['nome'], 'value' => $valorCentavos, 'amount' => 1]
                        ],
                        'metadata' => [
                            'custom_id' => 'contrato_' . $contrato['id'],
                            'notification_url' => defined('EFI_WEBHOOK_URL') && EFI_WEBHOOK_URL ? EFI_WEBHOOK_URL : (SITE_URL . '/webhook_eficobrancas.php')
                        ],
                        'customer' => [
                            'email' => !empty($aluno['email']) ? $aluno['email'] : ('contrato' . $contrato['id'] . '@' . parse_url(SITE_URL, PHP_URL_HOST))
                        ],
                        'settings' => [
                            'payment_method' => 'credit_card'
                        ]
                    ];

                    $resp = $efi->createPaymentLinkOneStep($payload);
                    $payment_url = $resp['data']['payment_url'] ?? null;
                    $efi_charge_id = $resp['data']['charge_id'] ?? null;

                    if (!$payment_url) {
                        throw new Exception('Erro ao criar link de pagamento: ' . json_encode($resp));
                    }

                    $stmtUpC = $pdo->prepare("UPDATE contratos_aluno SET efi_charge_id = ?, efi_payment_url = ?, status = 'confirmed', atualizado_em = NOW() WHERE id = ?");
                    $stmtUpC->execute([$efi_charge_id, $payment_url, $contrato['id']]);

                    $success = 'Contrato confirmado e link de pagamento (cartão à vista) gerado.';
                }

                if ($forma === 'pix_avista') {
                    // Usa credenciais Pix do professor
                    $stmtProf = $pdo->prepare('SELECT client_id_efi, client_secret_efi, certificado_efi, chave_pix, ambiente_efi FROM professores WHERE id = ?');
                    $stmtProf->execute([$professor_id]);
                    $prof = $stmtProf->fetch();

                    if (empty($prof['client_id_efi']) || empty($prof['client_secret_efi']) || empty($prof['certificado_efi'])) {
                        throw new Exception('Para gerar PIX, o professor precisa configurar credenciais/certificado no Perfil.');
                    }

                    $envPix = !empty($prof['ambiente_efi']) ? $prof['ambiente_efi'] : $env;
                    $sandbox = (strtolower((string)$envPix) === 'sandbox');

                    $efiPix = new EfiPay($prof['client_id_efi'], $prof['client_secret_efi'], $prof['certificado_efi'], $sandbox);

                    if (empty($prof['chave_pix'])) {
                        throw new Exception('O professor precisa cadastrar uma chave PIX no Perfil.');
                    }

                    $txid = md5(uniqid(rand(), true));
                    $txid = substr(str_replace(['-','_'], '', $txid) . time(), 0, 30);

                    $respCob = $efiPix->createCob($txid, $valor, $prof['chave_pix'], null, 'Contrato à vista - ' . SITE_NAME);

                    if (!isset($respCob['txid'])) {
                        throw new Exception('Erro ao criar cobrança PIX: ' . json_encode($respCob));
                    }

                    $qrcodeText = null;
                    if (isset($respCob['loc']['id'])) {
                        $respQr = $efiPix->getQrCode($respCob['loc']['id']);
                        $qrcodeText = $respQr['qrcode'] ?? null;
                    }

                    $stmtUpC = $pdo->prepare("UPDATE contratos_aluno SET txid_efi = ?, link_pagamento = ?, status = 'confirmed', atualizado_em = NOW() WHERE id = ?");
                    $stmtUpC->execute([$respCob['txid'], $qrcodeText, $contrato['id']]);

                    $success = 'Contrato confirmado e PIX gerado.';
                }

                if ($forma === 'boleto_avista') {
                    $stmtProf = $pdo->prepare('SELECT client_id_efi, client_secret_efi, certificado_efi, ambiente_efi FROM professores WHERE id = ?');
                    $stmtProf->execute([$professor_id]);
                    $prof = $stmtProf->fetch();

                    if (empty($prof['client_id_efi']) || empty($prof['client_secret_efi']) || empty($prof['certificado_efi'])) {
                        throw new Exception('Para gerar boleto, o professor precisa configurar credenciais/certificado no Perfil.');
                    }

                    $envPix = !empty($prof['ambiente_efi']) ? $prof['ambiente_efi'] : $env;
                    $sandbox = (strtolower((string)$envPix) === 'sandbox');

                    $efiPix = new EfiPay($prof['client_id_efi'], $prof['client_secret_efi'], $prof['certificado_efi'], $sandbox);

                    $cliente = [
                        'name' => $aluno['nome'],
                        'cpf' => preg_replace('/\D/', '', (string)($aluno['cpf'] ?? ''))
                    ];

                    $vencimento = (new DateTime('+7 days'))->format('Y-m-d');

                    $respBol = $efiPix->criarBoleto($valor, $vencimento, $cliente, []);

                    $chargeId = $respBol['data']['charge_id'] ?? ($respBol['data']['charge_id'] ?? null);
                    $boletoLink = $respBol['data']['payment']['banking_billet']['link'] ?? null;
                    $boletoBarcode = $respBol['data']['payment']['banking_billet']['barcode'] ?? null;
                    $boletoPdf = $respBol['data']['payment']['banking_billet']['pdf']['charge'] ?? null;

                    $stmtUpC = $pdo->prepare("UPDATE contratos_aluno SET efi_charge_id = ?, boleto_url = ?, boleto_barcode = ?, boleto_pdf_url = ?, status = 'confirmed', atualizado_em = NOW() WHERE id = ?");
                    $stmtUpC->execute([$chargeId, $boletoLink, $boletoBarcode, $boletoPdf, $contrato['id']]);

                    $success = 'Contrato confirmado e boleto gerado.';
                }
            }

            $stmtLast->execute([$professor_id, $aluno_id]);
            $contrato = $stmtLast->fetch();
        }

        if ($action === 'renew') {
            if (!$contrato || !in_array($contrato['status'], ['completed', 'paid'], true)) {
                throw new Exception('A renovação só pode ser feita após o fim do ciclo atual.');
            }

            $stmtIns = $pdo->prepare("INSERT INTO contratos_aluno (professor_id, aluno_id, status, data_inicio, duracao_meses, parcelas, desconto_avista_percent, valor_mensal, valor_total, valor_avista, valor_parcela, forma_pagamento) VALUES (?, ?, 'draft', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtIns->execute([
                $professor_id,
                $aluno_id,
                $contrato['data_inicio'],
                (int)$contrato['duracao_meses'],
                (int)$contrato['parcelas'],
                (float)$contrato['desconto_avista_percent'],
                (float)$contrato['valor_mensal'],
                (float)$contrato['valor_total'],
                (float)$contrato['valor_avista'],
                (float)$contrato['valor_parcela'],
                (string)$contrato['forma_pagamento']
            ]);

            $success = 'Renovação criada como rascunho. Ajuste os dados e confirme.';

            $stmtLast->execute([$professor_id, $aluno_id]);
            $contrato = $stmtLast->fetch();
        }

    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$page_title = 'Contrato do Aluno';
require_once 'includes/header.php';

$valorMensal = (float)($aluno['preco_padrao'] ?? 0);
$defaultDataInicio = (new DateTime('+7 days'))->format('Y-m-d');
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
    <h1 class="h4 mb-0"><i class="fas fa-file-signature me-2"></i> Contrato do Aluno</h1>
    <a href="alunos_detalhes.php?id=<?php echo (int)$aluno_id; ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success shadow-sm"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>


<?php
$tem_contrato = !empty($contrato);
$is_draft = $tem_contrato && $contrato['status'] === 'draft';
$tem_pagamento_link = $tem_contrato && !empty($contrato['efi_payment_url']);
$tem_pix = $tem_contrato && !empty($contrato['link_pagamento']);
$tem_boleto = $tem_contrato && (!empty($contrato['boleto_url']) || !empty($contrato['boleto_barcode']) || !empty($contrato['boleto_pdf_url']));
$pode_renovar = $tem_contrato && ($contrato['status'] === 'completed' || $contrato['status'] === 'paid');
?>

<ul class="nav nav-tabs" id="contratoAlunoTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-rascunho" data-bs-toggle="tab" data-bs-target="#pane-rascunho" type="button" role="tab" aria-controls="pane-rascunho" aria-selected="true">
            Rascunho
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-cobranca" data-bs-toggle="tab" data-bs-target="#pane-cobranca" type="button" role="tab" aria-controls="pane-cobranca" aria-selected="false">
            Cobrança
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-status" data-bs-toggle="tab" data-bs-target="#pane-status" type="button" role="tab" aria-controls="pane-status" aria-selected="false">
            Status
        </button>
    </li>
</ul>

<div class="tab-content border border-top-0 rounded-bottom p-3" id="contratoAlunoTabsContent">
    <div class="tab-pane fade show active" id="pane-rascunho" role="tabpanel" aria-labelledby="tab-rascunho" tabindex="0">
        <div class="card shadow-sm">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0">Dados do Contrato (Rascunho)</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-muted small">Aluno</div>
                    <div class="fw-bold"><?php echo htmlspecialchars($aluno['nome']); ?></div>
                    <div class="text-muted small">Tipo de aula: <?php echo htmlspecialchars((string)($aluno['tipo_aula_nome'] ?? '-')); ?></div>
                    <div class="text-muted small">Preço padrão (mensal): R$ <?php echo number_format($valorMensal, 2, ',', '.'); ?></div>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="save_draft">

                    <div class="mb-3">
                        <label class="form-label">Data de início do contrato</label>
                        <input type="date" name="data_inicio" class="form-control" value="<?php echo htmlspecialchars((string)($contrato['data_inicio'] ?? $defaultDataInicio)); ?>" required>
                    </div>

                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Duração (meses)</label>
                            <input type="number" min="1" name="duracao_meses" class="form-control" value="<?php echo htmlspecialchars((string)($contrato['duracao_meses'] ?? 6)); ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Parcelas (<= meses)</label>
                            <input type="number" min="1" name="parcelas" class="form-control" value="<?php echo htmlspecialchars((string)($contrato['parcelas'] ?? 6)); ?>" required>
                        </div>
                    </div>

                    <div class="mt-2 mb-3">
                        <label class="form-label">Forma de pagamento</label>
                        <select name="forma_pagamento" class="form-select" required>
                            <?php $fp = (string)($contrato['forma_pagamento'] ?? 'cartao_recorrente'); ?>
                            <option value="cartao_recorrente" <?php echo $fp === 'cartao_recorrente' ? 'selected' : ''; ?>>Cartão recorrente (cobrança mensal automática)</option>
                            <option value="pix_avista" <?php echo $fp === 'pix_avista' ? 'selected' : ''; ?>>PIX à vista (10% off)</option>
                            <option value="boleto_avista" <?php echo $fp === 'boleto_avista' ? 'selected' : ''; ?>>Boleto à vista (10% off)</option>
                            <option value="cartao_avista" <?php echo $fp === 'cartao_avista' ? 'selected' : ''; ?>>Cartão à vista (10% off)</option>
                        </select>
                    </div>

                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-save me-1"></i> Salvar rascunho
                        </button>
                    </div>
                </form>

                <?php if ($is_draft): ?>
                    <hr>
                    <form method="POST" onsubmit="return confirm('Confirmar o contrato e gerar cobrança automaticamente?');">
                        <input type="hidden" name="action" value="confirm">
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fas fa-check me-1"></i> Confirmar e gerar cobrança
                        </button>
                    </form>
                <?php endif; ?>

                <hr>
                <a class="btn btn-outline-secondary btn-sm" href="contrato_gerar.php?id=<?php echo (int)$aluno_id; ?>" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-print me-1"></i> Ver/Imprimir contrato (PDF)
                </a>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="pane-cobranca" role="tabpanel" aria-labelledby="tab-cobranca" tabindex="0">
        <div class="card shadow-sm">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0">Cobrança</h6>
            </div>
            <div class="card-body">
                <?php if (!$tem_contrato): ?>
                    <div class="text-muted">Crie e salve um rascunho para gerar a cobrança.</div>
                <?php else: ?>
                    <?php if ($tem_pagamento_link): ?>
                        <a class="btn btn-outline-primary btn-sm" href="<?php echo htmlspecialchars($contrato['efi_payment_url']); ?>" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-up-right-from-square me-1"></i> Abrir Pagamento Seguro
                        </a>
                    <?php endif; ?>

                    <?php if ($tem_pix): ?>
                        <hr>
                        <div class="mb-2 text-muted small">PIX Copia e Cola</div>
                        <div class="input-group">
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($contrato['link_pagamento']); ?>" readonly>
                            <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard.writeText(document.querySelector('#pixCopiaCola').value)">Copiar</button>
                        </div>
                        <input type="hidden" id="pixCopiaCola" value="<?php echo htmlspecialchars($contrato['link_pagamento']); ?>">
                    <?php endif; ?>

                    <?php if ($tem_boleto): ?>
                        <hr>
                        <div class="mb-2 text-muted small">Boleto</div>
                        <div class="d-flex flex-wrap gap-2">
                            <?php if (!empty($contrato['boleto_url'])): ?>
                                <a class="btn btn-outline-primary btn-sm" href="<?php echo htmlspecialchars($contrato['boleto_url']); ?>" target="_blank" rel="noopener noreferrer">
                                    <i class="fas fa-receipt me-1"></i> Abrir boleto
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($contrato['boleto_pdf_url'])): ?>
                                <a class="btn btn-outline-secondary btn-sm" href="<?php echo htmlspecialchars($contrato['boleto_pdf_url']); ?>" target="_blank" rel="noopener noreferrer">
                                    <i class="fas fa-file-pdf me-1"></i> Abrir PDF
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($contrato['boleto_barcode'])): ?>
                            <div class="mt-2 input-group">
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($contrato['boleto_barcode']); ?>" readonly>
                                <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard.writeText(document.querySelector('#boletoLinha').value)">Copiar linha</button>
                            </div>
                            <input type="hidden" id="boletoLinha" value="<?php echo htmlspecialchars($contrato['boleto_barcode']); ?>">
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!$tem_pagamento_link && !$tem_pix && !$tem_boleto): ?>
                        <div class="text-muted">Nenhuma cobrança gerada ainda para este contrato.</div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="pane-status" role="tabpanel" aria-labelledby="tab-status" tabindex="0">
        <div class="card shadow-sm">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0">Status do Contrato</h6>
            </div>
            <div class="card-body">
                <?php if (!$tem_contrato): ?>
                    <div class="text-muted">Nenhum contrato criado para este aluno.</div>
                <?php else: ?>
                    <div class="mb-2"><span class="text-muted small">Status:</span> <span class="fw-bold"><?php echo htmlspecialchars($contrato['status']); ?></span></div>
                    <div class="mb-2"><span class="text-muted small">Início:</span> <span class="fw-bold"><?php echo htmlspecialchars($contrato['data_inicio']); ?></span></div>
                    <div class="mb-2"><span class="text-muted small">Meses:</span> <span class="fw-bold"><?php echo (int)$contrato['duracao_meses']; ?></span></div>
                    <div class="mb-2"><span class="text-muted small">Parcelas:</span> <span class="fw-bold"><?php echo (int)$contrato['parcelas']; ?></span></div>
                    <div class="mb-2"><span class="text-muted small">Valor mensal:</span> <span class="fw-bold">R$ <?php echo number_format((float)$contrato['valor_mensal'], 2, ',', '.'); ?></span></div>
                    <div class="mb-2"><span class="text-muted small">Total:</span> <span class="fw-bold">R$ <?php echo number_format((float)$contrato['valor_total'], 2, ',', '.'); ?></span></div>
                    <div class="mb-2"><span class="text-muted small">À vista (<?php echo number_format((float)$contrato['desconto_avista_percent'], 0); ?>% off):</span> <span class="fw-bold">R$ <?php echo number_format((float)$contrato['valor_avista'], 2, ',', '.'); ?></span></div>
                    <div class="mb-3"><span class="text-muted small">Parcela:</span> <span class="fw-bold">R$ <?php echo number_format((float)$contrato['valor_parcela'], 2, ',', '.'); ?></span></div>

                    <?php if ($pode_renovar): ?>
                        <hr>
                        <form method="POST" onsubmit="return confirm('Criar renovação como rascunho?');">
                            <input type="hidden" name="action" value="renew">
                            <button type="submit" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-rotate me-1"></i> Renovar
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
