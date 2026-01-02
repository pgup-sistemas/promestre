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

// Buscar tipos de aula
$stmt = $pdo->prepare("SELECT * FROM tipos_aula WHERE professor_id = ? AND ativo = 1");
$stmt->execute([$professor['id']]);
$tipos_aula = $stmt->fetchAll();

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = clean($_POST['nome']);
    $email = clean($_POST['email']);
    $telefone = clean($_POST['telefone']);
    $whatsapp = clean($_POST['whatsapp']);
    $cpf = clean($_POST['cpf']);
    $data_nascimento = $_POST['data_nascimento'];
    $tipo_aula_id = !empty($_POST['tipo_aula_id']) ? (int)$_POST['tipo_aula_id'] : null;
    $endereco = clean($_POST['endereco']);
    
    if (empty($nome) || empty($whatsapp)) {
        $error = "Nome e WhatsApp são obrigatórios.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO alunos (professor_id, nome, email, telefone, whatsapp, cpf, data_nascimento, endereco, tipo_aula_id, status, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'inativo', 'Pré-matrícula realizada pelo site')");
            $stmt->execute([$professor['id'], $nome, $email, $telefone, $whatsapp, $cpf, $data_nascimento, $endereco, $tipo_aula_id]);
            $success = true;
        } catch (PDOException $e) {
            $error = "Erro ao realizar matrícula: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pré-Matrícula - <?php echo htmlspecialchars($professor['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .hero { background: linear-gradient(135deg, #20c997 0%, #0d6efd 100%); color: white; padding: 3rem 0; margin-bottom: 2rem; }
    </style>
</head>
<body>

<div class="hero text-center">
    <div class="container">
        <h1>Pré-Matrícula Online</h1>
        <p class="lead">Estude com <?php echo htmlspecialchars($professor['nome']); ?></p>
    </div>
</div>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body p-4">
                    <?php if ($success): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle text-success fa-5x mb-3"></i>
                            <h2>Sucesso!</h2>
                            <p class="lead">Sua pré-matrícula foi enviada com sucesso.</p>
                            <p>O professor entrará em contato em breve para confirmar.</p>
                        </div>
                    <?php else: ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <h5 class="mb-3 text-primary">Seus Dados</h5>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="nome" class="form-label">Nome Completo *</label>
                                    <input type="text" class="form-control" id="nome" name="nome" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email">
                                </div>
                                <div class="col-md-6">
                                    <label for="cpf" class="form-label">CPF</label>
                                    <input type="text" class="form-control" id="cpf" name="cpf">
                                </div>
                                <div class="col-md-6">
                                    <label for="telefone" class="form-label">Telefone</label>
                                    <input type="text" class="form-control" id="telefone" name="telefone">
                                </div>
                                <div class="col-md-6">
                                    <label for="whatsapp" class="form-label">WhatsApp *</label>
                                    <input type="text" class="form-control" id="whatsapp" name="whatsapp" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                                    <input type="date" class="form-control" id="data_nascimento" name="data_nascimento">
                                </div>
                                <div class="col-md-6">
                                    <label for="tipo_aula_id" class="form-label">Interesse / Curso</label>
                                    <select class="form-select" id="tipo_aula_id" name="tipo_aula_id">
                                        <option value="">Selecione...</option>
                                        <?php foreach ($tipos_aula as $aula): ?>
                                            <option value="<?php echo $aula['id']; ?>"><?php echo htmlspecialchars($aula['nome']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label for="endereco" class="form-label">Endereço</label>
                                    <textarea class="form-control" id="endereco" name="endereco" rows="2"></textarea>
                                </div>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">Enviar Matrícula</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
