<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Enviar Cobranças';
$professor_id = $_SESSION['user_id'];

$error = '';
$mensalidades_selecionadas = [];

// Buscar template padrão de cobrança
$stmt_template = $pdo->prepare("
    SELECT * FROM templates_mensagem 
    WHERE professor_id = ? AND tipo = 'cobranca' AND ativo = 1 
    LIMIT 1
");
$stmt_template->execute([$professor_id]);
$template = $stmt_template->fetch();

if (!$template) {
    setFlash('Crie um template de cobrança primeiro em Templates de Mensagem.', 'warning');
    redirect('templates_mensagem.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensalidade_ids = isset($_POST['mensalidades']) ? $_POST['mensalidades'] : [];
    
    if (empty($mensalidade_ids)) {
        $error = 'Selecione pelo menos uma mensalidade.';
    } else {
        // Buscar mensalidades e alunos
        $placeholders = implode(',', array_fill(0, count($mensalidade_ids), '?'));
        $stmt = $pdo->prepare("
            SELECT m.*, a.nome as aluno_nome, a.whatsapp, a.telefone 
            FROM mensalidades m 
            JOIN alunos a ON m.aluno_id = a.id 
            WHERE m.id IN ($placeholders) AND m.professor_id = ?
        ");
        $stmt->execute(array_merge($mensalidade_ids, [$professor_id]));
        $mensalidades_selecionadas = $stmt->fetchAll();
    }
}

// Buscar mensalidades pendentes
$mes = isset($_GET['mes']) ? $_GET['mes'] : date('m');
$ano = isset($_GET['ano']) ? $_GET['ano'] : date('Y');

$stmt = $pdo->prepare("
    SELECT m.*, a.nome as aluno_nome, a.whatsapp 
    FROM mensalidades m 
    JOIN alunos a ON m.aluno_id = a.id 
    WHERE m.professor_id = ? 
    AND m.status IN ('pendente', 'atrasado')
    AND MONTH(m.data_vencimento) = ?
    AND YEAR(m.data_vencimento) = ?
    ORDER BY m.data_vencimento ASC
");
$stmt->execute([$professor_id, $mes, $ano]);
$mensalidades = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-paper-plane me-2"></i> Enviar Cobranças</h1>
    <a href="mensalidades.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i> Voltar
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (count($mensalidades_selecionadas) > 0): ?>
    <!-- Abrir WhatsApp para cada mensalidade -->
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Abrindo WhatsApp para enviar as cobranças. Clique em cada link para enviar a mensagem.
    </div>
    
    <div class="list-group mb-4">
        <?php foreach ($mensalidades_selecionadas as $mensalidade): 
            // Preparar dados para o template
            $dados_template = [
                'nome' => $mensalidade['aluno_nome'],
                'valor' => floatval($mensalidade['valor']),
                'data_vencimento' => $mensalidade['data_vencimento'],
                'pix' => $mensalidade['link_pagamento'] ?? '',
                'boleto' => $mensalidade['boleto_url'] ?? ''
            ];
            
            // Processar template
            $mensagem = processarTemplate($template['template'], $dados_template);
            
            // Gerar link WhatsApp
            $whatsapp_link = gerarLinkWhatsApp($mensalidade['whatsapp'], $mensagem);
            
            // Registrar no histórico
            registrarNotificacao(
                $professor_id,
                $mensalidade['aluno_id'],
                $mensalidade['id'],
                $template['id'],
                'cobranca',
                $template['template'],
                $mensagem,
                $mensalidade['whatsapp']
            );
        ?>
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1"><?php echo htmlspecialchars($mensalidade['aluno_nome']); ?></h6>
                        <p class="mb-1 text-muted">
                            Valor: R$ <?php echo number_format($mensalidade['valor'], 2, ',', '.'); ?> | 
                            Vencimento: <?php echo date('d/m/Y', strtotime($mensalidade['data_vencimento'])); ?>
                        </p>
                        <small class="text-muted"><?php echo htmlspecialchars(substr($mensagem, 0, 100)); ?>...</small>
                    </div>
                    <a href="<?php echo $whatsapp_link; ?>" target="_blank" class="btn btn-success">
                        <i class="fab fa-whatsapp me-2"></i> Enviar
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <script>
        // Abrir primeiro link automaticamente
        document.addEventListener('DOMContentLoaded', function() {
            var firstLink = document.querySelector('.list-group-item .btn-success');
            if (firstLink) {
                setTimeout(function() {
                    firstLink.click();
                }, 500);
            }
        });
    </script>
<?php else: ?>
    <!-- Formulário de seleção -->
    <form method="POST" action="">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Selecione as Mensalidades para Cobrar</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Filtros:</label>
                    <div class="row">
                        <div class="col-md-6">
                            <input type="month" class="form-control" name="periodo" 
                                   value="<?php echo $ano . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT); ?>" 
                                   onchange="window.location.href='?mes=' + this.value.split('-')[1] + '&ano=' + this.value.split('-')[0]">
                        </div>
                        <div class="col-md-6">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selecionarTodas()">
                                Selecionar Todas
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="desmarcarTodas()">
                                Desmarcar Todas
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if (count($mensalidades) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50"><input type="checkbox" id="selectAll" onchange="toggleAll()"></th>
                                    <th>Aluno</th>
                                    <th>Valor</th>
                                    <th>Vencimento</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mensalidades as $m): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="mensalidades[]" value="<?php echo $m['id']; ?>" class="mensalidade-check">
                                        </td>
                                        <td><?php echo htmlspecialchars($m['aluno_nome']); ?></td>
                                        <td>R$ <?php echo number_format($m['valor'], 2, ',', '.'); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($m['data_vencimento'])); ?></td>
                                        <td>
                                            <?php if ($m['status'] == 'atrasado'): ?>
                                                <span class="badge bg-danger">Atrasado</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Pendente</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i> Enviar Cobranças Selecionadas
                        </button>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Nenhuma mensalidade pendente encontrada para este período.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </form>
    
    <script>
        function toggleAll() {
            var checkboxes = document.querySelectorAll('.mensalidade-check');
            var selectAll = document.getElementById('selectAll');
            checkboxes.forEach(function(cb) {
                cb.checked = selectAll.checked;
            });
        }
        
        function selecionarTodas() {
            document.querySelectorAll('.mensalidade-check').forEach(function(cb) {
                cb.checked = true;
            });
            document.getElementById('selectAll').checked = true;
        }
        
        function desmarcarTodas() {
            document.querySelectorAll('.mensalidade-check').forEach(function(cb) {
                cb.checked = false;
            });
            document.getElementById('selectAll').checked = false;
        }
    </script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

