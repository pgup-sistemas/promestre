<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

$mensagem = '';
$tipo_mensagem = '';
$token_valido = false;

if (isset($_GET['token'])) {
    $token = clean($_GET['token']);

    // Verifica token
    $stmt = $pdo->prepare("SELECT * FROM recuperacao_senha WHERE token = ? AND expiracao > NOW()");
    $stmt->execute([$token]);
    $recuperacao = $stmt->fetch();

    if ($recuperacao) {
        $token_valido = true;
    } else {
        $mensagem = "Link inválido ou expirado.";
        $tipo_mensagem = "danger";
    }
} else {
    $mensagem = "Token não fornecido.";
    $tipo_mensagem = "danger";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valido) {
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    if ($nova_senha === $confirmar_senha) {
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $email = $recuperacao['email'];

        // Atualiza senha
        $stmt = $pdo->prepare("UPDATE professores SET senha = ? WHERE email = ?");
        $stmt->execute([$senha_hash, $email]);

        // Remove token usado
        $stmt = $pdo->prepare("DELETE FROM recuperacao_senha WHERE token = ?");
        $stmt->execute([$token]);

        $mensagem = "Senha redefinida com sucesso! <a href='index.php'>Faça login agora</a>.";
        $tipo_mensagem = "success";
        $token_valido = false; // Esconde o formulário
    } else {
        $mensagem = "As senhas não coincidem.";
        $tipo_mensagem = "danger";
    }
}
?>

<div class="login-container">
    <div class="login-card" style="max-width: 450px; width: 100%;">
        <div class="login-header">
            <div class="login-icon">
                <i class="fas fa-key"></i>
            </div>
            <h1 class="login-title">Nova Senha</h1>
            <p class="login-subtitle">Crie uma nova senha segura</p>
        </div>
        
        <div class="login-body">
            <?php if ($mensagem): ?>
                <div class="alert alert-<?php echo $tipo_mensagem; ?> mb-4">
                    <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>

            <?php if ($token_valido): ?>
            <form method="POST" action="" class="login-form">
                <div class="form-group mb-3">
                    <label for="nova_senha" class="form-label">Nova Senha</label>
                    <input type="password" class="form-control" id="nova_senha" name="nova_senha" required>
                </div>
                <div class="form-group mb-3">
                    <label for="confirmar_senha" class="form-label">Confirmar Senha</label>
                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                </div>
                
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">Redefinir Senha</button>
                </div>
            </form>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="index.php" class="text-decoration-none text-muted">Voltar para Login</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
