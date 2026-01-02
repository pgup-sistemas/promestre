<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$professor_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$error = '';

if (!$id) {
    redirect('mensalidades.php');
}

// Buscar mensalidade
$stmt = $pdo->prepare("SELECT m.*, a.nome as aluno_nome FROM mensalidades m JOIN alunos a ON m.aluno_id = a.id WHERE m.id = ? AND m.professor_id = ?");
$stmt->execute([$id, $professor_id]);
$mensalidade = $stmt->fetch();

if (!$mensalidade) {
    setFlash('Cobrança não encontrada.', 'danger');
    redirect('mensalidades.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valor = $_POST['valor'];
    $data_vencimento = $_POST['data_vencimento'];
    $status = $_POST['status'];
    $observacoes = clean($_POST['observacoes']);
    
    // Se mudar para pago, pode definir data pagamento se não tiver
    $data_pagamento = $mensalidade['data_pagamento'];
    if ($status == 'pago' && empty($data_pagamento)) {
        $data_pagamento = date('Y-m-d');
    } elseif ($status != 'pago') {
        $data_pagamento = null;
    }

    try {
        $stmt = $pdo->prepare("UPDATE mensalidades SET valor = ?, data_vencimento = ?, status = ?, data_pagamento = ?, observacoes = ? WHERE id = ?");
        $stmt->execute([$valor, $data_vencimento, $status, $data_pagamento, $observacoes, $id]);
        
        setFlash('Cobrança atualizada com sucesso!', 'success');
        redirect('mensalidades.php');
    } catch (PDOException $e) {
        $error = 'Erro ao atualizar: ' . $e->getMessage();
    }
}

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-edit me-2"></i> Editar Cobrança</h1>
    <a href="mensalidades.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Voltar</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Dados da Cobrança</h6>
            </div>
            <div class="card-body p-4">
                <h5 class="card-title mb-4">Aluno: <?php echo htmlspecialchars($mensalidade['aluno_nome']); ?></h5>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="valor" class="form-label">Valor (R$)</label>
                        <input type="number" step="0.01" class="form-control" id="valor" name="valor" value="<?php echo $mensalidade['valor']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="data_vencimento" class="form-label">Data Vencimento</label>
                        <input type="date" class="form-control" id="data_vencimento" name="data_vencimento" value="<?php echo $mensalidade['data_vencimento']; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="pendente" <?php echo $mensalidade['status'] == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                            <option value="pago" <?php echo $mensalidade['status'] == 'pago' ? 'selected' : ''; ?>>Pago</option>
                            <option value="atrasado" <?php echo $mensalidade['status'] == 'atrasado' ? 'selected' : ''; ?>>Atrasado</option>
                            <option value="cancelado" <?php echo $mensalidade['status'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?php echo htmlspecialchars($mensalidade['observacoes']); ?></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="mensalidades.php" class="btn btn-light me-md-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
