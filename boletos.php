<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Meus Boletos';
require_once 'includes/header.php';

// Filtros
$mes = filter_input(INPUT_GET, 'mes', FILTER_SANITIZE_STRING) ?: date('m');
$ano = filter_input(INPUT_GET, 'ano', FILTER_SANITIZE_STRING) ?: date('Y');
$aluno_id = filter_input(INPUT_GET, 'aluno_id', FILTER_VALIDATE_INT);
$status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING);

// Obter lista de alunos
$stmt = $pdo->prepare("SELECT id, nome FROM alunos WHERE professor_id = ? AND status = 'ativo' ORDER BY nome");
$stmt->execute([$_SESSION['user_id']]);
$alunos = $stmt->fetchAll();

// Consulta de mensalidades com boletos
$sql = "SELECT 
    m.id,
    m.valor,
    m.data_vencimento,
    m.status,
    m.data_pagamento,
    m.forma_pagamento,
    m.observacoes,
    m.boleto_url,
    m.boleto_barcode,
    m.boleto_expira_em,
    m.valor_multa,
    m.valor_juros,
    a.nome as aluno_nome
FROM mensalidades m
LEFT JOIN alunos a ON m.aluno_id = a.id
WHERE m.professor_id = ? 
AND m.boleto_url IS NOT NULL";

$params = [$_SESSION['user_id']];

if ($mes && $ano) {
    $sql .= " AND MONTH(m.data_vencimento) = ? AND YEAR(m.data_vencimento) = ?";
    $params[] = $mes;
    $params[] = $ano;
}

if ($aluno_id) {
    $sql .= " AND m.aluno_id = ?";
    $params[] = $aluno_id;
}

if ($status) {
    $sql .= " AND m.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY m.data_vencimento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$mensalidades = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-barcode me-2"></i> Meus Boletos</h1>
    <a href="mensalidades.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i> Nova Mensalidade</a>
</div>

<!-- Filtros -->
<div class="card shadow mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Mês</label>
                <select name="mes" class="form-select">
                    <option value="">Todos</option>
                    <?php for($i=1; $i<=12; $i++): ?>
                        <option value="<?= $i ?>" <?= $mes == $i ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Ano</label>
                <select name="ano" class="form-select">
                    <option value="">Todos</option>
                    <?php for($i=date('Y')-2; $i<=date('Y')+2; $i++): ?>
                        <option value="<?= $i ?>" <?= $ano == $i ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
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
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="pendente" <?= $status == 'pendente' ? 'selected' : '' ?>>Pendente</option>
                    <option value="pago" <?= $status == 'pago' ? 'selected' : '' ?>>Pago</option>
                    <option value="atrasado" <?= $status == 'atrasado' ? 'selected' : '' ?>>Atrasado</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label"> </label>
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de Boletos -->
<div class="card shadow">
    <div class="card-header">
        <h5 class="mb-0">Boletos Gerados</h5>
    </div>
    <div class="card-body">
        <?php if (empty($mensalidades)): ?>
            <div class="alert alert-info">Nenhum boleto encontrado.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Valor</th>
                            <th>Vencimento</th>
                            <th>Status</th>
                            <th>Expira em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($mensalidades as $m): ?>
                            <tr>
                                <td><?= $m['aluno_nome'] ?></td>
                                <td>R$ <?= number_format($m['valor'] + $m['valor_multa'] + $m['valor_juros'], 2, ',', '.') ?></td>
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
                                <td><?= $m['boleto_expira_em'] ? date('d/m/Y', strtotime($m['boleto_expira_em'])) : '-' ?></td>
                                <td>
                                    <?php if ($m['boleto_url']): ?>
                                        <a href="<?= $m['boleto_url'] ?>" target="_blank" class="btn btn-sm btn-primary me-2">
                                            <i class="fas fa-download me-2"></i> PDF
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($m['boleto_barcode']): ?>
                                        <button class="btn btn-sm btn-outline-primary me-2" onclick="copiarCodigo('<?= $m['boleto_barcode'] ?>')">
                                            <i class="fas fa-copy me-2"></i> Copiar Código
                                        </button>
                                    <?php endif; ?>
                                    
                                    <a href="boletos_gerar.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-barcode me-2"></i> Regenerar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function copiarCodigo(codigo) {
    navigator.clipboard.writeText(codigo).then(function() {
        alert('Código de barras copiado para a área de transferência!');
    }).catch(function(err) {
        console.error('Erro ao copiar: ', err);
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>