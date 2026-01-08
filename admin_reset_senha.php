<?php
require_once 'includes/config.php';
require_once 'includes/user_management.php';

$page_title = 'Redefinição de Senha';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $token = $_POST['token'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'Preencha todos os campos.';
    } elseif ($password !== $confirm_password) {
        $error = 'As senhas não coincidem.';
    } elseif (strlen($password) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } else {
        // Validar token
        $token_data = validateResetToken($token);
        
        if (!$token_data) {
            $error = 'Token inválido ou expirado.';
        } else {
            // Atualizar senha
            if (updateUserPassword($token_data['professor_id'], $password)) {
                // Marcar token como usado
                markTokenAsUsed($token);
                
                // Registrar log
                logActivity('PASSWORD_RESET', 'professores', $token_data['professor_id'], 
                    "Senha redefinida via link de admin");
                
                $success = 'Senha redefinida com sucesso! <a href="index.php">Faça login agora</a>.';
                
                // Enviar notificação por email (opcional)
                // enviarNotificacaoResetSenha($token_data['email'], $token_data['nome']);
            } else {
                $error = 'Erro ao redefinir senha. Tente novamente.';
            }
        }
    }
} else {
    // Validar token na carga da página
    $token_data = validateResetToken($token);
    if (!$token_data) {
        $error = 'Token inválido ou expirado. Solicite um novo link de redefinição.';
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <div class="avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-key fa-2x"></i>
                    </div>
                    <h2 class="h4">Redefinir Senha</h2>
                    <p class="text-muted">Digite sua nova senha abaixo</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                    </div>
                <?php else: ?>
                    <?php if (isset($token_data)): ?>
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            Redefinindo senha para: <strong><?php echo htmlspecialchars($token_data['nome']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($token_data['email']); ?></small>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Nova Senha</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Digite sua nova senha" required minlength="6">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Mínimo 6 caracteres</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Senha</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="Confirme sua nova senha" required minlength="6">
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showPasswordStrength">
                                <label class="form-check-label" for="showPasswordStrength">
                                    Mostrar força da senha
                                </label>
                            </div>
                            <div class="progress mt-2" id="passwordStrength" style="height: 5px; display: none;">
                                <div class="progress-bar" role="progressbar"></div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Redefinir Senha
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <a href="index.php" class="text-muted">
                        <i class="fas fa-arrow-left me-1"></i> Voltar para o login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.getElementById('togglePassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const showPasswordStrength = document.getElementById('showPasswordStrength');
    const passwordStrength = document.getElementById('passwordStrength');
    const strengthBar = passwordStrength.querySelector('.progress-bar');

    // Toggle password visibility
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });

    toggleConfirmPassword.addEventListener('click', function() {
        const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPasswordInput.setAttribute('type', type);
        this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });

    // Password strength checker
    showPasswordStrength.addEventListener('change', function() {
        passwordStrength.style.display = this.checked ? 'block' : 'none';
    });

    passwordInput.addEventListener('input', function() {
        if (!showPasswordStrength.checked) return;

        const password = this.value;
        let strength = 0;

        // Length check
        if (password.length >= 6) strength += 25;
        if (password.length >= 10) strength += 25;

        // Character variety
        if (/[a-z]/.test(password)) strength += 12.5;
        if (/[A-Z]/.test(password)) strength += 12.5;
        if (/[0-9]/.test(password)) strength += 12.5;
        if (/[^a-zA-Z0-9]/.test(password)) strength += 12.5;

        // Update progress bar
        strengthBar.style.width = strength + '%';
        strengthBar.className = 'progress-bar';

        if (strength < 30) {
            strengthBar.classList.add('bg-danger');
        } else if (strength < 60) {
            strengthBar.classList.add('bg-warning');
        } else if (strength < 80) {
            strengthBar.classList.add('bg-info');
        } else {
            strengthBar.classList.add('bg-success');
        }
    });

    // Password confirmation validation
    confirmPasswordInput.addEventListener('input', function() {
        if (this.value !== passwordInput.value) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
