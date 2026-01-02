<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$professor_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$page_title = $id ? 'Editar Tipo de Aula' : 'Novo Tipo de Aula';
$error = '';

$tipo = [
    'nome' => '',
    'preco_padrao' => '',
    'descricao' => '',
    'cor' => '#0d6efd',
    'ativo' => 1
];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM tipos_aula WHERE id = ? AND professor_id = ?");
    $stmt->execute([$id, $professor_id]);
    $tipo_db = $stmt->fetch();
    
    if ($tipo_db) {
        $tipo = $tipo_db;
    } else {
        setFlash('Tipo de aula não encontrado.', 'danger');
        redirect('tipos_aula.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = clean($_POST['nome']);
    // Converter preço de formato BR (1.000,00) para US (1000.00) se necessário, ou assumir input type number
    $preco_padrao = $_POST['preco_padrao']; 
    $descricao = clean($_POST['descricao']);
    $cor = clean($_POST['cor']);
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if (empty($nome) || empty($preco_padrao)) {
        $error = 'Nome e Preço são obrigatórios.';
    } else {
        try {
            if ($id) {
                // Atualizar
                $stmt = $pdo->prepare("UPDATE tipos_aula SET nome = ?, preco_padrao = ?, descricao = ?, cor = ?, ativo = ? WHERE id = ? AND professor_id = ?");
                $stmt->execute([$nome, $preco_padrao, $descricao, $cor, $ativo, $id, $professor_id]);
                $msg = 'Tipo de aula atualizado com sucesso!';
            } else {
                // Inserir
                $stmt = $pdo->prepare("INSERT INTO tipos_aula (professor_id, nome, preco_padrao, descricao, cor, ativo) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$professor_id, $nome, $preco_padrao, $descricao, $cor, $ativo]);
                $msg = 'Tipo de aula cadastrado com sucesso!';
            }
            
            setFlash($msg, 'success');
            redirect('tipos_aula.php');
            
        } catch (PDOException $e) {
            $error = 'Erro ao salvar: ' . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-chalkboard me-2"></i> <?php echo $page_title; ?></h1>
    <a href="tipos_aula.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Voltar</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Informações da Aula</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome da Aula *</label>
                        <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($tipo['nome']); ?>" placeholder="Ex: Piano Clássico, Inglês Kids" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="preco_padrao" class="form-label">Preço Mensal Padrão (R$) *</label>
                        <input type="number" step="0.01" class="form-control" id="preco_padrao" name="preco_padrao" value="<?php echo $tipo['preco_padrao']; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo htmlspecialchars($tipo['descricao']); ?></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="cor" class="form-label">Cor de Identificação</label>
                            <input type="color" class="form-control form-control-color w-100" id="cor" name="cor" value="<?php echo $tipo['cor']; ?>">
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" <?php echo $tipo['ativo'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ativo">Ativo</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="tipos_aula.php" class="btn btn-light me-md-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
