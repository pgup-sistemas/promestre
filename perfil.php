<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$professor_id = $_SESSION['user_id'];
$page_title = 'Meu Perfil';
$error = '';
$success = '';

// Buscar dados atuais
$stmt = $pdo->prepare("SELECT * FROM professores WHERE id = ?");
$stmt->execute([$professor_id]);
$professor = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = clean($_POST['nome']);
    $email = clean($_POST['email']); // Idealmente verificar unicidade se mudar
    $telefone = clean($_POST['telefone']);
    $chave_pix = clean($_POST['chave_pix']);
    $client_id_efi = clean($_POST['client_id_efi']);
    $client_secret_efi = clean($_POST['client_secret_efi']);

    // Upload Certificado
    $certificado_path = $professor['certificado_efi'];
    if (isset($_FILES['certificado_efi']) && $_FILES['certificado_efi']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['certificado_efi']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['p12', 'pem'])) {
            $new_name = 'cert_' . $professor_id . '_' . time() . '.' . $ext;
            $upload_dir = __DIR__ . '/includes/certs/';
            
            if (move_uploaded_file($_FILES['certificado_efi']['tmp_name'], $upload_dir . $new_name)) {
                $certificado_path = $upload_dir . $new_name;
            } else {
                $error = "Erro ao salvar certificado.";
            }
        } else {
            $error = "Formato inválido. Use .p12 ou .pem";
        }
    }
    
    // Senha (opcional)
    $nova_senha = $_POST['nova_senha'];
    $confirma_senha = $_POST['confirma_senha'];

    if (empty($nome) || empty($email)) {
        $error = 'Nome e Email são obrigatórios.';
    } else {
        try {
            if (!empty($nova_senha)) {
                if ($nova_senha !== $confirma_senha) {
                    $error = 'As senhas não coincidem.';
                } else {
                    $hashed = password_hash($nova_senha, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE professores SET nome = ?, email = ?, telefone = ?, chave_pix = ?, client_id_efi = ?, client_secret_efi = ?, certificado_efi = ?, senha = ? WHERE id = ?");
                    $stmt->execute([$nome, $email, $telefone, $chave_pix, $client_id_efi, $client_secret_efi, $certificado_path, $hashed, $professor_id]);
                    $_SESSION['user_name'] = $nome; // Atualizar sessão
                    $success = 'Perfil e senha atualizados!';
                }
            } else {
                if (!$error) {
                    $stmt = $pdo->prepare("UPDATE professores SET nome = ?, email = ?, telefone = ?, chave_pix = ?, client_id_efi = ?, client_secret_efi = ?, certificado_efi = ? WHERE id = ?");
                    $stmt->execute([$nome, $email, $telefone, $chave_pix, $client_id_efi, $client_secret_efi, $certificado_path, $professor_id]);
                    $_SESSION['user_name'] = $nome; // Atualizar sessão
                    $success = 'Perfil atualizado!';
                }
            }
            
            // Recarregar dados
            if (!$error) {
                $stmt = $pdo->prepare("SELECT * FROM professores WHERE id = ?");
                $stmt->execute([$professor_id]);
                $professor = $stmt->fetch();
            }

        } catch (PDOException $e) {
            $error = 'Erro ao atualizar: ' . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-user-cog me-2"></i> Meu Perfil</h1>
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
                <h6 class="m-0 font-weight-bold text-primary">Informações do Professor</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="" enctype="multipart/form-data">
                    <h5 class="mb-3 text-primary">Dados Pessoais</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label for="nome" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($professor['nome']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($professor['email']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="telefone" class="form-label">Telefone (WhatsApp)</label>
                            <input type="text" class="form-control" id="telefone" name="telefone" value="<?php echo htmlspecialchars($professor['telefone']); ?>">
                        </div>
                    </div>

                    <h5 class="mb-3 text-primary">Dados de Recebimento</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label for="chave_pix" class="form-label">Chave PIX Padrão</label>
                            <input type="text" class="form-control" id="chave_pix" name="chave_pix" value="<?php echo htmlspecialchars($professor['chave_pix']); ?>" placeholder="CPF, Email, Telefone ou Aleatória">
                            <div class="form-text">Usada para exibir nos lembretes de cobrança.</div>
                        </div>
                    </div>
                    
                    <h5 class="mb-3 text-primary">Links Públicos</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label">Link para Agendamento (Aula Experimental)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="http://localhost/promestre/agendar.php?p=<?php echo $professor['slug']; ?>" readonly id="linkAgendar">
                                <button class="btn btn-outline-secondary" type="button" onclick="copiarLink('linkAgendar')"><i class="fas fa-copy"></i></button>
                                <a href="agendar.php?p=<?php echo $professor['slug']; ?>" target="_blank" class="btn btn-outline-primary"><i class="fas fa-external-link-alt"></i></a>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Link para Pré-Matrícula</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="http://localhost/promestre/matricula.php?p=<?php echo $professor['slug']; ?>" readonly id="linkMatricula">
                                <button class="btn btn-outline-secondary" type="button" onclick="copiarLink('linkMatricula')"><i class="fas fa-copy"></i></button>
                                <a href="matricula.php?p=<?php echo $professor['slug']; ?>" target="_blank" class="btn btn-outline-primary"><i class="fas fa-external-link-alt"></i></a>
                            </div>
                        </div>
                    </div>

                    <h5 class="mb-3 text-primary">Integração EfiBank (Opcional)</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="client_id_efi" class="form-label">Client ID (Prod)</label>
                            <input type="text" class="form-control" id="client_id_efi" name="client_id_efi" value="<?php echo htmlspecialchars($professor['client_id_efi'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="client_secret_efi" class="form-label">Client Secret (Prod)</label>
                            <input type="password" class="form-control" id="client_secret_efi" name="client_secret_efi" value="<?php echo htmlspecialchars($professor['client_secret_efi'] ?? ''); ?>">
                        </div>
                        <div class="col-md-12">
                            <label for="certificado_efi" class="form-label">Certificado (.p12 ou .pem)</label>
                            <input type="file" class="form-control" id="certificado_efi" name="certificado_efi" accept=".p12,.pem">
                            <?php if (!empty($professor['certificado_efi'])): ?>
                                <div class="form-text text-success"><i class="fas fa-check-circle"></i> Certificado já enviado.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <h5 class="mb-3 text-primary">Alterar Senha <small class="text-muted fw-normal">(Deixe em branco para não alterar)</small></h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="nova_senha" class="form-label">Nova Senha</label>
                            <input type="password" class="form-control" id="nova_senha" name="nova_senha">
                        </div>
                        <div class="col-md-6">
                            <label for="confirma_senha" class="form-label">Confirmar Nova Senha</label>
                            <input type="password" class="form-control" id="confirma_senha" name="confirma_senha">
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary px-4">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
