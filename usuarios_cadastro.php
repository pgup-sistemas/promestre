<?php
require_once 'includes/config.php';
require_once 'includes/user_management.php';

// Verificar permissão
requireAdmin();

$page_title = 'Novo Usuário';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = clean($_POST['nome']);
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $email = clean($email);
    $telefone = clean($_POST['telefone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nivel = clean($_POST['nivel']); // admin ou professor

    if (empty($nome) || empty($email) || empty($password) || empty($confirm_password) || empty($nivel)) {
        $error = 'Preencha todos os campos obrigatórios.';
    } elseif ($password !== $confirm_password) {
        $error = 'As senhas não coincidem.';
    } else {
        // Verificar se email já existe
        $stmt = $pdo->prepare("SELECT id FROM professores WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = 'Este email já está cadastrado.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Gerar slug
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nome)));
            $slug .= '-' . substr(md5(time()), 0, 6);

            $stmt = $pdo->prepare("INSERT INTO professores (nome, email, senha, telefone, nivel, slug, ativo, data_cadastro) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
            
            try {
                $stmt->execute([$nome, $email, $hashed_password, $telefone, $nivel, $slug]);
                
                logActivity('CREATE_USER', 'professores', $pdo->lastInsertId(), "Usuário criado: $nome ($nivel)");
                
                setFlash('Usuário cadastrado com sucesso!', 'success');
                redirect('gestao_usuarios.php');
            } catch (PDOException $e) {
                $error = 'Erro ao cadastrar: ' . $e->getMessage();
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3"><i class="fas fa-user-plus me-2"></i> Novo Usuário</h1>
    <a href="gestao_usuarios.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Voltar
    </a>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger mb-4"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="row g-3">
            <div class="col-md-6">
                <label for="nome" class="form-label">Nome Completo *</label>
                <input type="text" class="form-control" id="nome" name="nome" required value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">
            </div>
            
            <div class="col-md-6">
                <label for="email" class="form-label">Email *</label>
                <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="col-md-6">
                <label for="telefone" class="form-label">Telefone (WhatsApp)</label>
                <input type="text" class="form-control" id="telefone" name="telefone" placeholder="(00) 00000-0000" value="<?php echo isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : ''; ?>">
            </div>

            <div class="col-md-6">
                <label for="nivel" class="form-label">Nível de Acesso *</label>
                <select class="form-select" id="nivel" name="nivel" required>
                    <option value="professor" <?php echo (isset($_POST['nivel']) && $_POST['nivel'] === 'professor') ? 'selected' : ''; ?>>Professor</option>
                    <option value="admin" <?php echo (isset($_POST['nivel']) && $_POST['nivel'] === 'admin') ? 'selected' : ''; ?>>Administrador</option>
                </select>
                <div class="form-text">Administradores têm acesso total ao sistema.</div>
            </div>

            <div class="col-md-6">
                <label for="password" class="form-label">Senha *</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <div class="col-md-6">
                <label for="confirm_password" class="form-label">Confirmar Senha *</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>

            <div class="col-12 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Cadastrar Usuário
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Máscara de telefone simples
    document.getElementById('telefone').addEventListener('input', function (e) {
        var x = e.target.value.replace(/\D/g, '').match(/(\d{0,2})(\d{0,5})(\d{0,4})/);
        e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
    });
</script>

<?php require_once 'includes/footer.php'; ?>
