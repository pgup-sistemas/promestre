<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email']);

    // Verifica se o email existe
    $stmt = $pdo->prepare("SELECT id, nome FROM professores WHERE email = ?");
    $stmt->execute([$email]);
    $professor = $stmt->fetch();

    if ($professor) {
        $token = bin2hex(random_bytes(32));
        
        // Salva o token usando o horário do banco de dados para evitar conflitos de fuso horário
        $stmt = $pdo->prepare("INSERT INTO recuperacao_senha (email, token, expiracao) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
        $stmt->execute([$email, $token]);

        $link = SITE_URL . "/redefinir_senha.php?token=" . $token;
        $conteudo_email = "Olá " . $professor['nome'] . ",<br><br>Para redefinir sua senha, clique no link abaixo:<br><a href=\"" . $link . "\">Redefinir Senha</a><br><br>Se você não solicitou isso, ignore este email.";
        sendMail($email, "Recuperação de Senha", $conteudo_email);

        $mensagem = "Um link de recuperação foi enviado para seu email.";
        $tipo_mensagem = "success";
    } else {
        // Por segurança, não informamos se o email existe ou não, mas para UX neste MVP vamos informar
        $mensagem = "Email não encontrado.";
        $tipo_mensagem = "danger";
    }
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-body p-4">
                <h3 class="text-center mb-4">Recuperar Senha</h3>

                <?php if ($mensagem): ?>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    showToast(<?= json_encode($mensagem) ?>, <?= json_encode($tipo_mensagem) ?>);
                });
                </script>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Enviar Link</button>
                        <a href="index.php" class="btn btn-outline-secondary">Voltar para Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
