<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Minhas Turmas';
$professor_id = $_SESSION['user_id'];

// Filtros
$busca = isset($_GET['busca']) ? clean($_GET['busca']) : '';
$status = isset($_GET['status']) ? clean($_GET['status']) : 'ativo';

// Query
$sql = "SELECT t.*, 
        (SELECT COUNT(*) FROM turma_alunos ta WHERE ta.turma_id = t.id AND ta.ativo = 1) as qtd_alunos 
        FROM turmas t 
        WHERE t.professor_id = ?";
$params = [$professor_id];

if ($status && $status != 'todos') {
    $sql .= " AND t.ativo = ?";
    $params[] = ($status == 'ativo' ? 1 : 0);
}

if ($busca) {
    $sql .= " AND (t.nome LIKE ? OR t.descricao LIKE ?)";
    $busca_like = "%$busca%";
    $params[] = $busca_like;
    $params[] = $busca_like;
}

$sql .= " ORDER BY t.nome ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$turmas = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
    <h1 class="h4 mb-0"><i class="fas fa-users-class me-2"></i> Minhas Turmas</h1>
    <div class="d-flex flex-wrap gap-2">
        <a href="turmas_cadastro.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Nova Turma</a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
                <input type="text" class="form-control" name="busca" placeholder="Buscar por nome da turma..." value="<?php echo htmlspecialchars($busca); ?>">
            </div>
            <div class="col-12 col-md-4">
                <select class="form-select" name="status">
                    <option value="todos" <?php echo $status == 'todos' ? 'selected' : ''; ?>>Todos os Status</option>
                    <option value="ativo" <?php echo $status == 'ativo' ? 'selected' : ''; ?>>Ativas</option>
                    <option value="inativo" <?php echo $status == 'inativo' ? 'selected' : ''; ?>>Inativas</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-secondary btn-sm w-100">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Turma</th>
                        <th>Alunos</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($turmas) > 0): ?>
                        <?php foreach ($turmas as $turma): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle me-2" style="width: 12px; height: 12px; background-color: <?php echo htmlspecialchars($turma['cor']); ?>;"></div>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($turma['nome']); ?></div>
                                            <?php if ($turma['descricao']): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($turma['descricao'], 0, 50)) . (strlen($turma['descricao']) > 50 ? '...' : ''); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="fas fa-user-graduate me-1"></i> <?php echo $turma['qtd_alunos']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($turma['ativo']): ?>
                                        <span class="badge bg-success">Ativa</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a href="turmas_alunos.php?id=<?php echo $turma['id']; ?>" class="btn btn-sm btn-outline-info" title="Gerenciar Alunos">
                                            <i class="fas fa-users"></i>
                                        </a>
                                        <a href="turmas_cadastro.php?id=<?php echo $turma['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="turmas_excluir.php?id=<?php echo $turma['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza que deseja excluir esta turma?');" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-3 d-block"></i>
                                Nenhuma turma encontrada.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
