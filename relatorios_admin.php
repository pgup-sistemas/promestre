<?php
require_once 'includes/config.php';
require_once 'includes/user_management.php';

// Apenas admin pode acessar
requireAdmin();

$page_title = 'Relatórios Administrativos';
require_once 'includes/header.php';

// Filtros
$mes = filter_input(INPUT_GET, 'mes', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: date('m');
$ano = filter_input(INPUT_GET, 'ano', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: date('Y');
$professor_id = filter_input(INPUT_GET, 'professor_id', FILTER_VALIDATE_INT);

// Obter lista de professores para o filtro
$stmt = $pdo->query("SELECT id, nome FROM professores WHERE nivel = 'professor' ORDER BY nome");
$professores = $stmt->fetchAll();

// Consulta de mensalidades (Sistema todo ou por professor)
$sql = "SELECT 
    m.id,
    m.valor,
    m.data_vencimento,
    m.status,
    m.data_pagamento,
    m.forma_pagamento,
    p.nome as professor_nome,
    a.nome as aluno_nome
FROM mensalidades m
LEFT JOIN professores p ON m.professor_id = p.id
LEFT JOIN alunos a ON m.aluno_id = a.id
WHERE MONTH(m.data_vencimento) = ? 
AND YEAR(m.data_vencimento) = ?";

$params = [$mes, $ano];

if ($professor_id) {
    $sql .= " AND m.professor_id = ?";
    $params[] = $professor_id;
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
    <h1><i class="fas fa-chart-line me-2"></i> Relatórios Administrativos</h1>
    <a href="dashboard_admin.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Voltar
    </a>
</div>

<!-- Filtros -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Filtros</h6>
    </div>
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
                <label class="form-label">Professor</label>
                <select name="professor_id" class="form-select">
                    <option value="">Todos os professores</option>
                    <?php foreach($professores as $prof): ?>
                        <option value="<?= $prof['id'] ?>" <?= $professor_id == $prof['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($prof['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Cards de Resumo -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total a Receber</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">R$ <?= number_format($total_receber, 2, ',', '.') ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Recebido</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">R$ <?= number_format($total_recebido, 2, ',', '.') ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pendente</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">R$ <?= number_format($total_pendente, 2, ',', '.') ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Atrasado</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">R$ <?= number_format($total_atrasado, 2, ',', '.') ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabela Detalhada -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Detalhamento das Mensalidades (Sistema)</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Vencimento</th>
                        <th>Professor</th>
                        <th>Aluno</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Pagamento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mensalidades)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Nenhum registro encontrado para o período selecionado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($mensalidades as $m): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($m['data_vencimento'])) ?></td>
                                <td><?= htmlspecialchars($m['professor_nome']) ?></td>
                                <td><?= htmlspecialchars($m['aluno_nome']) ?></td>
                                <td>R$ <?= number_format($m['valor'], 2, ',', '.') ?></td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'pago' => 'success',
                                        'pendente' => 'warning',
                                        'atrasado' => 'danger',
                                        'cancelado' => 'secondary'
                                    ][$m['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $statusClass ?>">
                                        <?= ucfirst($m['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($m['data_pagamento']): ?>
                                        <?= date('d/m/Y', strtotime($m['data_pagamento'])) ?>
                                        <br>
                                        <small class="text-muted"><?= ucfirst($m['forma_pagamento'] ?? '') ?></small>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
