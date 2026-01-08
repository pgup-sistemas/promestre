<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$professor_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$page_title = $id ? 'Editar Turma' : 'Nova Turma';
$error = '';

$turma = [
    'nome' => '',
    'cor' => '#4e73df',
    'descricao' => '',
    'ativo' => 1
];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM turmas WHERE id = ? AND professor_id = ?");
    $stmt->execute([$id, $professor_id]);
    $turma_db = $stmt->fetch();
    
    if ($turma_db) {
        $turma = $turma_db;
    } else {
        setFlash('Turma não encontrada.', 'danger');
        redirect('turmas.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = clean($_POST['nome']);
    $cor = clean($_POST['cor']);
    $descricao = clean($_POST['descricao']);
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if (empty($nome)) {
        $error = 'O nome da turma é obrigatório.';
    } else {
        try {
            if ($id) {
                // Atualizar
                $stmt = $pdo->prepare("UPDATE turmas SET nome = ?, cor = ?, descricao = ?, ativo = ? WHERE id = ? AND professor_id = ?");
                $stmt->execute([$nome, $cor, $descricao, $ativo, $id, $professor_id]);
                $msg = 'Turma atualizada com sucesso!';
            } else {
                // Inserir
                $stmt = $pdo->prepare("INSERT INTO turmas (professor_id, nome, cor, descricao, ativo) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$professor_id, $nome, $cor, $descricao, $ativo]);
                $msg = 'Turma criada com sucesso!';
            }
            
            setFlash($msg, 'success');
            redirect('turmas.php');
            
        } catch (PDOException $e) {
            $error = 'Erro ao salvar: ' . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-users-class me-2"></i> <?php echo $page_title; ?></h1>
    <a href="turmas.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Voltar</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Dados da Turma</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome da Turma *</label>
                        <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($turma['nome']); ?>" placeholder="Ex: Teoria Musical Iniciante" required>
                    </div>

                    <div class="mb-3">
                        <label for="cor" class="form-label">Cor de Identificação</label>
                        <input type="color" class="form-control form-control-color" id="cor" name="cor" value="<?php echo htmlspecialchars($turma['cor']); ?>" title="Escolha uma cor">
                    </div>

                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo htmlspecialchars($turma['descricao']); ?></textarea>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="ativo" name="ativo" <?php echo $turma['ativo'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="ativo">Turma Ativa</label>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i> Salvar Turma</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
