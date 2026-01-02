<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Dashboard';
$professor_id = $_SESSION['user_id'];

// Consultas para o Dashboard

// 1. Total de Alunos Ativos
$stmt = $pdo->prepare("SELECT COUNT(*) FROM alunos WHERE professor_id = ? AND status = 'ativo'");
$stmt->execute([$professor_id]);
$total_alunos = $stmt->fetchColumn();

// 1.1 Pré-matrículas Pendentes
$stmt = $pdo->prepare("SELECT COUNT(*) FROM alunos WHERE professor_id = ? AND status = 'inativo' AND observacoes LIKE '%Pré-matrícula realizada pelo site%'");
$stmt->execute([$professor_id]);
$pre_matriculas = $stmt->fetchColumn();

// 2. Mensalidades Pendentes
$stmt = $pdo->prepare("SELECT COUNT(*) FROM mensalidades WHERE professor_id = ? AND status = 'pendente'");
$stmt->execute([$professor_id]);
$mensalidades_pendentes = $stmt->fetchColumn();

// 3. Receita do Mês Atual
$mes_atual = date('m');
$ano_atual = date('Y');
$stmt = $pdo->prepare("
    SELECT SUM(valor) 
    FROM mensalidades 
    WHERE professor_id = ? 
    AND status = 'pago' 
    AND MONTH(data_pagamento) = ? 
    AND YEAR(data_pagamento) = ?
");
$stmt->execute([$professor_id, $mes_atual, $ano_atual]);
$receita_mes = $stmt->fetchColumn() ?: 0;

// 4. Próximas Aulas (Agenda)
$stmt = $pdo->prepare("
    SELECT ag.*, a.nome as aluno_nome 
    FROM agenda ag 
    LEFT JOIN alunos a ON ag.aluno_id = a.id 
    WHERE ag.professor_id = ? 
    AND ag.data_inicio >= NOW() 
    ORDER BY ag.data_inicio ASC 
    LIMIT 5
");
$stmt->execute([$professor_id]);
$proximas_aulas = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
    <a href="agendar.php?p=<?php echo $_SESSION['user_slug'] ?? ''; ?>" target="_blank" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-external-link-alt fa-sm text-white-50"></i> Ver Link de Agendamento</a>
</div>

<?php if (!isSystemSubscriptionActive($professor_id)): ?>
    <div class="alert alert-warning shadow-sm d-flex justify-content-between align-items-center" role="alert">
        <div>
            <div class="fw-bold">Assinatura inativa</div>
            <div class="small">Alguns recursos (PIX, cartão, recorrência e envio de cobranças) estão bloqueados até a ativação.</div>
        </div>
        <a href="assinatura_sistema.php" class="btn btn-sm btn-primary">
            <i class="fas fa-crown me-2"></i> Assinar / Reativar
        </a>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Card Alunos -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2" style="border-left: 4px solid var(--primary-color);">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Alunos Ativos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_alunos; ?></div>
                        <?php if ($pre_matriculas > 0): ?>
                            <div class="mt-2 text-xs text-danger font-weight-bold">
                                <a href="alunos.php?status=inativo" class="text-danger text-decoration-none">
                                    <i class="fas fa-exclamation-circle"></i> <?php echo $pre_matriculas; ?> nova(s) pré-matrícula(s)
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Mensalidades Pendentes -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2" style="border-left: 4px solid var(--warning-color);">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Cobranças Pendentes</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $mensalidades_pendentes; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-circle fa-2x text-gray-300 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Receita -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2" style="border-left: 4px solid var(--success-color);">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Receita (<?php echo date('M/Y'); ?>)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">R$ <?php echo number_format($receita_mes, 2, ',', '.'); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x text-gray-300 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Próximas Aulas</h6>
                <a href="agenda.php" class="btn btn-sm btn-primary">Ver Agenda Completa</a>
            </div>
            <div class="card-body">
                <?php if (count($proximas_aulas) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($proximas_aulas as $aula): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <div class="fw-bold text-primary">
                                        <?php echo date('d/m H:i', strtotime($aula['data_inicio'])); ?> - 
                                        <?php echo htmlspecialchars($aula['titulo']); ?>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($aula['aluno_nome'] ?? 'Sem aluno vinculado'); ?>
                                    </small>
                                </div>
                                <span class="badge bg-<?php echo $aula['status'] == 'agendado' ? 'primary' : ($aula['status'] == 'realizado' ? 'success' : 'secondary'); ?>">
                                    <?php echo ucfirst($aula['status']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted my-4">Nenhuma aula agendada para os próximos dias.</p>
                    <div class="text-center">
                        <a href="agenda_cadastro.php" class="btn btn-outline-primary btn-sm">Agendar Aula</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
