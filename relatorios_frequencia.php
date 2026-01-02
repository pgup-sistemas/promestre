<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Relatórios de Frequência';
require_once 'includes/header.php';

// Filtros
$mes = filter_input(INPUT_GET, 'mes', FILTER_SANITIZE_STRING) ?: date('m');
$ano = filter_input(INPUT_GET, 'ano', FILTER_SANITIZE_STRING) ?: date('Y');
$aluno_id = filter_input(INPUT_GET, 'aluno_id', FILTER_VALIDATE_INT);

// Obter lista de alunos
$stmt = $pdo->prepare("SELECT id, nome FROM alunos WHERE professor_id = ? AND status = 'ativo' ORDER BY nome");
$stmt->execute([$_SESSION['user_id']]);
$alunos = $stmt->fetchAll();

// Consulta de frequência
$sql = "SELECT 
    a.id,
    a.titulo,
    a.data_inicio,
    a.aluno_id,
    al.nome as aluno_nome,
    a.presenca,
    a.status
FROM agenda a
LEFT JOIN alunos al ON a.aluno_id = al.id
WHERE a.professor_id = ? 
AND MONTH(a.data_inicio) = ? 
AND YEAR(a.data_inicio) = ?";

$params = [$_SESSION['user_id'], $mes, $ano];

if ($aluno_id) {
    $sql .= " AND a.aluno_id = ?";
    $params[] = $aluno_id;
}

$sql .= " ORDER BY a.data_inicio DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$agendamentos = $stmt->fetchAll();

// Calcular estatísticas
$total_aulas = count($agendamentos);
$presentes = count(array_filter($agendamentos, fn($a) => $a['presenca'] === 'presente'));
$ausentes = count(array_filter($agendamentos, fn($a) => $a['presenca'] === 'ausente'));
$justificadas = count(array_filter($agendamentos, fn($a) => $a['presenca'] === 'justificada'));
$nao_marcadas = count(array_filter($agendamentos, fn($a) => $a['presenca'] === null));
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-chart-bar me-2"></i> Relatórios de Frequência</h1>
    <div class="btn-group" role="group">
        <a href="relatorios.php" class="btn btn-outline-primary">Financeiro</a>
        <a href="relatorios_frequencia.php" class="btn btn-primary">Frequência</a>
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
                <h5 class="card-title">Total de Aulas</h5>
                <h2 class="mb-0"><?= $total_aulas ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Presentes</h5>
                <h2 class="mb-0"><?= $presentes ?></h2>
                <small><?= $total_aulas > 0 ? round(($presentes/$total_aulas)*100, 1) : 0 ?>%</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">Ausências</h5>
                <h2 class="mb-0"><?= $ausentes ?></h2>
                <small><?= $total_aulas > 0 ? round(($ausentes/$total_aulas)*100, 1) : 0 ?>%</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Justificadas</h5>
                <h2 class="mb-0"><?= $justificadas ?></h2>
                <small><?= $total_aulas > 0 ? round(($justificadas/$total_aulas)*100, 1) : 0 ?>%</small>
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
                <a href="relatorios_exportar_excel.php?mes=<?= $mes ?>&ano=<?= $ano ?>&aluno_id=<?= $aluno_id ?>&tipo=frequencia"
                   class="btn btn-success me-2">
                    <i class="fas fa-file-excel me-2"></i> Excel
                </a>
                <a href="relatorios_exportar_pdf.php?mes=<?= $mes ?>&ano=<?= $ano ?>&aluno_id=<?= $aluno_id ?>&tipo=frequencia"
                   class="btn btn-danger">
                    <i class="fas fa-file-pdf me-2"></i> PDF
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Frequência -->
<div class="card shadow">
    <div class="card-header">
        <h5 class="mb-0">Detalhes de Frequência</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Horário</th>
                        <th>Aluno</th>
                        <th>Aula</th>
                        <th>Status</th>
                        <th>Presença</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($agendamentos as $agenda): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($agenda['data_inicio'])) ?></td>
                            <td><?= date('H:i', strtotime($agenda['data_inicio'])) ?></td>
                            <td><?= $agenda['aluno_nome'] ?: 'N/A' ?></td>
                            <td><?= $agenda['titulo'] ?></td>
                            <td>
                                <?php if($agenda['status'] === 'agendado'): ?>
                                    <span class="badge bg-primary">Agendado</span>
                                <?php elseif($agenda['status'] === 'realizado'): ?>
                                    <span class="badge bg-success">Realizado</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Cancelado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($agenda['presenca'] === 'presente'): ?>
                                    <span class="badge bg-success">Presente</span>
                                <?php elseif($agenda['presenca'] === 'ausente'): ?>
                                    <span class="badge bg-warning">Ausente</span>
                                <?php elseif($agenda['presenca'] === 'justificada'): ?>
                                    <span class="badge bg-info">Justificada</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Não marcada</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>