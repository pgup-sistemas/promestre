<?php
require_once 'includes/config.php';
require_once 'includes/user_management.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$page_title = 'Login';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $email = clean($email);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Preencha todos os campos.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM professores WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Email não encontrado. Verifique o email ou faça seu cadastro.';
        } elseif (password_verify($password, $user['senha'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nome'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_slug'] = $user['slug'];
            $_SESSION['user_nivel'] = $user['nivel'] ?? 'professor';
            
            // Atualizar último login
            updateLastLogin($user['id']);
            
            // Registrar log
            logActivity('LOGIN', null, null, "Login do usuário {$user['nome']}");
            
            redirect('dashboard.php');
        } else {
            $error = 'Senha incorreta. Verifique a senha ou use "Esqueci minha senha".';
        }
    }
}

require_once 'includes/header.php';
?>

<div class="login-container">
    <div class="login-card" style="max-width: 450px; width: 100%;">
        <div class="login-header">
            <div class="login-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1 class="login-title">Bem-vindo ao Promestre</h1>
            <p class="login-subtitle">Faça login para continuar</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger mb-4"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">Senha</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Entrar</button>
                </div>
            </form>
            
            <div class="text-center mt-4">
                <a href="esqueci_senha.php" class="text-decoration-none text-muted small">Esqueci minha senha</a>
                <span class="mx-2 text-muted">•</span>
                <a href="register.php" class="text-decoration-none fw-bold text-success">Cadastre-se</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
