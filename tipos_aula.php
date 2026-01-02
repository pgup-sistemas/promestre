<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Tipos de Aula';
$professor_id = $_SESSION['user_id'];

// Query
$stmt = $pdo->prepare("SELECT * FROM tipos_aula WHERE professor_id = ? ORDER BY nome ASC");
$stmt->execute([$professor_id]);
$tipos = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
    <h1 class="h4 mb-0"><i class="fas fa-chalkboard me-2"></i> Tipos de Aula</h1>
    <a href="tipos_aula_cadastro.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Novo Tipo</a>
</div>

<div class="row">
    <?php if (count($tipos) > 0): ?>
        <?php foreach ($tipos as $tipo): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm border-start border-5" style="border-left-color: <?php echo $tipo['cor']; ?> !important;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="card-title fw-bold"><?php echo htmlspecialchars($tipo['nome']); ?></h6>
                            <?php if ($tipo['ativo']): ?>
                                <span class="badge bg-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inativo</span>
                            <?php endif; ?>
                        </div>
                        
                        <h4 class="text-primary mb-3">R$ <?php echo number_format($tipo['preco_padrao'], 2, ',', '.'); ?> <small class="text-muted fs-6">/mês</small></h4>
                        
                        <p class="card-text text-muted small mb-3">
                            <?php echo $tipo['descricao'] ? htmlspecialchars($tipo['descricao']) : 'Sem descrição.'; ?>
                        </p>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="tipos_aula_cadastro.php?id=<?php echo $tipo['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                            <a href="tipos_aula_excluir.php?id=<?php echo $tipo['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza que deseja excluir?');">
                                <i class="fas fa-trash me-1"></i> Excluir
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info text-center py-5">
                <i class="fas fa-chalkboard-teacher fa-3x mb-3 opacity-50"></i>
                <h5>Nenhum tipo de aula cadastrado</h5>
                <p>Comece criando os serviços que você oferece (ex: Piano, Inglês, Yoga).</p>
                <a href="tipos_aula_cadastro.php" class="btn btn-primary mt-2">Cadastrar Primeiro Tipo</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
