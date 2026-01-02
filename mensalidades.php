<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Financeiro';
$professor_id = $_SESSION['user_id'];

// Filtros
$mes = isset($_GET['mes']) ? $_GET['mes'] : date('m');
$ano = isset($_GET['ano']) ? $_GET['ano'] : date('Y');
$status = isset($_GET['status']) ? clean($_GET['status']) : 'todos';

// Query
$sql = "SELECT m.*, a.nome as aluno_nome, a.whatsapp as aluno_whatsapp 
        FROM mensalidades m 
        JOIN alunos a ON m.aluno_id = a.id 
        WHERE m.professor_id = ? 
        AND MONTH(m.data_vencimento) = ? 
        AND YEAR(m.data_vencimento) = ?";
$params = [$professor_id, $mes, $ano];

if ($status && $status != 'todos') {
    $sql .= " AND m.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY m.data_vencimento ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$mensalidades = $stmt->fetchAll();

// Totais
$total_recebido = 0;
$total_pendente = 0;
$total_atrasado = 0;

foreach ($mensalidades as $m) {
    if ($m['status'] == 'pago') $total_recebido += $m['valor'];
    elseif ($m['status'] == 'pendente') $total_pendente += $m['valor'];
    elseif ($m['status'] == 'atrasado') $total_atrasado += $m['valor'];
}

require_once 'includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
    <h1 class="h4 mb-0"><i class="fas fa-file-invoice-dollar me-2"></i> Financeiro</h1>
    <div class="d-flex flex-wrap gap-2">
        <a href="mensalidades_enviar.php" class="btn btn-success btn-sm"><i class="fas fa-paper-plane me-1"></i> Enviar Cobranças</a>
        <a href="mensalidades_gerar.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Nova Cobrança</a>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-12 col-md-4">
        <div class="card shadow-sm h-100" style="border-left: 4px solid var(--success-color);">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Recebido</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">R$ <?php echo number_format($total_recebido, 2, ',', '.'); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card shadow-sm h-100" style="border-left: 4px solid var(--warning-color);">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pendente</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">R$ <?php echo number_format($total_pendente, 2, ',', '.'); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card shadow-sm h-100" style="border-left: 4px solid var(--danger-color);">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Atrasado</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">R$ <?php echo number_format($total_atrasado, 2, ',', '.'); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-times-circle fa-2x text-gray-300 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <select class="form-select" name="mes">
                    <?php 
                    $meses = [1=>'Janeiro', 2=>'Fevereiro', 3=>'Março', 4=>'Abril', 5=>'Maio', 6=>'Junho', 7=>'Julho', 8=>'Agosto', 9=>'Setembro', 10=>'Outubro', 11=>'Novembro', 12=>'Dezembro'];
                    foreach ($meses as $num => $nome): ?>
                        <option value="<?php echo $num; ?>" <?php echo $mes == $num ? 'selected' : ''; ?>><?php echo $nome; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <input type="number" class="form-control" name="ano" value="<?php echo $ano; ?>">
            </div>
            <div class="col-12 col-md-3">
                <select class="form-select" name="status">
                    <option value="todos" <?php echo $status == 'todos' ? 'selected' : ''; ?>>Todos os Status</option>
                    <option value="pendente" <?php echo $status == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                    <option value="pago" <?php echo $status == 'pago' ? 'selected' : ''; ?>>Pago</option>
                    <option value="atrasado" <?php echo $status == 'atrasado' ? 'selected' : ''; ?>>Atrasado</option>
                    <option value="cancelado" <?php echo $status == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-secondary btn-sm w-100">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Aluno</th>
                        <th>Vencimento</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($mensalidades) > 0): ?>
                        <?php foreach ($mensalidades as $m): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?php echo htmlspecialchars($m['aluno_nome']); ?></div>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($m['data_vencimento'])); ?></td>
                                <td>R$ <?php echo number_format($m['valor'], 2, ',', '.'); ?></td>
                                <td>
                                    <?php 
                                    $badge_class = 'bg-secondary';
                                    if ($m['status'] == 'pago') $badge_class = 'bg-success';
                                    elseif ($m['status'] == 'pendente') $badge_class = 'bg-warning text-dark';
                                    elseif ($m['status'] == 'atrasado') $badge_class = 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($m['status']); ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <?php 
                                            // Link WhatsApp
                                            $whatsapp_num = preg_replace('/[^0-9]/', '', $m['aluno_whatsapp']);
                                            if (strlen($whatsapp_num) <= 11) $whatsapp_num = '55' . $whatsapp_num;
                                            
                                            $msg = "Olá " . $m['aluno_nome'] . ", sua mensalidade de " . date('m/Y', strtotime($m['data_vencimento'])) . " no valor de R$ " . number_format($m['valor'], 2, ',', '.') . " vence em " . date('d/m/Y', strtotime($m['data_vencimento'])) . ".";
                                            $link_wa = "https://wa.me/$whatsapp_num?text=" . urlencode($msg);
                                        ?>
                                        <a href="<?php echo $link_wa; ?>" target="_blank" class="btn btn-sm btn-outline-success" title="Cobrar no WhatsApp">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>

                                        <?php if ($m['status'] != 'pago'): ?>
                                            <a href="mensalidades_pix.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-info" title="Gerar/Ver PIX">
                                                <i class="fa-brands fa-pix"></i>
                                            </a>
                                            <a href="mensalidades_cartao.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-dark" title="Pagar com Cartão">
                                                <i class="fas fa-credit-card"></i>
                                            </a>
                                            <a href="mensalidades_pagar.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-success" title="Marcar como Pago (Dinheiro)">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="mensalidades_editar.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="mensalidades_excluir.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-danger" title="Excluir" onclick="return confirm('Excluir esta cobrança?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <p class="text-muted mb-0">Nenhuma cobrança encontrada neste período.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
