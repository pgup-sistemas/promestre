<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$professor_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id) {
    redirect('alunos.php');
}

// Buscar dados do aluno
$stmt = $pdo->prepare("SELECT a.*, t.nome as tipo_aula_nome, t.cor as tipo_aula_cor 
                       FROM alunos a 
                       LEFT JOIN tipos_aula t ON a.tipo_aula_id = t.id 
                       WHERE a.id = ? AND a.professor_id = ? AND a.deleted_at IS NULL");
$stmt->execute([$id, $professor_id]);
$aluno = $stmt->fetch();

if (!$aluno) {
    redirect('alunos.php');
}

// Buscar histórico de mensalidades
$stmt = $pdo->prepare("SELECT * FROM mensalidades WHERE aluno_id = ? ORDER BY data_vencimento DESC");
$stmt->execute([$id]);
$mensalidades = $stmt->fetchAll();

// Buscar histórico de aulas (presença)
$stmt = $pdo->prepare("SELECT * FROM agenda WHERE aluno_id = ? ORDER BY data_inicio DESC LIMIT 20");
$stmt->execute([$id]);
$aulas = $stmt->fetchAll();

$page_title = 'Detalhes do Aluno';
require_once 'includes/header.php';

// Formatar WhatsApp
$whatsapp_num = preg_replace('/[^0-9]/', '', $aluno['whatsapp']);
if (strlen($whatsapp_num) <= 11) {
    $whatsapp_num = '55' . $whatsapp_num;
}

// Formatar telefone
$telefone_num = preg_replace('/[^0-9]/', '', (string)($aluno['telefone'] ?? ''));
if (strlen($telefone_num) <= 11 && $telefone_num !== '') {
    $telefone_num = '55' . $telefone_num;
}

// Verificar se aluno é menor de idade
$aluno_menor = false;
if (!empty($aluno['data_nascimento'])) {
    try {
        $dn = new DateTime($aluno['data_nascimento']);
        $hoje = new DateTime('today');
        $aluno_menor = ($dn->diff($hoje)->y < 18);
    } catch (Exception $e) {
        $aluno_menor = false;
    }
}

// Formatar contatos do responsável
$resp_whatsapp_num = preg_replace('/[^0-9]/', '', (string)($aluno['responsavel_whatsapp'] ?? ''));
if (strlen($resp_whatsapp_num) <= 11 && $resp_whatsapp_num !== '') {
    $resp_whatsapp_num = '55' . $resp_whatsapp_num;
}
$resp_telefone_num = preg_replace('/[^0-9]/', '', (string)($aluno['responsavel_telefone'] ?? ''));
if (strlen($resp_telefone_num) <= 11 && $resp_telefone_num !== '') {
    $resp_telefone_num = '55' . $resp_telefone_num;
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
    <div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <h1 class="h4 mb-0"><i class="fas fa-user me-2"></i> <?php echo htmlspecialchars($aluno['nome']); ?></h1>
            <?php if ($aluno_menor): ?>
                <span class="badge bg-warning text-dark">Menor de idade</span>
            <?php endif; ?>
            <?php if (!empty($aluno['possui_responsavel'])): ?>
                <span class="badge bg-info text-dark">Com responsável</span>
            <?php endif; ?>
        </div>
        <?php if (!empty($aluno['tipo_aula_nome'])): ?>
            <div class="mt-1">
                <span class="badge" style="background-color: <?php echo $aluno['tipo_aula_cor']; ?>">
                    <?php echo htmlspecialchars($aluno['tipo_aula_nome']); ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="https://wa.me/<?php echo $whatsapp_num; ?>" target="_blank" class="btn btn-success btn-sm">
            <i class="fab fa-whatsapp me-1"></i> WhatsApp
        </a>
        <a href="contrato_aluno.php?id=<?php echo $aluno['id']; ?>" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-file-signature me-1"></i> Contrato
        </a>
        <a href="aluno_assinatura.php?id=<?php echo $aluno['id']; ?>" class="btn btn-outline-dark btn-sm">
            <i class="fas fa-repeat me-1"></i> Assinatura
        </a>
        <a href="alunos_cadastro.php?id=<?php echo $aluno['id']; ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-edit me-1"></i> Editar
        </a>
        <a href="alunos.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Voltar
        </a>
    </div>
</div>

<?php $tem_responsavel = (!empty($aluno['possui_responsavel']) || !empty($aluno['responsavel_nome'])); ?>

<ul class="nav nav-tabs" id="alunoTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-resumo" data-bs-toggle="tab" data-bs-target="#pane-resumo" type="button" role="tab" aria-controls="pane-resumo" aria-selected="true">
            Resumo
        </button>
    </li>
    <?php if ($tem_responsavel): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-responsavel" data-bs-toggle="tab" data-bs-target="#pane-responsavel" type="button" role="tab" aria-controls="pane-responsavel" aria-selected="false">
                Responsável
            </button>
        </li>
    <?php endif; ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-financeiro" data-bs-toggle="tab" data-bs-target="#pane-financeiro" type="button" role="tab" aria-controls="pane-financeiro" aria-selected="false">
            Financeiro
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-aulas" data-bs-toggle="tab" data-bs-target="#pane-aulas" type="button" role="tab" aria-controls="pane-aulas" aria-selected="false">
            Aulas
        </button>
    </li>
</ul>

<div class="tab-content border border-top-0 rounded-bottom p-3" id="alunoTabsContent">
    <div class="tab-pane fade show active" id="pane-resumo" role="tabpanel" aria-labelledby="tab-resumo" tabindex="0">
        <div class="card shadow-sm">
            <div class="card-header bg-light py-2">
                <h5 class="mb-0">Dados do Aluno</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0">
                        <small class="text-muted d-block">Status</small>
                        <?php if ($aluno['status'] == 'ativo'): ?>
                            <span class="badge bg-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inativo</span>
                        <?php endif; ?>
                    </li>
                    <li class="list-group-item px-0">
                        <small class="text-muted d-block">Tipo de Aula</small>
                        <?php if ($aluno['tipo_aula_nome']): ?>
                            <span class="badge" style="background-color: <?php echo $aluno['tipo_aula_cor']; ?>">
                                <?php echo htmlspecialchars($aluno['tipo_aula_nome']); ?>
                            </span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </li>
                    <li class="list-group-item px-0">
                        <small class="text-muted d-block">Email</small>
                        <?php if (!empty($aluno['email'])): ?>
                            <a href="mailto:<?php echo htmlspecialchars($aluno['email']); ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($aluno['email']); ?>
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </li>
                    <li class="list-group-item px-0">
                        <small class="text-muted d-block">Telefone</small>
                        <?php if (!empty($aluno['telefone'])): ?>
                            <a href="tel:<?php echo htmlspecialchars($telefone_num !== '' ? ('+' . $telefone_num) : ''); ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($aluno['telefone']); ?>
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </li>
                    <li class="list-group-item px-0">
                        <small class="text-muted d-block">WhatsApp</small>
                        <?php if (!empty($aluno['whatsapp'])): ?>
                            <a href="https://wa.me/<?php echo $whatsapp_num; ?>" target="_blank" class="text-decoration-none">
                                <?php echo htmlspecialchars($aluno['whatsapp']); ?>
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </li>
                    <li class="list-group-item px-0">
                        <small class="text-muted d-block">Data de Nascimento</small>
                        <?php echo $aluno['data_nascimento'] ? date('d/m/Y', strtotime($aluno['data_nascimento'])) : '-'; ?>
                    </li>
                    <li class="list-group-item px-0">
                        <small class="text-muted d-block">CPF</small>
                        <?php echo $aluno['cpf'] ? htmlspecialchars($aluno['cpf']) : '-'; ?>
                    </li>
                </ul>
                <?php if ($aluno['observacoes']): ?>
                    <div class="mt-3">
                        <small class="text-muted d-block">Observações</small>
                        <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($aluno['observacoes'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($tem_responsavel): ?>
        <div class="tab-pane fade" id="pane-responsavel" role="tabpanel" aria-labelledby="tab-responsavel" tabindex="0">
            <div class="card shadow-sm">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Responsável</h5>
                    <?php if ($aluno_menor): ?>
                        <span class="badge bg-warning text-dark">Obrigatório</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="fw-bold"><?php echo htmlspecialchars((string)($aluno['responsavel_nome'] ?? '')); ?></div>
                    <?php if (!empty($aluno['responsavel_parentesco'])): ?>
                        <div class="text-muted small"><?php echo htmlspecialchars((string)$aluno['responsavel_parentesco']); ?></div>
                    <?php endif; ?>

                    <div class="mt-3">
                        <div class="small text-muted">Contato</div>
                        <?php if (!empty($aluno['responsavel_whatsapp'])): ?>
                            <div>
                                <a href="https://wa.me/<?php echo $resp_whatsapp_num; ?>" target="_blank" class="text-decoration-none">
                                    <i class="fab fa-whatsapp me-1"></i><?php echo htmlspecialchars((string)$aluno['responsavel_whatsapp']); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($aluno['responsavel_telefone'])): ?>
                            <div>
                                <a href="tel:<?php echo htmlspecialchars($resp_telefone_num !== '' ? ('+' . $resp_telefone_num) : ''); ?>" class="text-decoration-none">
                                    <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars((string)$aluno['responsavel_telefone']); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($aluno['responsavel_email'])): ?>
                            <div>
                                <a href="mailto:<?php echo htmlspecialchars((string)$aluno['responsavel_email']); ?>" class="text-decoration-none">
                                    <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars((string)$aluno['responsavel_email']); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($aluno['responsavel_cpf'])): ?>
                            <div class="text-muted small mt-2">CPF: <?php echo htmlspecialchars((string)$aluno['responsavel_cpf']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="tab-pane fade" id="pane-financeiro" role="tabpanel" aria-labelledby="tab-financeiro" tabindex="0">
        <div class="card shadow-sm">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Histórico Financeiro</h5>
                <a href="mensalidades_gerar.php?aluno_id=<?php echo $aluno['id']; ?>" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-plus me-1"></i> Nova Cobrança
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Vencimento</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th class="d-none d-md-table-cell">Pagamento</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($mensalidades) > 0): ?>
                                <?php foreach ($mensalidades as $mensalidade): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($mensalidade['data_vencimento'])); ?></td>
                                        <td>R$ <?php echo number_format($mensalidade['valor'], 2, ',', '.'); ?></td>
                                        <td>
                                            <?php if ($mensalidade['status'] == 'pago'): ?>
                                                <span class="badge bg-success">Pago</span>
                                            <?php elseif ($mensalidade['status'] == 'pendente'): ?>
                                                <?php if (strtotime($mensalidade['data_vencimento']) < strtotime(date('Y-m-d'))): ?>
                                                    <span class="badge bg-danger">Atrasado</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Pendente</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php echo ucfirst($mensalidade['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <?php echo $mensalidade['data_pagamento'] ? date('d/m/Y', strtotime($mensalidade['data_pagamento'])) : '-'; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="mensalidades_editar.php?id=<?php echo $mensalidade['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <p class="text-muted mb-0">Nenhuma mensalidade registrada.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="pane-aulas" role="tabpanel" aria-labelledby="tab-aulas" tabindex="0">
        <div class="card shadow-sm">
            <div class="card-header bg-light py-2">
                <h5 class="mb-0">Histórico de Aulas</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th class="d-none d-md-table-cell">Horário</th>
                                <th>Título</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($aulas) > 0): ?>
                                <?php foreach ($aulas as $aula): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($aula['data_inicio'])); ?></td>
                                        <td class="d-none d-md-table-cell"><?php echo date('H:i', strtotime($aula['data_inicio'])); ?> - <?php echo date('H:i', strtotime($aula['data_fim'])); ?></td>
                                        <td><?php echo htmlspecialchars($aula['titulo']); ?></td>
                                        <td>
                                            <?php if ($aula['status'] == 'realizado'): ?>
                                                <span class="badge bg-success">Realizado</span>
                                            <?php elseif ($aula['status'] == 'cancelado'): ?>
                                                <span class="badge bg-danger">Cancelado</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">Agendado</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <p class="text-muted mb-0">Nenhuma aula registrada.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
