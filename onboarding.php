<?php
require_once 'includes/config.php';
require_once 'includes/user_management.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$page_title = isset($_GET['view']) ? 'Como Funciona - Guia do Sistema' : 'Bem-vindo ao Promestre';
$step = $_GET['step'] ?? 1;
$professor_id = $_SESSION['user_id'];

// Verificar se já completou onboarding
if (isset($_POST['complete_onboarding'])) {
    $_SESSION['onboarding_completed'] = true;
    logActivity('ONBOARDING_COMPLETE', null, null, "Onboarding concluído pelo professor");
    redirect('dashboard.php');
}

// Se já completou, permitir acesso se vier do menu "Como Funciona"
if (isset($_SESSION['onboarding_completed']) && !isset($_GET['view'])) {
    redirect('dashboard.php');
}

// Dados do professor
$stmt = $pdo->prepare("SELECT * FROM professores WHERE id = ?");
$stmt->execute([$professor_id]);
$professor = $stmt->fetch();

require_once 'includes/header.php';
?>

<style>
.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.feature-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    transition: transform 0.3s;
}

.feature-card:hover {
    transform: translateY(-5px);
}

.feature-icon {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 15px;
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Progress Bar Bootstrap -->
            <div class="progress mb-4" style="height: 30px;">
                <div class="progress-bar bg-primary" style="width: <?php echo ($step * 25); ?>%">
                    Passo <?php echo $step; ?> de 4
                </div>
                </div>

        <?php if ($step == 1): ?>
            <!-- Passo 1: Boas-vindas -->
            <div class="card shadow mb-4">
                <div class="card-body">
                <div class="text-center mb-4">
                    <i class="fas fa-graduation-cap feature-icon"></i>
                    <h2 class="mb-3">Bem-vindo ao Promestre!</h2>
                    <p class="text-muted">Olá, <strong><?php echo htmlspecialchars($professor['nome']); ?></strong>! Vamos te guiar pelos principais recursos do sistema.</p>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Este tutorial rápido levará apenas 2-3 minutos para completar.
                </div>

                <div class="text-center mt-4">
                    <h4 class="mb-3">O que você encontrará aqui:</h4>
                    <div class="feature-grid">
                        <div class="feature-card">
                            <i class="fas fa-users feature-icon"></i>
                            <h5>Gestão de Alunos</h5>
                            <p class="text-muted small">Cadastre e gerencie seus alunos</p>
                        </div>
                        <div class="feature-card">
                            <i class="fas fa-calendar feature-icon"></i>
                            <h5>Agendamento</h5>
                            <p class="text-muted small">Organize suas aulas facilmente</p>
                        </div>
                        <div class="feature-card">
                            <i class="fas fa-dollar-sign feature-icon"></i>
                            <h5>Mensalidades</h5>
                            <p class="text-muted small">Controle financeiro automatizado</p>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="dashboard.php" class="btn btn-secondary">Pular Tutorial</a>
                    <a href="?step=2" class="btn btn-primary">Próximo Passo <i class="fas fa-arrow-right ms-2"></i></a>
                </div>
                </div>

        <?php elseif ($step == 2): ?>
            <!-- Passo 2: Como adicionar alunos -->
            <div class="card shadow mb-4">
                <div class="card-body">
                <h3 class="mb-4"><i class="fas fa-users me-2"></i> Passo 2: Adicionando Seus Alunos</h3>
                
                <div class="row">
                    <div class="col-md-8">
                        <h5>Como cadastrar novos alunos:</h5>
                        <ol>
                            <li class="mb-3">
                                <strong>Acesse o menu "Alunos"</strong><br>
                                No menu lateral, clique em "Alunos" para ver sua lista.
                            </li>
                            <li class="mb-3">
                                <strong>Clique em "Novo Aluno"</strong><br>
                                No canto superior direito, clique no botão verde.
                            </li>
                            <li class="mb-3">
                                <strong>Preencha os dados</strong><br>
                                Nome, email, telefone, tipo de aula e valor da mensalidade.
                            </li>
                            <li class="mb-3">
                                <strong>Pré-matrícula automática</strong><br>
                                Alunos podem se cadastrar pelo seu link de agendamento!
                            </li>
                        </ol>

                        <div class="alert alert-success">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Dica:</strong> Seu link de agendamento é: 
                            <code>https://promestre.pageup.net.br/agendar.php?p=<?php echo $_SESSION['user_slug']; ?></code>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <img src="https://via.placeholder.com/300x200/009246/ffffff?text=Adicionar+Alunos" 
                                 class="img-fluid rounded" alt="Adicionar Alunos">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="?step=1" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Anterior</a>
                    <a href="?step=3" class="btn btn-primary">Próximo Passo <i class="fas fa-arrow-right ms-2"></i></a>
                </div>
                </div>

        <?php elseif ($step == 3): ?>
            <!-- Passo 3: Agendamento e Financeiro -->
            <div class="card shadow mb-4">
                <div class="card-body">
                <h3 class="mb-4"><i class="fas fa-calendar-dollar me-2"></i> Passo 3: Agendamento e Financeiro</h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-calendar-alt text-primary me-2"></i>Agendamento de Aulas</h5>
                        <ul>
                            <li>Agende aulas individuais ou em grupo</li>
                            <li>Defina recorrência semanal</li>
                            <li>Envie lembretes automáticos</li>
                            <li>Controle de presença online</li>
                        </ul>

                        <div class="mt-3">
                            <a href="agenda.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-external-link-alt me-1"></i> Ver Agenda
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-money-bill-wave text-success me-2"></i>Controle Financeiro</h5>
                        <ul>
                            <li>Mensalidades automáticas</li>
                            <li>Geração de boletos e PIX</li>
                            <li>Relatórios de receita</li>
                            <li>Controle de inadimplentes</li>
                        </ul>

                        <div class="mt-3">
                            <a href="mensalidades.php" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-external-link-alt me-1"></i> Ver Financeiro
                            </a>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning mt-4">
                    <i class="fas fa-crown me-2"></i>
                    <strong>Assinatura Ativa:</strong> Para usar recursos avançados (PIX, cartão, recorrência), 
                    mantenha sua assinatura do sistema ativa.
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="?step=2" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Anterior</a>
                    <a href="?step=4" class="btn btn-primary">Próximo Passo <i class="fas fa-arrow-right ms-2"></i></a>
                </div>
                </div>

        <?php elseif ($step == 4): ?>
            <!-- Passo 4: Recursos e Suporte -->
            <div class="card shadow mb-4">
                <div class="card-body">
                <h3 class="mb-4"><i class="fas fa-rocket me-2"></i> Passo 4: Recursos e Suporte</h3>
                
                <div class="text-center mb-4">
                    <h4>🎉 Parabéns! Você está pronto!</h4>
                    <p class="text-muted">Aqui estão os recursos finais que vão ajudar seu dia a dia:</p>
                </div>

                <div class="feature-grid">
                    <div class="feature-card">
                        <i class="fas fa-mobile-alt feature-icon"></i>
                        <h6>App Mobile</h6>
                        <p class="small">Acesse pelo celular</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-whatsapp feature-icon"></i>
                        <h6>Integração WhatsApp</h6>
                        <p class="small">Comunicação direta</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-chart-bar feature-icon"></i>
                        <h6>Relatórios</h6>
                        <p class="small">Análise completa</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-headset feature-icon"></i>
                        <h6>Suporte</h6>
                        <p class="small">Estamos aqui para ajudar</p>
                    </div>
                </div>

                <div class="alert alert-info mt-4">
                    <h5><i class="fas fa-question-circle me-2"></i> Precisa de ajuda?</h5>
                    <ul class="mb-0">
                        <li><strong>WhatsApp:</strong> (xx) xxxx-xxxx</li>
                        <li><strong>Email:</strong> suporte@promestre.com</li>
                        <li><strong>Central de Ajuda:</strong> <a href="#">Clique aqui</a></li>
                    </ul>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="?step=3" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Anterior</a>
                    <form method="POST" class="d-inline">
                        <button type="submit" name="complete_onboarding" class="btn btn-success">
                            <i class="fas fa-check me-2"></i> Começar a Usar!
                        </button>
                    </form>
                </div>
                </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
