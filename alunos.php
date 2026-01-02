<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Meus Alunos';
$professor_id = $_SESSION['user_id'];

// Filtros
$busca = isset($_GET['busca']) ? clean($_GET['busca']) : '';
$status = isset($_GET['status']) ? clean($_GET['status']) : 'ativo';

// Query
$sql = "SELECT a.*, t.nome as tipo_aula_nome, t.cor as tipo_aula_cor 
        FROM alunos a 
        LEFT JOIN tipos_aula t ON a.tipo_aula_id = t.id 
        WHERE a.professor_id = ? AND a.deleted_at IS NULL";
$params = [$professor_id];

if ($status && $status != 'todos') {
    $sql .= " AND a.status = ?";
    $params[] = $status;
}

if ($busca) {
    $sql .= " AND (a.nome LIKE ? OR a.email LIKE ? OR a.telefone LIKE ?)";
    $busca_like = "%$busca%";
    $params[] = $busca_like;
    $params[] = $busca_like;
    $params[] = $busca_like;
}

$sql .= " ORDER BY a.nome ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$alunos = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
    <h1 class="h4 mb-0"><i class="fas fa-users me-2"></i> Meus Alunos</h1>
    <div class="d-flex flex-wrap gap-2">
        <a href="alunos_cadastro.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Novo Aluno</a>
    </div>
</div>

<?php
// Contar pré-matrículas pendentes
$stmt_pre = $pdo->prepare("SELECT COUNT(*) FROM alunos WHERE professor_id = ? AND status = 'inativo' AND observacoes LIKE '%Pré-matrícula realizada pelo site%'");
$stmt_pre->execute([$professor_id]);
$pre_matriculas_count = $stmt_pre->fetchColumn();
?>

<?php if ($pre_matriculas_count > 0 && $status != 'inativo' && $status != 'todos'): ?>
<div class="alert alert-info d-flex align-items-center mb-4" role="alert">
    <i class="fas fa-info-circle fa-lg me-3"></i>
    <div>
        Você tem <strong><?php echo $pre_matriculas_count; ?></strong> nova(s) pré-matrícula(s) pendente(s). 
        <a href="?status=inativo" class="alert-link">Clique aqui para visualizar</a>.
    </div>
</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
                <input type="text" class="form-control" name="busca" placeholder="Buscar por nome, email ou telefone..." value="<?php echo $busca; ?>">
            </div>
            <div class="col-12 col-md-4">
                <select class="form-select" name="status">
                    <option value="todos" <?php echo $status == 'todos' ? 'selected' : ''; ?>>Todos os Status</option>
                    <option value="ativo" <?php echo $status == 'ativo' ? 'selected' : ''; ?>>Ativos</option>
                    <option value="inativo" <?php echo $status == 'inativo' ? 'selected' : ''; ?>>Inativos</option>
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
                        <th class="ps-4">Nome</th>
                        <th class="d-none d-md-table-cell">Contato</th>
                        <th class="d-none d-lg-table-cell">Tipo de Aula</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($alunos) > 0): ?>
                        <?php foreach ($alunos as $aluno): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">
                                        <?php echo htmlspecialchars($aluno['nome']); ?>
                                        <?php if (strpos($aluno['observacoes'], 'Pré-matrícula realizada pelo site') !== false && $aluno['status'] == 'inativo'): ?>
                                            <span class="badge bg-info text-dark ms-2" style="font-size: 0.7rem;">NOVA PRÉ-MATRÍCULA</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($aluno['email']): ?>
                                        <div class="small text-muted"><?php echo htmlspecialchars($aluno['email']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($aluno['whatsapp'])): ?>
                                        <div class="small text-muted d-md-none"><i class="fab fa-whatsapp text-success me-1"></i><?php echo htmlspecialchars($aluno['whatsapp']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($aluno['tipo_aula_nome'])): ?>
                                        <div class="d-lg-none mt-1">
                                            <span class="badge" style="background-color: <?php echo $aluno['tipo_aula_cor']; ?>">
                                                <?php echo htmlspecialchars($aluno['tipo_aula_nome']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <div><i class="fab fa-whatsapp text-success me-1"></i> <?php echo htmlspecialchars($aluno['whatsapp']); ?></div>
                                    <?php if ($aluno['telefone'] && $aluno['telefone'] != $aluno['whatsapp']): ?>
                                        <div class="small text-muted"><?php echo htmlspecialchars($aluno['telefone']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <?php if ($aluno['tipo_aula_nome']): ?>
                                        <span class="badge" style="background-color: <?php echo $aluno['tipo_aula_cor']; ?>">
                                            <?php echo htmlspecialchars($aluno['tipo_aula_nome']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($aluno['status'] == 'ativo'): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <?php 
                                            // Formatar telefone para link whatsapp (remover caracteres não numéricos)
                                            $whatsapp_num = preg_replace('/[^0-9]/', '', $aluno['whatsapp']);
                                            // Se não tiver código do país, adiciona 55
                                            if (strlen($whatsapp_num) <= 11) {
                                                $whatsapp_num = '55' . $whatsapp_num;
                                            }
                                        ?>
                                        <a href="https://wa.me/<?php echo $whatsapp_num; ?>" target="_blank" class="btn btn-sm btn-outline-success" title="Enviar WhatsApp">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                        <a href="alunos_detalhes.php?id=<?php echo $aluno['id']; ?>" class="btn btn-sm btn-outline-info" title="Ver Detalhes">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="alunos_cadastro.php?id=<?php echo $aluno['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="contrato_gerar.php?id=<?php echo $aluno['id']; ?>" class="btn btn-sm btn-outline-dark" title="Gerar Contrato" target="_blank">
                                                <i class="fas fa-file-contract"></i>
                                            </a>
                                            <a href="alunos_excluir.php?id=<?php echo $aluno['id']; ?>" class="btn btn-sm btn-outline-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este aluno?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <p class="text-muted mb-0">Nenhum aluno encontrado.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
