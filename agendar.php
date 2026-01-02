<?php
require_once 'includes/config.php';

$slug = isset($_GET['p']) ? clean($_GET['p']) : '';
$professor = null;

if ($slug) {
    $stmt = $pdo->prepare("SELECT * FROM professores WHERE slug = ?");
    $stmt->execute([$slug]);
    $professor = $stmt->fetch();
}

if (!$professor) {
    die("Professor não encontrado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = clean($_POST['nome']);
    $whatsapp = clean($_POST['whatsapp']);
    $interesse = clean($_POST['interesse']);
    $horario = clean($_POST['horario']);
    
    // Formatar mensagem
    $msg = "Olá " . $professor['nome'] . ", me chamo *$nome*.\n";
    $msg .= "Gostaria de agendar uma aula experimental de *$interesse*.\n";
    $msg .= "Minha disponibilidade é: $horario.\n";
    $msg .= "Meu WhatsApp: $whatsapp";
    
    $link = "https://wa.me/55" . preg_replace('/[^0-9]/', '', $professor['telefone']) . "?text=" . urlencode($msg);
    
    header("Location: $link");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Aula - <?php echo htmlspecialchars($professor['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 3rem 0; margin-bottom: 2rem; }
    </style>
</head>
<body>

<div class="hero text-center">
    <div class="container">
        <h1>Agendar Aula Experimental</h1>
        <p class="lead">Com o professor(a) <?php echo htmlspecialchars($professor['nome']); ?></p>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center align-items-center">
        <!-- Coluna do Formulário -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <h4 class="card-title text-center mb-4 text-primary">Preencha seus dados</h4>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Seu Nome</label>
                            <input type="text" class="form-control bg-light" id="nome" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="whatsapp" class="form-label">Seu WhatsApp</label>
                            <input type="text" class="form-control bg-light" id="whatsapp" name="whatsapp" placeholder="(DDD) 99999-9999" required>
                        </div>
                        <div class="mb-3">
                            <label for="interesse" class="form-label">Interesse (Instrumento/Matéria)</label>
                            <input type="text" class="form-control bg-light" id="interesse" name="interesse" required>
                        </div>
                        <div class="mb-3">
                            <label for="horario" class="form-label">Melhor Dia/Horário</label>
                            <input type="text" class="form-control bg-light" id="horario" name="horario" placeholder="Ex: Terça à tarde ou Sábado de manhã" required>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success btn-lg shadow-sm">
                                <i class="fab fa-whatsapp me-2"></i> Solicitar via WhatsApp
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Coluna de Instruções (Onboarding Aluno) -->
        <div class="col-lg-5 mb-4 ms-lg-5">
            <div class="ps-lg-4">
                <h3 class="mb-4 text-secondary"><i class="fas fa-question-circle me-2"></i> Como funciona?</h3>
                
                <div class="d-flex mb-4">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">1</div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="mb-1">Informe seus dados</h5>
                        <p class="text-muted small">Preencha o formulário ao lado com seu nome, contato e o que deseja aprender.</p>
                    </div>
                </div>

                <div class="d-flex mb-4">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">2</div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="mb-1">Envie a solicitação</h5>
                        <p class="text-muted small">Ao clicar no botão, você será redirecionado automaticamente para o WhatsApp do professor.</p>
                    </div>
                </div>

                <div class="d-flex mb-4">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">3</div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="mb-1">Confirme o agendamento</h5>
                        <p class="text-muted small">Basta enviar a mensagem preenchida no WhatsApp e combinar os detalhes finais diretamente com o professor.</p>
                    </div>
                </div>

                <div class="alert alert-light border shadow-sm mt-4">
                    <small class="text-muted"><i class="fas fa-lock me-1"></i> Seus dados são enviados diretamente para o professor e não ficam salvos publicamente.</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-3 text-muted">
        <small>Sistema Promestre &copy; <?php echo date('Y'); ?></small>
    </div>
</div>

</body>
</html>
