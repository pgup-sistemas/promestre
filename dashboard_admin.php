<?php
require_once 'includes/config.php';
require_once 'includes/user_management.php';

// Apenas admin pode acessar
requireAdmin();

// Verificar se é primeiro acesso admin e redirecionar para onboarding
if (!isset($_SESSION['admin_onboarding_completed'])) {
    // Verificar se já existem outros professores além do admin
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM professores WHERE nivel = 'professor' AND ativo = TRUE");
    $stmt->execute();
    $total_professores = $stmt->fetchColumn();
    
    // Se é o primeiro acesso ou não tem professores, mostrar onboarding
    if ($total_professores == 0) {
        redirect('onboarding_admin.php');
    } else {
        // Se já tem professores, marcar onboarding como completo
        $_SESSION['admin_onboarding_completed'] = true;
    }
}

$page_title = 'Dashboard Administrativo';

// Estatísticas globais do sistema
$stmt = $pdo->query("SELECT COUNT(*) FROM professores WHERE ativo = TRUE AND nivel = 'professor'");
$total_professores = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM professores WHERE nivel = 'admin'");
$total_admins = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM alunos WHERE status = 'ativo'");
$total_alunos_sistema = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM mensalidades WHERE status = 'pendente'");
$mensalidades_pendentes_sistema = $stmt->fetchColumn();

// Professores com assinatura ativa
$stmt = $pdo->query("
    SELECT COUNT(DISTINCT a.professor_id) 
    FROM assinaturas a 
    WHERE a.status = 'ativa' AND a.paid_until >= NOW()
");
$assinaturas_ativas = $stmt->fetchColumn();

// Receita do mês atual
$mes_atual = date('m');
$ano_atual = date('Y');
$stmt = $pdo->prepare("
    SELECT SUM(valor) 
    FROM mensalidades 
    WHERE status = 'pago' 
    AND MONTH(data_pagamento) = ? 
    AND YEAR(data_pagamento) = ?
");
$stmt->execute([$mes_atual, $ano_atual]);
$receita_mes_sistema = $stmt->fetchColumn() ?: 0;

// Professores recentes (últimos 7 dias)
$stmt = $pdo->query("
    SELECT nome, email, data_cadastro, ultimo_login
    FROM professores 
    WHERE nivel = 'professor' AND ativo = TRUE 
    AND data_cadastro >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY data_cadastro DESC 
    LIMIT 5
");
$professores_recentes = $stmt->fetchAll();

// Atividades recentes do sistema
$stmt = $pdo->query("
    SELECT la.*, p.nome as usuario_nome 
    FROM logs_atividades la 
    LEFT JOIN professores p ON la.professor_id = p.id 
    ORDER BY la.data_criacao DESC 
    LIMIT 10
");
$logs_recentes = $stmt->fetchAll();

// Professores sem login recente (últimos 30 dias)
$stmt = $pdo->query("
    SELECT nome, email, ultimo_login
    FROM professores 
    WHERE nivel = 'professor' 
    AND ativo = TRUE 
    AND (ultimo_login IS NULL OR ultimo_login < DATE_SUB(NOW(), INTERVAL 30 DAY))
    ORDER BY CASE WHEN ultimo_login IS NULL THEN 1 ELSE 0 END, ultimo_login DESC
    LIMIT 5
");
$professores_inativos = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-shield-alt me-2"></i> Dashboard Administrativo
        <span class="badge bg-danger ms-2">Admin</span>
    </h1>
    <div>
        <a href="gestao_usuarios.php" class="btn btn-danger btn-sm me-2">
            <i class="fas fa-users-cog me-1"></i> Gestão de Usuários
        </a>
        <a href="relatorios_admin.php" class="btn btn-primary btn-sm">
            <i class="fas fa-chart-bar me-1"></i> Relatórios
        </a>
    </div>
</div>

<!-- Cards Principais -->
<div class="row">
    <!-- Professores Ativos -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2" style="border-left: 4px solid var(--primary-color);">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Professores Ativos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_professores; ?></div>
                        <small class="text-muted">
                            <i class="fas fa-user-shield me-1"></i> 
                            <?php echo $total_admins; ?> admin(s)
                        </small>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chalkboard-teacher fa-2x text-gray-300 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alunos Totais -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2" style="border-left: 4px solid var(--success-color);">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Alunos Totais</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_alunos_sistema; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assinaturas Ativas -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2" style="border-left: 4px solid var(--info-color);">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Assinaturas Ativas</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $assinaturas_ativas; ?></div>
                        <small class="text-muted">
                            <?php 
                            $percent = $total_professores > 0 ? round(($assinaturas_ativas / $total_professores) * 100, 1) : 0;
                            echo $percent . '% dos professores';
                            ?>
                        </small>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-crown fa-2x text-gray-300 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Receita do Mês -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2" style="border-left: 4px solid var(--warning-color);">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col me-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Receita do Mês</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatMoney($receita_mes_sistema); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x text-gray-300 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alertas e Suporte -->
<div class="row">
    <!-- Professores Inativos -->
    <div class="col-xl-6 col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>Professores Inativos (30+ dias)
                </h6>
                <span class="badge bg-warning"><?php echo count($professores_inativos); ?></span>
            </div>
            <div class="card-body">
                <?php if (empty($professores_inativos)): ?>
                    <p class="text-muted text-center">Todos os professores estão ativos! ✅</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Professor</th>
                                    <th>Email</th>
                                    <th>Último Login</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($professores_inativos as $prof): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($prof['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($prof['email']); ?></td>
                                        <td>
                                            <?php echo $prof['ultimo_login'] ? formatDateTime($prof['ultimo_login']) : '<span class="text-muted">Nunca</span>'; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="contatarProfessor('<?php echo htmlspecialchars($prof['email']); ?>')">
                                                <i class="fas fa-envelope me-1"></i> Contatar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Professores Recentes -->
    <div class="col-xl-6 col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-user-plus me-2"></i>Novos Professores (7 dias)
                </h6>
                <span class="badge bg-primary"><?php echo count($professores_recentes); ?></span>
            </div>
            <div class="card-body">
                <?php if (empty($professores_recentes)): ?>
                    <p class="text-muted text-center">Nenhum professor novo nos últimos 7 dias.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Cadastro</th>
                                    <th>Último Login</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($professores_recentes as $prof): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($prof['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($prof['email']); ?></td>
                                        <td><?php echo formatDate($prof['data_cadastro']); ?></td>
                                        <td><?php echo $prof['ultimo_login'] ? formatDateTime($prof['ultimo_login']) : '<span class="text-muted">Nunca</span>'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Logs de Atividades -->
<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-secondary">
                    <i class="fas fa-history me-2"></i>Atividades Recentes do Sistema
                </h6>
                <a href="logs_atividades.php" class="btn btn-sm btn-outline-secondary">Ver Todos</a>
            </div>
            <div class="card-body">
                <?php if (empty($logs_recentes)): ?>
                    <p class="text-muted text-center">Nenhuma atividade recente.</p>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($logs_recentes as $log): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-<?php echo getLogLevelColor($log['acao']); ?>"></div>
                                <div class="timeline-content">
                                    <div class="small text-muted"><?php echo formatDateTime($log['data_criacao']); ?></div>
                                    <div class="fw-medium"><?php echo htmlspecialchars($log['usuario_nome'] ?? 'Sistema'); ?></div>
                                    <div class="small"><?php echo htmlspecialchars($log['descricao'] ?? $log['acao']); ?></div>
                                    <?php if ($log['tabela']): ?>
                                        <div class="small text-muted">
                                            <i class="fas fa-database me-1"></i>
                                            Tabela: <?php echo htmlspecialchars($log['tabela']); ?>
                                            <?php if ($log['registro_id']): ?>
                                                | ID: <?php echo $log['registro_id']; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function contatarProfessor(email) {
    window.location.href = 'mailto:' + email + '?subject=Contato Suporte Promestre&body=Olá, percebemos que você está um tempo sem acessar o sistema. Precisa de algum ajuda?';
}
</script>

<?php require_once 'includes/footer.php'; ?>
