<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$professor_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$page_title = $id ? 'Editar Aluno' : 'Novo Aluno';
$error = '';

// Buscar tipos de aula para o select
$stmt = $pdo->prepare("SELECT * FROM tipos_aula WHERE professor_id = ? AND ativo = 1 ORDER BY nome");
$stmt->execute([$professor_id]);
$tipos_aula = $stmt->fetchAll();

$aluno = [
    'nome' => '',
    'email' => '',
    'telefone' => '',
    'whatsapp' => '',
    'cpf' => '',
    'data_nascimento' => '',
    'endereco' => '',
    'tipo_aula_id' => '',
    'status' => 'ativo',
    'observacoes' => ''
];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM alunos WHERE id = ? AND professor_id = ?");
    $stmt->execute([$id, $professor_id]);
    $aluno_db = $stmt->fetch();
    
    if ($aluno_db) {
        $aluno = $aluno_db;
    } else {
        setFlash('Aluno não encontrado.', 'danger');
        redirect('alunos.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = clean($_POST['nome']);
    $email = clean($_POST['email']);
    $telefone = clean($_POST['telefone']);
    $whatsapp = clean($_POST['whatsapp']);
    $cpf = clean($_POST['cpf']);
    $data_nascimento = !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null;
    $endereco = clean($_POST['endereco']);
    $tipo_aula_id = !empty($_POST['tipo_aula_id']) ? $_POST['tipo_aula_id'] : null;
    $status = $_POST['status'];
    $observacoes = clean($_POST['observacoes']);

    if (empty($nome) || empty($telefone) || empty($whatsapp)) {
        $error = 'Nome, Telefone e WhatsApp são obrigatórios.';
    } else {
        try {
            if ($id) {
                // Atualizar
                $stmt = $pdo->prepare("UPDATE alunos SET nome = ?, email = ?, telefone = ?, whatsapp = ?, cpf = ?, data_nascimento = ?, endereco = ?, tipo_aula_id = ?, status = ?, observacoes = ? WHERE id = ? AND professor_id = ?");
                $stmt->execute([$nome, $email, $telefone, $whatsapp, $cpf, $data_nascimento, $endereco, $tipo_aula_id, $status, $observacoes, $id, $professor_id]);
                $msg = 'Aluno atualizado com sucesso!';
            } else {
                // Inserir
                $stmt = $pdo->prepare("INSERT INTO alunos (professor_id, nome, email, telefone, whatsapp, cpf, data_nascimento, endereco, tipo_aula_id, status, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$professor_id, $nome, $email, $telefone, $whatsapp, $cpf, $data_nascimento, $endereco, $tipo_aula_id, $status, $observacoes]);
                $msg = 'Aluno cadastrado com sucesso!';
            }
            
            setFlash($msg, 'success');
            redirect('alunos.php');
            
        } catch (PDOException $e) {
            $error = 'Erro ao salvar: ' . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-user-plus me-2"></i> <?php echo $page_title; ?></h1>
    <a href="alunos.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Voltar</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Dados do Aluno</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="">
                    <h5 class="mb-3 text-primary">Dados Pessoais</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label for="nome" class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($aluno['nome']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($aluno['email']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                            <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" value="<?php echo $aluno['data_nascimento']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="telefone" class="form-label">Telefone *</label>
                            <input type="text" class="form-control" id="telefone" name="telefone" value="<?php echo htmlspecialchars($aluno['telefone']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="whatsapp" class="form-label">WhatsApp *</label>
                            <input type="text" class="form-control" id="whatsapp" name="whatsapp" value="<?php echo htmlspecialchars($aluno['whatsapp']); ?>" required>
                            <div class="form-text">Ex: 69999999999 (somente números com DDD)</div>
                        </div>
                        <div class="col-md-6">
                            <label for="cpf" class="form-label">CPF</label>
                            <input type="text" class="form-control" id="cpf" name="cpf" value="<?php echo htmlspecialchars($aluno['cpf']); ?>">
                        </div>
                        <div class="col-md-12">
                            <label for="endereco" class="form-label">Endereço Completo</label>
                            <input type="text" class="form-control" id="endereco" name="endereco" value="<?php echo htmlspecialchars($aluno['endereco']); ?>">
                        </div>
                    </div>

                    <h5 class="mb-3 text-primary">Dados da Aula</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="tipo_aula_id" class="form-label">Tipo de Aula</label>
                            <select class="form-select" id="tipo_aula_id" name="tipo_aula_id">
                                <option value="">Selecione...</option>
                                <?php foreach ($tipos_aula as $tipo): ?>
                                    <option value="<?php echo $tipo['id']; ?>" <?php echo $aluno['tipo_aula_id'] == $tipo['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tipo['nome']); ?> (R$ <?php echo number_format($tipo['preco_padrao'], 2, ',', '.'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($tipos_aula)): ?>
                                <div class="form-text text-danger">Cadastre um tipo de aula antes.</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="ativo" <?php echo $aluno['status'] == 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                <option value="inativo" <?php echo $aluno['status'] == 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?php echo htmlspecialchars($aluno['observacoes']); ?></textarea>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="alunos.php" class="btn btn-light me-md-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4">Salvar Aluno</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const maskPhone = (v) => {
        v = v.replace(/\D/g, "");
        v = v.replace(/^(\d{2})(\d)/g, "($1) $2");
        v = v.replace(/(\d)(\d{4})$/, "$1-$2");
        return v;
    };
    
    const maskCPF = (v) => {
        v = v.replace(/\D/g, "");
        v = v.replace(/(\d{3})(\d)/, "$1.$2");
        v = v.replace(/(\d{3})(\d)/, "$1.$2");
        v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
        return v;
    };

    ['telefone', 'whatsapp'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', e => e.target.value = maskPhone(e.target.value));
    });

    const cpf = document.getElementById('cpf');
    if (cpf) cpf.addEventListener('input', e => e.target.value = maskCPF(e.target.value));
});
</script>

<?php require_once 'includes/footer.php'; ?>
