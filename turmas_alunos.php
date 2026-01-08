<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$professor_id = $_SESSION['user_id'];
$turma_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$turma_id) {
    redirect('turmas.php');
}

// Buscar dados da turma
$stmt = $pdo->prepare("SELECT * FROM turmas WHERE id = ? AND professor_id = ?");
$stmt->execute([$turma_id, $professor_id]);
$turma = $stmt->fetch();

if (!$turma) {
    setFlash('Turma não encontrada.', 'danger');
    redirect('turmas.php');
}

// Processar Adição de Aluno
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_aluno'])) {
    $aluno_id = (int)$_POST['aluno_id'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO turma_alunos (turma_id, aluno_id, data_entrada) VALUES (?, ?, CURDATE())");
        $stmt->execute([$turma_id, $aluno_id]);
        setFlash('Aluno adicionado à turma!', 'success');
    } catch (PDOException $e) {
        setFlash('Erro ao adicionar aluno: ' . $e->getMessage(), 'danger');
    }
    redirect("turmas_alunos.php?id=$turma_id");
}

// Processar Remoção de Aluno
if (isset($_GET['remove'])) {
    $aluno_id = (int)$_GET['remove'];
    try {
        $stmt = $pdo->prepare("DELETE FROM turma_alunos WHERE turma_id = ? AND aluno_id = ?");
        $stmt->execute([$turma_id, $aluno_id]);
        setFlash('Aluno removido da turma.', 'success');
    } catch (PDOException $e) {
        setFlash('Erro ao remover aluno.', 'danger');
    }
    redirect("turmas_alunos.php?id=$turma_id");
}

// Buscar alunos MATRICULADOS nesta turma
$stmt = $pdo->prepare("
    SELECT a.*, ta.data_entrada 
    FROM alunos a 
    INNER JOIN turma_alunos ta ON a.id = ta.aluno_id 
    WHERE ta.turma_id = ? 
    ORDER BY a.nome
");
$stmt->execute([$turma_id]);
$alunos_matriculados = $stmt->fetchAll();

// Buscar alunos DISPONÍVEIS (do professor, que não estão nesta turma)
$stmt = $pdo->prepare("
    SELECT id, nome 
    FROM alunos 
    WHERE professor_id = ? 
    AND status = 'ativo'
    AND id NOT IN (SELECT aluno_id FROM turma_alunos WHERE turma_id = ?)
    ORDER BY nome
");
$stmt->execute([$professor_id, $turma_id]);
$alunos_disponiveis = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">Alunos da Turma</h1>
        <p class="mb-0 text-muted">Gerenciando: <strong><?php echo htmlspecialchars($turma['nome']); ?></strong></p>
    </div>
    <a href="turmas.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Voltar</a>
</div>

<div class="row">
    <!-- Coluna da Esquerda: Lista de Matriculados -->
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Alunos Matriculados (<?php echo count($alunos_matriculados); ?>)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Nome</th>
                                <th>Contato</th>
                                <th>Data Entrada</th>
                                <th class="text-end pe-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($alunos_matriculados) > 0): ?>
                                <?php foreach ($alunos_matriculados as $aluno): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold"><?php echo htmlspecialchars($aluno['nome']); ?></td>
                                        <td>
                                            <div class="small">
                                                <i class="fab fa-whatsapp text-success me-1"></i> <?php echo htmlspecialchars($aluno['whatsapp']); ?><br>
                                                <i class="fas fa-envelope text-muted me-1"></i> <?php echo htmlspecialchars($aluno['email']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($aluno['data_entrada'])); ?></td>
                                        <td class="text-end pe-4">
                                            <a href="?id=<?php echo $turma_id; ?>&remove=<?php echo $aluno['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Remover este aluno da turma?');"
                                               title="Remover da turma">
                                                <i class="fas fa-user-minus"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        Esta turma ainda não possui alunos. Adicione ao lado.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Coluna da Direita: Adicionar Aluno -->
    <div class="col-lg-4">
        <div class="card shadow mb-4 border-left-success">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-user-plus me-2"></i> Adicionar Aluno</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="aluno_id" class="form-label">Selecione o Aluno</label>
                        <select class="form-select" name="aluno_id" id="aluno_id" required>
                            <option value="">-- Escolha um aluno --</option>
                            <?php foreach ($alunos_disponiveis as $disponivel): ?>
                                <option value="<?php echo $disponivel['id']; ?>">
                                    <?php echo htmlspecialchars($disponivel['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (count($alunos_disponiveis) == 0): ?>
                            <div class="form-text text-muted mt-2">Todos os seus alunos ativos já estão nesta turma.</div>
                        <?php endif; ?>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="add_aluno" class="btn btn-success" <?php echo count($alunos_disponiveis) == 0 ? 'disabled' : ''; ?>>
                            Adicionar à Turma
                        </button>
                    </div>
                </form>
                <hr>
                <div class="text-center">
                    <a href="alunos_cadastro.php" class="small"><i class="fas fa-plus-circle"></i> Cadastrar novo aluno no sistema</a>
                </div>
            </div>
        </div>
        
        <div class="card shadow mb-4">
            <div class="card-body">
                <h6 class="font-weight-bold">Sobre Turmas</h6>
                <p class="small text-muted mb-0">
                    Alunos adicionados aqui serão automaticamente incluídos na lista de chamada quando você agendar uma aula para esta turma.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
