<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$professor_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM templates_mensagem WHERE id = ? AND professor_id = ?");
$stmt->execute([$id, $professor_id]);
$template = $stmt->fetch();

if (!$template) {
    setFlash('Template não encontrado.', 'danger');
    redirect('templates_mensagem.php');
}

$page_title = 'Editar Template';

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-edit me-2"></i> Editar Template</h1>
    <a href="templates_mensagem.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i> Voltar
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="templates_mensagem_salvar.php">
            <input type="hidden" name="id" value="<?php echo $template['id']; ?>">
            
            <div class="mb-3">
                <label class="form-label">Nome do Template</label>
                <input type="text" class="form-control" name="nome" value="<?php echo htmlspecialchars($template['nome']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Tipo</label>
                <select class="form-select" name="tipo" required>
                    <option value="cobranca" <?php echo $template['tipo'] == 'cobranca' ? 'selected' : ''; ?>>Cobrança</option>
                    <option value="lembrete" <?php echo $template['tipo'] == 'lembrete' ? 'selected' : ''; ?>>Lembrete</option>
                    <option value="agradecimento" <?php echo $template['tipo'] == 'agradecimento' ? 'selected' : ''; ?>>Agradecimento</option>
                    <option value="aviso" <?php echo $template['tipo'] == 'aviso' ? 'selected' : ''; ?>>Aviso</option>
                    <option value="personalizado" <?php echo $template['tipo'] == 'personalizado' ? 'selected' : ''; ?>>Personalizado</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Template da Mensagem</label>
                <textarea class="form-control" name="template" rows="8" required><?php echo htmlspecialchars($template['template']); ?></textarea>
                <small class="form-text text-muted">
                    Use as variáveis: [NOME], [VALOR], [DATA_VENCIMENTO], [PIX], [BOLETO], [DATA_HOJE], [HORA_HOJE]
                </small>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" name="ativo" value="1" <?php echo $template['ativo'] ? 'checked' : ''; ?>>
                <label class="form-check-label">Ativo</label>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i> Salvar
                </button>
                <a href="templates_mensagem.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

