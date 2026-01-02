<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Templates de Mensagem';
$professor_id = $_SESSION['user_id'];

$error = '';
$success = '';

// Buscar templates
$stmt = $pdo->prepare("SELECT * FROM templates_mensagem WHERE professor_id = ? ORDER BY tipo, nome");
$stmt->execute([$professor_id]);
$templates = $stmt->fetchAll();

// Buscar histórico recente
$stmt_hist = $pdo->prepare("
    SELECT h.*, a.nome as aluno_nome 
    FROM historico_notificacoes h 
    LEFT JOIN alunos a ON h.aluno_id = a.id 
    WHERE h.professor_id = ? 
    ORDER BY h.enviado_em DESC 
    LIMIT 50
");
$stmt_hist->execute([$professor_id]);
$historico = $stmt_hist->fetchAll();

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-comment-dots me-2"></i> Templates de Mensagem</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTemplate">
        <i class="fas fa-plus me-2"></i> Novo Template
    </button>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<!-- Lista de Templates -->
<div class="row mb-4">
    <?php foreach ($templates as $template): ?>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><?php echo htmlspecialchars($template['nome']); ?></strong>
                    <span class="badge bg-secondary"><?php echo ucfirst($template['tipo']); ?></span>
                </div>
                <div class="card-body">
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($template['template'])); ?></p>
                    <small class="text-muted">
                        Variáveis disponíveis: [NOME], [VALOR], [DATA_VENCIMENTO], [PIX], [BOLETO]
                    </small>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <div>
                        <?php if ($template['ativo']): ?>
                            <span class="badge bg-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inativo</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <a href="templates_mensagem_editar.php?id=<?php echo $template['id']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <a href="templates_mensagem_excluir.php?id=<?php echo $template['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if (count($templates) == 0): ?>
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Nenhum template cadastrado. Clique em "Novo Template" para criar.
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Histórico de Notificações -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-history me-2"></i> Histórico de Notificações Enviadas</h5>
    </div>
    <div class="card-body">
        <?php if (count($historico) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Aluno</th>
                            <th>Tipo</th>
                            <th>WhatsApp</th>
                            <th>Mensagem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $notif): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($notif['enviado_em'])); ?></td>
                                <td><?php echo htmlspecialchars($notif['aluno_nome'] ?? 'N/A'); ?></td>
                                <td><span class="badge bg-info"><?php echo ucfirst($notif['tipo']); ?></span></td>
                                <td><?php echo htmlspecialchars($notif['whatsapp']); ?></td>
                                <td>
                                    <small class="text-muted"><?php echo htmlspecialchars(substr($notif['mensagem_enviada'], 0, 100)); ?>...</small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">Nenhuma notificação enviada ainda.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Novo Template -->
<div class="modal fade" id="modalTemplate" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="templates_mensagem_salvar.php">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome do Template</label>
                        <input type="text" class="form-control" name="nome" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" name="tipo" required>
                            <option value="cobranca">Cobrança</option>
                            <option value="lembrete">Lembrete</option>
                            <option value="agradecimento">Agradecimento</option>
                            <option value="aviso">Aviso</option>
                            <option value="personalizado">Personalizado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Template da Mensagem</label>
                        <textarea class="form-control" name="template" rows="6" required></textarea>
                        <small class="form-text text-muted">
                            Use as variáveis: [NOME], [VALOR], [DATA_VENCIMENTO], [PIX], [BOLETO]
                        </small>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="ativo" value="1" checked>
                        <label class="form-check-label">Ativo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

