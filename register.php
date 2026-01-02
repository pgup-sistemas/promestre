<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$page_title = 'Cadastro';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = clean($_POST['nome']);
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $telefone = clean($_POST['telefone']);

    if (empty($nome) || empty($email) || empty($password) || empty($confirm_password)) {
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
            $slug .= '-' . substr(md5(time()), 0, 6); // Garantir unicidade

            $stmt = $pdo->prepare("INSERT INTO professores (nome, email, senha, telefone, slug) VALUES (?, ?, ?, ?, ?)");
            
            try {
                $stmt->execute([$nome, $email, $hashed_password, $telefone, $slug]);
                setFlash('Cadastro realizado com sucesso! Faça login.', 'success');
                redirect('index.php');
            } catch (PDOException $e) {
                $error = 'Erro ao cadastrar: ' . $e->getMessage();
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h1 class="h3 mb-3 fw-normal">Crie sua conta</h1>
                    <p class="text-muted">Comece a gerenciar suas aulas hoje</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome Completo</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="telefone" class="form-label">Telefone (WhatsApp)</label>
                        <input type="text" class="form-control" id="telefone" name="telefone" placeholder="(00) 00000-0000">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmar Senha</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Cadastrar</button>
                    </div>
                </form>
                
                <div class="mt-3 text-center">
                    <p>Já tem uma conta? <a href="index.php">Faça Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
