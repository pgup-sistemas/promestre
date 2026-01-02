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
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-user me-2"></i> <?php echo htmlspecialchars($aluno['nome']); ?></h1>
    <div>
        <a href="https://wa.me/<?php echo $whatsapp_num; ?>" target="_blank" class="btn btn-success me-2">
            <i class="fab fa-whatsapp me-2"></i> WhatsApp
        </a>
        <a href="alunos_cadastro.php?id=<?php echo $aluno['id']; ?>" class="btn btn-primary me-2">
            <i class="fas fa-edit me-2"></i> Editar
        </a>
        <a href="alunos.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Voltar
        </a>
    </div>
</div>

<div class="row">
    <!-- Card de Informações -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light">
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
                        <?php echo $aluno['email'] ? htmlspecialchars($aluno['email']) : '-'; ?>
                    </li>
                    <li class="list-group-item px-0">
                        <small class="text-muted d-block">Telefone</small>
                        <?php echo htmlspecialchars($aluno['telefone']); ?>
                    </li>
                    <li class="list-group-item px-0">
                        <small class="text-muted d-block">WhatsApp</small>
                        <?php echo htmlspecialchars($aluno['whatsapp']); ?>
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

    <!-- Histórico Financeiro -->
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm h-100 mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Histórico Financeiro</h5>
                <a href="mensalidades_gerar.php?aluno_id=<?php echo $aluno['id']; ?>" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-plus me-1"></i> Nova Cobrança
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Vencimento</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Pagamento</th>
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
                                        <td>
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

        <!-- Histórico de Aulas -->
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light">
                <h5 class="mb-0">Histórico de Aulas</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Horário</th>
                                <th>Título</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($aulas) > 0): ?>
                                <?php foreach ($aulas as $aula): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($aula['data_inicio'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($aula['data_inicio'])); ?> - <?php echo date('H:i', strtotime($aula['data_fim'])); ?></td>
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
