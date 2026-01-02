<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

requireActiveSystemSubscription();

$professor_id = $_SESSION['user_id'];
$page_title = 'Gerar Cobranças';
$error = '';
$success = '';

// Buscar alunos ativos para o select individual
$stmt = $pdo->prepare("SELECT a.id, a.nome, t.preco_padrao 
                       FROM alunos a 
                       LEFT JOIN tipos_aula t ON a.tipo_aula_id = t.id 
                       WHERE a.professor_id = ? AND a.status = 'ativo' 
                       ORDER BY a.nome");
$stmt->execute([$professor_id]);
$alunos = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_geracao = $_POST['tipo_geracao']; // 'individual' ou 'lote'
    $mes = $_POST['mes'];
    $ano = $_POST['ano'];
    $dia_vencimento = $_POST['dia_vencimento']; // Dia padrão para lote ou individual
    
    // Validar data
    if (!checkdate($mes, $dia_vencimento, $ano)) {
        // Se dia 31 não existe no mês, tenta ajustar ou dar erro. Vamos simplificar e pegar o último dia do mês se inválido.
        $ultimo_dia = date('t', strtotime("$ano-$mes-01"));
        if ($dia_vencimento > $ultimo_dia) $dia_vencimento = $ultimo_dia;
    }
    
    $data_vencimento = "$ano-$mes-$dia_vencimento";
    
    if ($tipo_geracao === 'individual') {
        $aluno_id = $_POST['aluno_id'];
        $valor = $_POST['valor'];
        
        if (empty($aluno_id) || empty($valor)) {
            $error = 'Selecione o aluno e o valor.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO mensalidades (professor_id, aluno_id, valor, data_vencimento, status) VALUES (?, ?, ?, ?, 'pendente')");
                $stmt->execute([$professor_id, $aluno_id, $valor, $data_vencimento]);
                $success = 'Cobrança gerada com sucesso!';
            } catch (PDOException $e) {
                $error = 'Erro ao gerar: ' . $e->getMessage();
            }
        }
    } elseif ($tipo_geracao === 'lote') {
        $count = 0;
        foreach ($alunos as $aluno) {
            // Verificar se já existe cobrança para este aluno neste mês/ano
            $stmt = $pdo->prepare("SELECT id FROM mensalidades WHERE professor_id = ? AND aluno_id = ? AND MONTH(data_vencimento) = ? AND YEAR(data_vencimento) = ?");
            $stmt->execute([$professor_id, $aluno['id'], $mes, $ano]);
            
            if ($stmt->rowCount() == 0) {
                // Se não tem preço definido, pula ou define 0 (vamos pular se null, mas ideal é avisar)
                $valor = $aluno['preco_padrao'] ?: 0;
                
                if ($valor > 0) {
                    $stmtIns = $pdo->prepare("INSERT INTO mensalidades (professor_id, aluno_id, valor, data_vencimento, status) VALUES (?, ?, ?, ?, 'pendente')");
                    $stmtIns->execute([$professor_id, $aluno['id'], $valor, $data_vencimento]);
                    $count++;
                }
            }
        }
        $success = "$count cobranças geradas com sucesso para o período $mes/$ano!";
    }
}

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-file-invoice-dollar me-2"></i> <?php echo $page_title; ?></h1>
    <a href="mensalidades.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Voltar</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success shadow-sm"><?php echo $success; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Configuração da Cobrança</h6>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="individual-tab" data-bs-toggle="tab" data-bs-target="#individual" type="button" role="tab">Individual</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="lote-tab" data-bs-toggle="tab" data-bs-target="#lote" type="button" role="tab">Em Lote (Todos Ativos)</button>
                    </li>
                </ul>

                <div class="tab-content" id="myTabContent">
                    
                    <!-- Aba Individual -->
                    <div class="tab-pane fade show active" id="individual" role="tabpanel">
                        <form method="POST" action="">
                            <input type="hidden" name="tipo_geracao" value="individual">
                            
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="aluno_id" class="form-label">Aluno</label>
                                    <select class="form-select" id="aluno_id" name="aluno_id" required onchange="atualizarValor(this)">
                                        <option value="">Selecione...</option>
                                        <?php foreach ($alunos as $a): ?>
                                            <option value="<?php echo $a['id']; ?>" data-preco="<?php echo $a['preco_padrao']; ?>">
                                                <?php echo htmlspecialchars($a['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="mes" class="form-label">Mês de Referência</label>
                                    <select class="form-select" name="mes" required>
                                        <?php 
                                        $mes_atual = date('m');
                                        for ($i=1; $i<=12; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo $i == $mes_atual ? 'selected' : ''; ?>><?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="ano" class="form-label">Ano</label>
                                    <input type="number" class="form-control" name="ano" value="<?php echo date('Y'); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="dia_vencimento" class="form-label">Dia Vencimento</label>
                                    <input type="number" class="form-control" name="dia_vencimento" value="10" min="1" max="31" required>
                                </div>
                                
                                <div class="col-md-12">
                                    <label for="valor" class="form-label">Valor (R$)</label>
                                    <input type="number" step="0.01" class="form-control" id="valor" name="valor" required>
                                </div>
                                
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-primary">Gerar Cobrança</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Aba Lote -->
                    <div class="tab-pane fade" id="lote" role="tabpanel">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Isso gerará cobranças para <strong>todos os alunos ativos</strong> usando o preço padrão do tipo de aula deles. Alunos sem tipo de aula ou preço definido serão ignorados.
                        </div>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="tipo_geracao" value="lote">
                            
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="mes_lote" class="form-label">Mês de Referência</label>
                                    <select class="form-select" id="mes_lote" name="mes" required>
                                        <?php 
                                        $mes_atual = date('m');
                                        for ($i=1; $i<=12; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo $i == $mes_atual ? 'selected' : ''; ?>><?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="ano_lote" class="form-label">Ano</label>
                                    <input type="number" class="form-control" id="ano_lote" name="ano" value="<?php echo date('Y'); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="dia_vencimento_lote" class="form-label">Dia Vencimento Padrão</label>
                                    <input type="number" class="form-control" id="dia_vencimento_lote" name="dia_vencimento" value="10" min="1" max="31" required>
                                </div>
                                
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-success">Gerar Todas as Cobranças</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function atualizarValor(select) {
    var option = select.options[select.selectedIndex];
    var preco = option.getAttribute('data-preco');
    if (preco) {
        document.getElementById('valor').value = preco;
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
