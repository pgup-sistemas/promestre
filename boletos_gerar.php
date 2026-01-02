<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Gerar Boleto';
require_once 'includes/header.php';

// Verificar se o EfiBank está configurado
$stmt = $pdo->prepare("SELECT chave_pix, client_id_efi, client_secret_efi, certificado_efi FROM professores WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$professor = $stmt->fetch();

if (!$professor || !$professor['client_id_efi'] || !$professor['client_secret_efi'] || !$professor['certificado_efi']) {
    echo '<div class="alert alert-warning">Para gerar boletos, é necessário configurar a integração com o EfiBank. <a href="perfil.php">Configurar agora</a></div>';
    require_once 'includes/footer.php';
    exit;
}

// Carregar EfiBank
require_once 'includes/EfiBank.php';

// Obter mensalidade
$mensalidade_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$mensalidade_id) {
    redirect('mensalidades.php');
}

$stmt = $pdo->prepare("SELECT m.*, a.nome as aluno_nome, a.cpf FROM mensalidades m LEFT JOIN alunos a ON m.aluno_id = a.id WHERE m.id = ? AND m.professor_id = ?");
$stmt->execute([$mensalidade_id, $_SESSION['user_id']]);
$mensalidade = $stmt->fetch();

if (!$mensalidade) {
    echo '<div class="alert alert-danger">Mensalidade não encontrada.</div>';
    require_once 'includes/footer.php';
    exit;
}

// Processar geração de boleto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $efi = new EfiBank($professor['client_id_efi'], $professor['client_secret_efi'], $professor['certificado_efi']);
        
        // Dados do boleto
        $valor = $mensalidade['valor'] + $mensalidade['valor_multa'] + $mensalidade['valor_juros'];
        $vencimento = date('Y-m-d', strtotime($mensalidade['data_vencimento']));
        
        // Validade do boleto (configuração do professor)
        $validade_dias = 7; // Padrão
        $stmt = $pdo->prepare("SELECT validade_boleto_dias FROM professores WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $config = $stmt->fetch();
        if ($config && $config['validade_boleto_dias']) {
            $validade_dias = $config['validade_boleto_dias'];
        }
        
        $vencimento_final = date('Y-m-d', strtotime("+$validade_dias days", strtotime($vencimento)));
        
        // Dados do pagador
        $pagador = [
            'cpf' => $mensalidade['cpf'] ?: '00000000000',
            'nome' => $mensalidade['aluno_nome'],
            'email' => '', // Pode ser obtido do aluno se necessário
            'telefone' => '', // Pode ser obtido do aluno se necessário
            'endereco' => '' // Pode ser obtido do aluno se necessário
        ];
        
        // Gerar boleto
        $boleto = $efi->gerarBoleto($valor, $vencimento_final, $pagador, $mensalidade['id']);
        
        if ($boleto && isset($boleto['nosso_numero'])) {
            // Atualizar mensalidade com dados do boleto
            $stmt = $pdo->prepare("UPDATE mensalidades SET 
                boleto_url = ?, 
                boleto_barcode = ?, 
                boleto_expira_em = ?,
                status = 'pendente'
                WHERE id = ?");
            
            $stmt->execute([
                $boleto['link_baixar_pdf'] ?? null,
                $boleto['barcode'] ?? null,
                $vencimento_final,
                $mensalidade_id
            ]);
            
            echo '<div class="alert alert-success">Boleto gerado com sucesso!</div>';
            echo '<div class="card">';
            echo '<div class="card-body">';
            echo '<h5>Dados do Boleto:</h5>';
            echo '<p><strong>Nosso Número:</strong> ' . $boleto['nosso_numero'] . '</p>';
            echo '<p><strong>Vencimento:</strong> ' . date('d/m/Y', strtotime($vencimento_final)) . '</p>';
            echo '<p><strong>Valor:</strong> R$ ' . number_format($valor, 2, ',', '.') . '</p>';
            if (isset($boleto['link_baixar_pdf'])) {
                echo '<a href="' . $boleto['link_baixar_pdf'] . '" target="_blank" class="btn btn-primary">Baixar Boleto</a>';
            }
            echo '</div>';
            echo '</div>';
        } else {
            throw new Exception('Erro ao gerar boleto: ' . json_encode($boleto));
        }
        
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Erro ao gerar boleto: ' . $e->getMessage() . '</div>';
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-barcode me-2"></i> Gerar Boleto</h1>
    <a href="mensalidades.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Voltar</a>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0">Dados da Mensalidade</h5>
            </div>
            <div class="card-body">
                <p><strong>Aluno:</strong> <?= $mensalidade['aluno_nome'] ?></p>
                <p><strong>Valor Original:</strong> R$ <?= number_format($mensalidade['valor'], 2, ',', '.') ?></p>
                <p><strong>Multa:</strong> R$ <?= number_format($mensalidade['valor_multa'], 2, ',', '.') ?></p>
                <p><strong>Juros:</strong> R$ <?= number_format($mensalidade['valor_juros'], 2, ',', '.') ?></p>
                <p><strong>Valor Total:</strong> <strong>R$ <?= number_format($mensalidade['valor'] + $mensalidade['valor_multa'] + $mensalidade['valor_juros'], 2, ',', '.') ?></strong></p>
                <p><strong>Vencimento:</strong> <?= date('d/m/Y', strtotime($mensalidade['data_vencimento'])) ?></p>
                <p><strong>Status:</strong> <span class="badge bg-<?= $mensalidade['status'] === 'pago' ? 'success' : ($mensalidade['status'] === 'atrasado' ? 'warning' : 'info') ?>"><?= ucfirst($mensalidade['status']) ?></span></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0">Configurações do Boleto</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Vencimento do Boleto</label>
                        <input type="date" class="form-control" name="vencimento" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Dados do Pagador</label>
                        <div class="row">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="cpf" value="<?= $mensalidade['cpf'] ?: '' ?>" placeholder="CPF">
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="nome" value="<?= $mensalidade['aluno_nome'] ?>" placeholder="Nome">
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        O boleto será gerado com o valor total da mensalidade (valor + multa + juros).
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-barcode me-2"></i> Gerar Boleto
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>