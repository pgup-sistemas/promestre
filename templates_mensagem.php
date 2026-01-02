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

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
    <h1 class="h4 mb-0"><i class="fas fa-comment-dots me-2"></i> Templates de Mensagem</h1>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTemplate">
        <i class="fas fa-plus me-1"></i> Novo Template
    </button>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>


<ul class="nav nav-tabs" id="templatesTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-templates" data-bs-toggle="tab" data-bs-target="#pane-templates" type="button" role="tab" aria-controls="pane-templates" aria-selected="true">
            Templates
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-historico" data-bs-toggle="tab" data-bs-target="#pane-historico" type="button" role="tab" aria-controls="pane-historico" aria-selected="false">
            Histórico
        </button>
    </li>
</ul>

<div class="tab-content border border-top-0 rounded-bottom p-3" id="templatesTabsContent">
    <div class="tab-pane fade show active" id="pane-templates" role="tabpanel" aria-labelledby="tab-templates" tabindex="0">
        <!-- Lista de Templates -->
        <div class="row">
            <?php foreach ($templates as $template): ?>
                <div class="col-md-6 mb-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center py-2">
                            <strong><?php echo htmlspecialchars($template['nome']); ?></strong>
                            <span class="badge bg-secondary"><?php echo ucfirst($template['tipo']); ?></span>
                        </div>
                        <div class="card-body">
                            <p class="card-text mb-2"><?php echo nl2br(htmlspecialchars($template['template'])); ?></p>
                            <small class="text-muted">
                                Variáveis: [NOME], [VALOR], [DATA_VENCIMENTO], [PIX], [BOLETO]
                            </small>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <div>
                                <?php if ($template['ativo']): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex gap-2">
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
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Nenhum template cadastrado. Clique em "Novo Template" para criar.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="tab-pane fade" id="pane-historico" role="tabpanel" aria-labelledby="tab-historico" tabindex="0">
        <!-- Histórico de Notificações -->
        <div class="card shadow-sm">
            <div class="card-header py-2">
                <h6 class="mb-0"><i class="fas fa-history me-2"></i> Histórico de Notificações Enviadas</h6>
            </div>
            <div class="card-body">
                <?php if (count($historico) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Aluno</th>
                                    <th>Tipo</th>
                                    <th class="d-none d-md-table-cell">WhatsApp</th>
                                    <th>Mensagem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historico as $notif): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($notif['enviado_em'])); ?></td>
                                        <td><?php echo htmlspecialchars($notif['aluno_nome'] ?? 'N/A'); ?></td>
                                        <td><span class="badge bg-info"><?php echo ucfirst($notif['tipo']); ?></span></td>
                                        <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($notif['whatsapp']); ?></td>
                                        <td>
                                            <small class="text-muted"><?php echo htmlspecialchars(substr($notif['mensagem_enviada'], 0, 120)); ?>...</small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Nenhuma notificação enviada ainda.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novo Template -->
<div class="modal fade" id="modalTemplate" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="templates_mensagem_salvar.php">
                <div class="modal-header">
                    <h6 class="modal-title">Novo Template</h6>
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

