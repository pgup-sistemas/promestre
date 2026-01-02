<?php
require_once 'includes/config.php';
require_once 'includes/EfiPay.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$professor_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id) {
    redirect('mensalidades.php');
}

// Buscar dados da mensalidade e do professor
$stmt = $pdo->prepare("
    SELECT m.*, p.client_id_efi, p.client_secret_efi, p.certificado_efi, p.chave_pix, a.nome as aluno_nome, a.cpf as aluno_cpf 
    FROM mensalidades m
    JOIN professores p ON m.professor_id = p.id
    JOIN alunos a ON m.aluno_id = a.id
    WHERE m.id = ? AND m.professor_id = ?
");
$stmt->execute([$id, $professor_id]);
$dados = $stmt->fetch();

if (!$dados) {
    redirect('mensalidades.php');
}

$error = '';
$qrcode_image = '';
$qrcode_text = '';

// Se não tiver credenciais configuradas
if (empty($dados['client_id_efi']) || empty($dados['client_secret_efi']) || empty($dados['certificado_efi'])) {
    $error = 'Para gerar PIX, você precisa configurar suas credenciais da EfiBank no seu <a href="perfil.php">Perfil</a>.';
} else {
    try {
        $efi = new EfiPay($dados['client_id_efi'], $dados['client_secret_efi'], $dados['certificado_efi']);

        // Se já tem txid, busca o QR Code
        if (!empty($dados['txid_efi'])) {
            // Precisamos do loc_id para buscar o QR Code. 
            // Na criação, a Efi retorna o loc.id. Se não salvamos, talvez seja melhor recriar ou tentar buscar pelo txid (GET /v2/cob/:txid)
            // Vamos tentar buscar os detalhes da cobrança para pegar o loc_id
            
            // Mas espera, se já geramos, deveríamos ter salvo o link ou o qrcode text. 
            // O banco tem `link_pagamento`. Se for o CopyPaste, ótimo.
            
            if (!empty($dados['link_pagamento'])) {
                 $qrcode_text = $dados['link_pagamento'];
                 // Para imagem, podemos usar uma lib JS ou tentar recuperar da Efi se tivermos o loc_id
                 // Vamos assumir que vamos gerar um novo se não tivermos a imagem fácil, 
                 // ou melhor, vamos tentar consultar a cobrança para pegar o loc_id
            }
            
            // Consulta cobrança para garantir e pegar loc_id atualizado
            // Implementar método getCob no EfiPay seria bom, mas vou usar cURL direto aqui ou adicionar lá.
            // Vou simplificar: se tem txid, tentamos criar um novo qrcode se não tivermos o texto.
        } 
        
        // Se não tem txid ou precisamos gerar
        if (empty($dados['txid_efi']) || empty($dados['link_pagamento'])) {
            // Gerar TxId único
            $txid = md5(uniqid(rand(), true)); // Efi aceita txid customizado (padrão regex [a-zA-Z0-9]{26,35})
            // Simplificando txid para 30 chars
            $txid = substr(str_replace(['-','_'], '', $txid) . time(), 0, 30);
            
            $valor = $dados['valor'];
            $chave = $dados['chave_pix']; // Chave cadastrada no perfil
            
            if (empty($chave)) {
                throw new Exception("Você precisa cadastrar uma Chave PIX no seu perfil.");
            }

            // Criar Cobrança
            $resp = $efi->createCob($txid, $valor, $chave, null, "Mensalidade Promestre");
            
            if (isset($resp['txid']) && isset($resp['loc']['id'])) {
                $txid_efi = $resp['txid'];
                $loc_id = $resp['loc']['id'];
                
                // Gerar QR Code
                $respQr = $efi->getQrCode($loc_id);
                
                if (isset($respQr['imagem']) && isset($respQr['qrcode'])) {
                    $qrcode_image = $respQr['imagem']; // base64
                    $qrcode_text = $respQr['qrcode'];
                    
                    // Salvar no banco
                    $stmt = $pdo->prepare("UPDATE mensalidades SET txid_efi = ?, link_pagamento = ? WHERE id = ?");
                    $stmt->execute([$txid_efi, $qrcode_text, $id]);
                    
                    // Atualizar array local
                    $dados['txid_efi'] = $txid_efi;
                    $dados['link_pagamento'] = $qrcode_text;
                }
            } else {
                throw new Exception("Erro ao criar cobrança: " . json_encode($resp));
            }
        } else {
            // Já existe, vamos exibir o que tem. 
            // Se quisermos a imagem, teríamos que buscar na API de novo usando o loc_id que não salvamos (erro meu no design do DB).
            // Mas podemos usar uma lib JS qrcode.min.js para desenhar o QRCode a partir do texto copia e cola.
            $qrcode_text = $dados['link_pagamento'];
        }

    } catch (Exception $e) {
        $error = "Erro na integração Efi: " . $e->getMessage();
    }
}

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fa-brands fa-pix me-2"></i> Pagamento via PIX</h1>
    <a href="mensalidades.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Voltar</a>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Dados do Pagamento</h6>
            </div>
            <div class="card-body text-center p-5">
                
                <?php if ($error): ?>
                    <div class="alert alert-danger text-start"><?php echo $error; ?></div>
                <?php else: ?>
                    
                    <h5 class="mb-3">Valor: <strong>R$ <?php echo number_format($dados['valor'], 2, ',', '.'); ?></strong></h5>
                    <p class="text-muted">Aluno: <?php echo htmlspecialchars($dados['aluno_nome']); ?></p>

                    <div class="my-4 d-flex justify-content-center">
                        <?php if ($qrcode_image): ?>
                            <img src="<?php echo $qrcode_image; ?>" class="img-fluid border p-2 rounded" style="max-width: 250px;" alt="QR Code PIX">
                        <?php else: ?>
                            <!-- Fallback usando JS se não tivermos a imagem base64 mas tivermos o texto -->
                            <div id="qrcode-canvas"></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted small">Pix Copia e Cola</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="pixCopiaCola" value="<?php echo htmlspecialchars($qrcode_text); ?>" readonly>
                            <button class="btn btn-outline-primary" type="button" onclick="copiarPix()">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                        </div>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Script para gerar QR Code se não tiver imagem e Copiar -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    function copiarPix() {
        var copyText = document.getElementById("pixCopiaCola");
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value).then(function() {
            alert("Código PIX copiado!");
        });
    }

    <?php if (empty($qrcode_image) && !empty($qrcode_text)): ?>
        var qrcode = new QRCode(document.getElementById("qrcode-canvas"), {
            text: "<?php echo $qrcode_text; ?>",
            width: 200,
            height: 200,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    <?php endif; ?>
</script>

<?php require_once 'includes/footer.php'; ?>
