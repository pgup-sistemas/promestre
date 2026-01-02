<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Relatórios Financeiros';
require_once 'includes/header.php';

// Filtros
$mes = filter_input(INPUT_GET, 'mes', FILTER_SANITIZE_STRING) ?: date('m');
$ano = filter_input(INPUT_GET, 'ano', FILTER_SANITIZE_STRING) ?: date('Y');
$aluno_id = filter_input(INPUT_GET, 'aluno_id', FILTER_VALIDATE_INT);

// Obter lista de alunos
$stmt = $pdo->prepare("SELECT id, nome FROM alunos WHERE professor_id = ? AND status = 'ativo' ORDER BY nome");
$stmt->execute([$_SESSION['user_id']]);
$alunos = $stmt->fetchAll();

// Consulta de mensalidades
$sql = "SELECT 
    m.id,
    m.valor,
    m.data_vencimento,
    m.status,
    m.data_pagamento,
    m.forma_pagamento,
    m.observacoes,
    m.valor_multa,
    m.valor_juros,
    m.dias_atraso,
    a.nome as aluno_nome
FROM mensalidades m
LEFT JOIN alunos a ON m.aluno_id = a.id
WHERE m.professor_id = ? 
AND MONTH(m.data_vencimento) = ? 
AND YEAR(m.data_vencimento) = ?";

$params = [$_SESSION['user_id'], $mes, $ano];

if ($aluno_id) {
    $sql .= " AND m.aluno_id = ?";
    $params[] = $aluno_id;
}

$sql .= " ORDER BY m.data_vencimento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$mensalidades = $stmt->fetchAll();

// Calcular estatísticas
$total_receber = array_sum(array_map(function($m) { return $m['valor']; }, $mensalidades));
$total_recebido = array_sum(array_map(function($m) { return $m['status'] === 'pago' ? $m['valor'] : 0; }, $mensalidades));
$total_atrasado = array_sum(array_map(function($m) { return $m['status'] === 'atrasado' ? $m['valor'] : 0; }, $mensalidades));
$total_pendente = array_sum(array_map(function($m) { return $m['status'] === 'pendente' ? $m['valor'] : 0; }, $mensalidades));
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-chart-bar me-2"></i> Relatórios Financeiros</h1>
    <div class="btn-group" role="group">
        <a href="relatorios.php" class="btn btn-primary">Financeiro</a>
        <a href="relatorios_frequencia.php" class="btn btn-outline-primary">Frequência</a>
    </div>
</div>

<!-- Filtros -->
<div class="card shadow mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Mês</label>
                <select name="mes" class="form-select">
                    <?php for($i=1; $i<=12; $i++): ?>
                        <option value="<?= $i ?>" <?= $mes == $i ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Ano</label>
                <select name="ano" class="form-select">
                    <?php for($i=date('Y')-2; $i<=date('Y')+2; $i++): ?>
                        <option value="<?= $i ?>" <?= $ano == $i ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Aluno</label>
                <select name="aluno_id" class="form-select">
                    <option value="">Todos os alunos</option>
                    <?php foreach($alunos as $aluno): ?>
                        <option value="<?= $aluno['id'] ?>" <?= $aluno_id == $aluno['id'] ? 'selected' : '' ?>>
                            <?= $aluno['nome'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label"> </label>
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<!-- Estatísticas Gerais -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Total a Receber</h5>
                <h2 class="mb-0">R$ <?= number_format($total_receber, 2, ',', '.') ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Recebido</h5>
                <h2 class="mb-0">R$ <?= number_format($total_recebido, 2, ',', '.') ?></h2>
                <small><?= $total_receber > 0 ? round(($total_recebido/$total_receber)*100, 1) : 0 ?>%</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">Em Atraso</h5>
                <h2 class="mb-0">R$ <?= number_format($total_atrasado, 2, ',', '.') ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Pendente</h5>
                <h2 class="mb-0">R$ <?= number_format($total_pendente, 2, ',', '.') ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Ações de Exportação -->
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Exportar Relatório</h6>
                <p class="text-muted">Baixe este relatório em diferentes formatos</p>
            </div>
            <div class="col-md-6 text-end">
                <a href="relatorios_exportar_excel.php?mes=<?= $mes ?>&ano=<?= $ano ?>&aluno_id=<?= $aluno_id ?>&tipo=financeiro" 
                   class="btn btn-success me-2">
                    <i class="fas fa-file-excel me-2"></i> Excel
                </a>
                <a href="relatorios_exportar_pdf.php?mes=<?= $mes ?>&ano=<?= $ano ?>&aluno_id=<?= $aluno_id ?>&tipo=financeiro" 
                   class="btn btn-danger">
                    <i class="fas fa-file-pdf me-2"></i> PDF
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Mensalidades -->
<div class="card shadow">
    <div class="card-header">
        <h5 class="mb-0">Detalhes de Mensalidades</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Aluno</th>
                        <th>Valor</th>
                        <th>Vencimento</th>
                        <th>Status</th>
                        <th>Pagamento</th>
                        <th>Forma</th>
                        <th>Multa</th>
                        <th>Juros</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($mensalidades as $m): ?>
                        <tr>
                            <td><?= $m['aluno_nome'] ?></td>
                            <td>R$ <?= number_format($m['valor'], 2, ',', '.') ?></td>
                            <td><?= date('d/m/Y', strtotime($m['data_vencimento'])) ?></td>
                            <td>
                                <?php if($m['status'] === 'pago'): ?>
                                    <span class="badge bg-success">Pago</span>
                                <?php elseif($m['status'] === 'atrasado'): ?>
                                    <span class="badge bg-warning">Atrasado</span>
                                <?php elseif($m['status'] === 'pendente'): ?>
                                    <span class="badge bg-info">Pendente</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Cancelado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $m['data_pagamento'] ? date('d/m/Y', strtotime($m['data_pagamento'])) : '-' ?>
                            </td>
                            <td><?= $m['forma_pagamento'] ?: '-' ?></td>
                            <td><?= $m['valor_multa'] > 0 ? 'R$ ' . number_format($m['valor_multa'], 2, ',', '.') : '-' ?></td>
                            <td><?= $m['valor_juros'] > 0 ? 'R$ ' . number_format($m['valor_juros'], 2, ',', '.') : '-' ?></td>
                            <td>
                                <strong>R$ <?= number_format($m['valor'] + $m['valor_multa'] + $m['valor_juros'], 2, ',', '.') ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>