<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Acesso Restrito';
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card shadow-sm">
            <div class="card-body p-5 text-center">
                <div class="mb-3">
                    <i class="fas fa-lock fa-3x text-muted"></i>
                </div>
                <h3 class="mb-2">Recurso disponível no plano</h3>
                <p class="text-muted mb-4">Para usar este recurso, ative sua assinatura do sistema.</p>
                <a href="assinatura_sistema.php" class="btn btn-primary">
                    <i class="fas fa-crown me-2"></i> Assinar / Reativar
                </a>
                <a href="dashboard.php" class="btn btn-outline-secondary ms-2">Voltar</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
