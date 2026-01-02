<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$professor_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM professores WHERE id = ?");
$stmt->execute([$professor_id]);
$professor = $stmt->fetch();

$hasPixKey = !empty($professor['chave_pix']);
$hasPixCreds = !empty($professor['client_id_efi']) && !empty($professor['client_secret_efi']);
$hasPixCert = !empty($professor['certificado_efi']) && file_exists($professor['certificado_efi']);

$hasSaasCreds = (defined('EFI_CHARGES_CLIENT_ID') && EFI_CHARGES_CLIENT_ID) && (defined('EFI_CHARGES_CLIENT_SECRET') && EFI_CHARGES_CLIENT_SECRET);

$page_title = 'Onboarding';
require_once 'includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
    <h1 class="h4 mb-0"><i class="fas fa-info-circle me-2"></i> Primeiros Passos</h1>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-body p-5">
                <div class="text-center mb-5">
                    <h3 class="text-primary mb-3">Bem-vindo ao Promestre!</h3>
                    <p class="lead text-muted">Checklist rápido para você configurar tudo e começar a usar.</p>
                    <p class="mb-0">Dica: existem 2 contextos diferentes de credenciais Efí: <strong>receber dos alunos (por professor)</strong> e <strong>assinatura SaaS do sistema</strong>.</p>
                </div>

                <div class="alert alert-light border d-flex justify-content-between align-items-center mb-4" role="alert">
                    <div>
                        <div class="fw-bold">Status da sua conta</div>
                        <div class="small text-muted">PIX (Perfil):
                            <?php echo ($hasPixKey && $hasPixCreds && $hasPixCert) ? '<span class="badge bg-success">Configurado</span>' : '<span class="badge bg-warning text-dark">Pendente</span>'; ?>
                            &nbsp;|&nbsp; Assinatura SaaS (.env):
                            <?php echo $hasSaasCreds ? '<span class="badge bg-success">Configurado</span>' : '<span class="badge bg-warning text-dark">Pendente</span>'; ?>
                        </div>
                    </div>
                    <a href="perfil.php" class="btn btn-outline-primary btn-sm">Abrir Meu Perfil</a>
                </div>

                <div class="row g-4">
                    <!-- Passo 1 -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; font-size: 24px;">
                                    1
                                </div>
                                <h6 class="card-title">Tipos de Aula</h6>
                                <p class="card-text text-muted">Comece definindo o que você ensina. Cadastre os tipos de aula, definindo nome, cor e valor mensal.</p>
                                <a href="tipos_aula.php" class="btn btn-outline-primary mt-2">Configurar Aulas</a>
                            </div>
                        </div>
                    </div>

                    <!-- Passo 2 -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; font-size: 24px;">
                                    2
                                </div>
                                <h6 class="card-title">Cadastrar Alunos</h6>
                                <p class="card-text text-muted">Adicione seus alunos e vincule-os a um tipo de aula. Aqui você mantém os dados de contato organizados.</p>
                                <a href="alunos.php" class="btn btn-outline-primary mt-2">Adicionar Alunos</a>
                            </div>
                        </div>
                    </div>

                    <!-- Passo 3 -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; font-size: 24px;">
                                    3
                                </div>
                                <h6 class="card-title">Agenda</h6>
                                <p class="card-text text-muted">Marque as aulas no calendário. Você pode controlar presenças e visualizar seus compromissos facilmente.</p>
                                <a href="agenda.php" class="btn btn-outline-primary mt-2">Ver Agenda</a>
                            </div>
                        </div>
                    </div>

                    <!-- Passo 4 -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; font-size: 24px;">
                                    4
                                </div>
                                <h6 class="card-title">Financeiro</h6>
                                <p class="card-text text-muted">Gere mensalidades e controle quem já pagou. O sistema ajuda você a não perder nenhum recebimento.</p>
                                <a href="mensalidades.php" class="btn btn-outline-success mt-2">Gerenciar Financeiro</a>
                            </div>
                        </div>
                    </div>

                    <!-- Passo 5 -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="rounded-circle bg-warning text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; font-size: 24px;">
                                    5
                                </div>
                                <h6 class="card-title">Receba com PIX</h6>
                                <p class="card-text text-muted">Configure no seu perfil: chave PIX, credenciais Efí e certificado (.p12/.pem) para gerar cobranças automaticamente.</p>
                                <div class="mb-2">
                                    <?php echo ($hasPixKey && $hasPixCreds && $hasPixCert) ? '<span class="badge bg-success">Configurado</span>' : '<span class="badge bg-warning text-dark">Pendente</span>'; ?>
                                </div>
                                <a href="perfil.php" class="btn btn-outline-warning mt-2">Configurar no Perfil</a>
                            </div>
                        </div>
                    </div>

                    <!-- Passo 6 -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="rounded-circle bg-info text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; font-size: 24px;">
                                    6
                                </div>
                                <h6 class="card-title">Link de Agendamento</h6>
                                <p class="card-text text-muted">Compartilhe seu link público para que novos alunos solicitem agendamentos diretamente pelo WhatsApp.</p>
                                <a href="agendar.php?p=<?php echo $_SESSION['user_slug'] ?? ''; ?>" target="_blank" class="btn btn-outline-info mt-2">Testar Link</a>
                            </div>
                        </div>
                    </div>

                    <!-- Passo 7 -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="rounded-circle bg-dark text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; font-size: 24px;">
                                    7
                                </div>
                                <h6 class="card-title">Assinatura do Sistema</h6>
                                <p class="card-text text-muted">Assinatura SaaS do Promestre (cobrança do sistema para o professor). Depende das credenciais globais no <code>.env</code>.</p>
                                <div class="mb-2">
                                    <?php echo $hasSaasCreds ? '<span class="badge bg-success">Configurado</span>' : '<span class="badge bg-warning text-dark">Pendente</span>'; ?>
                                </div>
                                <a href="assinatura_sistema.php" class="btn btn-outline-dark mt-2">Abrir Assinatura</a>
                            </div>
                        </div>
                    </div>

                    <!-- Passo 8 -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; font-size: 24px;">
                                    8
                                </div>
                                <h6 class="card-title">Modelo de Contrato</h6>
                                <p class="card-text text-muted">Defina um modelo e gere contratos automaticamente para seus alunos substituindo variáveis.</p>
                                <a href="contratos_config.php" class="btn btn-outline-secondary mt-2">Configurar Contrato</a>
                            </div>
                        </div>
                    </div>

                    <!-- Passo 9 -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; font-size: 24px;">
                                    9
                                </div>
                                <h6 class="card-title">Webhooks</h6>
                                <p class="card-text text-muted">Para atualizar pagamentos automaticamente, configure a URL e o segredo do webhook no <code>.env</code>. Em localhost, use túnel (ngrok/cloudflared) para receber chamadas externas.</p>
                                <a href="README.md" class="btn btn-outline-primary mt-2">Ver Documentação</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-5">
                    <a href="dashboard.php" class="btn btn-primary btn-lg px-5">Ir para o Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
