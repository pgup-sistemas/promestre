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

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-body p-4">
                <h3 class="text-center mb-4">Redefinir Senha</h3>

                <?php if ($mensagem): ?>
                <div class="alert alert-<?= $tipo_mensagem == 'error' ? 'danger' : $tipo_mensagem ?> alert-dismissible fade show" role="alert">
                    <?= $mensagem ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Mantendo o toast para consistência se a função existir
                    if (typeof showToast === 'function') {
                        showToast(<?= json_encode(strip_tags($mensagem)) ?>, <?= json_encode($tipo_mensagem) ?>);
                    }
                });
                </script>
                <?php endif; ?>

                <?php if ($token_valido): ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="nova_senha" class="form-label">Nova Senha</label>
                            <input type="password" class="form-control" id="nova_senha" name="nova_senha" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmar_senha" class="form-label">Confirmar Senha</label>
                            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Salvar Nova Senha</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="d-grid">
                        <a href="index.php" class="btn btn-primary">Ir para Login</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
