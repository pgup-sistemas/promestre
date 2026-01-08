<?php
require_once 'includes/config.php';
require_once 'includes/user_management.php';

// Apenas admin pode acessar
requireAdmin();

$page_title = isset($_GET['view']) ? 'Como Funciona - Guia Administrativo' : 'Bem-vindo Administrador';
$step = $_GET['step'] ?? 1;
$professor_id = $_SESSION['user_id'];

// Verificar se já completou onboarding
if (isset($_POST['complete_onboarding'])) {
    $_SESSION['admin_onboarding_completed'] = true;
    logActivity('ADMIN_ONBOARDING_COMPLETE', null, null, "Onboarding admin concluído");
    redirect('dashboard_admin.php');
}

// Se já completou, permitir acesso se vier do menu "Como Funciona"
if (isset($_SESSION['admin_onboarding_completed']) && !isset($_GET['view'])) {
    redirect('dashboard_admin.php');
}

require_once 'includes/header.php';
?>

<style>
.onboarding-card {
    margin-bottom: 20px;
}

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
    color: #dc3545;
    margin-bottom: 15px;
}

.btn-skip {
    background: transparent;
    border: 2px solid #6c757d;
    color: #6c757d;
}

.btn-skip:hover {
    background: #6c757d;
    color: white;
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Progress Bar Bootstrap -->
            <div class="progress mb-4" style="height: 30px;">
                <div class="progress-bar bg-danger" style="width: <?php echo ($step * 25); ?>%">
                    Passo <?php echo $step; ?> de 4
                </div>
                </div>

        <?php if ($step == 1): ?>
            <!-- Passo 1: Boas-vindas Admin -->
            <div class="card shadow mb-4">
                <div class="card-body">
                <div class="text-center mb-4">
                    <i class="fas fa-shield-alt feature-icon"></i>
                    <h2 class="mb-3">Bem-vindo Administrador!</h2>
                    <p class="text-muted">Como gestor do sistema Promestre, você tem acesso completo para monitorar e dar suporte a todos os professores.</p>
                </div>

                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Responsabilidades do Administrador:</strong><br>
                    • Gestão de usuários e suporte<br>
                    • Monitoramento de pagamentos<br>
                    • Auditoria do sistema
                </div>

                <div class="text-center mt-4">
                    <h4 class="mb-3">Suas ferramentas de gestão:</h4>
                    <div class="feature-grid">
                        <div class="feature-card">
                            <i class="fas fa-users-cog feature-icon"></i>
                            <h5>Gestão de Usuários</h5>
                            <p class="text-muted small">Administre contas e senhas</p>
                        </div>
                        <div class="feature-card">
                            <i class="fas fa-chart-line feature-icon"></i>
                            <h5>Dashboard Completo</h5>
                            <p class="text-muted small">Visão geral do sistema</p>
                        </div>
                        <div class="feature-card">
                            <i class="fas fa-history feature-icon"></i>
                            <h5>Logs de Auditoria</h5>
                            <p class="text-muted small">Acompanhe todas as atividades</p>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="dashboard_admin.php" class="btn btn-secondary">Pular Tutorial</a>
                    <a href="?step=2" class="btn btn-danger">Próximo Passo <i class="fas fa-arrow-right ms-2"></i></a>
                </div>
                </div>

        <?php elseif ($step == 2): ?>
            <!-- Passo 2: Gestão de Usuários -->
            <div class="card shadow mb-4">
                <div class="card-body">
                <h3 class="mb-4"><i class="fas fa-users-cog me-2"></i> Passo 2: Gestão de Usuários</h3>
                
                <div class="row">
                    <div class="col-md-8">
                        <h5>Como gerenciar professores:</h5>
                        <ol>
                            <li class="mb-3">
                                <strong>Acesse "Gestão de Usuários"</strong><br>
                                No dashboard admin, clique no botão vermelho "Gestão de Usuários".
                            </li>
                            <li class="mb-3">
                                <strong>Visualize todos os professores</strong><br>
                                Filtre por nome, email ou status (ativo/inativo).
                            </li>
                            <li class="mb-3">
                                <strong>Ações disponíveis</strong><br>
                                • Editar dados do professor<br>
                                • Ativar/Desativar conta<br>
                                • Resetar senha (gera link temporário)<br>
                                • Excluir conta (soft delete)
                            </li>
                            <li class="mb-3">
                                <strong>Reset de Senha</strong><br>
                                Gera um link seguro de 2 horas para o professor redefinir sua senha.
                            </li>
                        </ol>

                        <div class="alert alert-success">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Dica:</strong> Todos os logs de atividades são registrados para auditoria completa!
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <img src="https://via.placeholder.com/300x200/dc3545/ffffff?text=Gestão+de+Usuários" 
                                 class="img-fluid rounded" alt="Gestão de Usuários">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="?step=1" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Anterior</a>
                    <a href="?step=3" class="btn btn-danger">Próximo Passo <i class="fas fa-arrow-right ms-2"></i></a>
                </div>
                </div>

        <?php elseif ($step == 3): ?>
            <!-- Passo 3: Monitoramento e Suporte -->
            <div class="card shadow mb-4">
                <div class="card-body">
                <h3 class="mb-4"><i class="fas fa-chart-line me-2"></i> Passo 3: Monitoramento e Suporte</h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-tachometer-alt text-danger me-2"></i>Dashboard Administrativo</h5>
                        <ul>
                            <li><strong>Professores Ativos:</strong> Total de contas ativas</li>
                            <li><strong>Alunos Totais:</strong> Todos os alunos do sistema</li>
                            <li><strong>Assinaturas Ativas:</strong> Professores com pagamento em dia</li>
                            <li><strong>Receita do Mês:</strong> Total faturado no mês</li>
                            <li><strong>Professores Inativos:</strong> Sem acesso há 30+ dias</li>
                        </ul>

                        <div class="mt-3">
                            <a href="dashboard_admin.php" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-external-link-alt me-1"></i> Ver Dashboard
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-headset text-warning me-2"></i>Suporte aos Professores</h5>
                        <ul>
                            <li><strong>Reset de Senha:</strong> Gere links temporários</li>
                            <li><strong>Ativação de Conta:</strong> Reative contas desativadas</li>
                            <li><strong>Verificação de Dados:</strong> Ajude com informações incorretas</li>
                            <li><strong>Problemas Técnicos:</strong> Auxilie com dificuldades</li>
                        </ul>

                        <div class="mt-3">
                            <a href="gestao_usuarios.php" class="btn btn-outline-warning btn-sm">
                                <i class="fas fa-external-link-alt me-1"></i> Gerenciar Usuários
                            </a>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Importante:</strong> Como administrador, você pode ver dados de TODOS os professores e alunos, mas cada professor só vê seus próprios dados.
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="?step=2" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Anterior</a>
                    <a href="?step=4" class="btn btn-danger">Próximo Passo <i class="fas fa-arrow-right ms-2"></i></a>
                </div>
                </div>

        <?php elseif ($step == 4): ?>
            <!-- Passo 4: Auditoria e Segurança -->
            <div class="card shadow mb-4">
                <div class="card-body">
                <h3 class="mb-4"><i class="fas fa-shield-alt me-2"></i> Passo 4: Auditoria e Segurança</h3>
                
                <div class="text-center mb-4">
                    <h4>🔒 Segurança e Transparência</h4>
                    <p class="text-muted">O sistema mantém registros completos de todas as atividades</p>
                </div>

                <div class="feature-grid">
                    <div class="feature-card">
                        <i class="fas fa-history feature-icon"></i>
                        <h6>Logs de Atividades</h6>
                        <p class="small">Registro completo de todas as ações no sistema</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-user-shield feature-icon"></i>
                        <h6>Controle de Acesso</h6>
                        <p class="small">Apenas administradores acessam dados globais</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-key feature-icon"></i>
                        <h6>Reset Seguro</h6>
                        <p class="small">Links temporários com validade de 2 horas</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-database feature-icon"></i>
                        <h6>Soft Delete</h6>
                        <p class="small">Dados excluídos podem ser recuperados</p>
                    </div>
                </div>

                <div class="alert alert-warning mt-4">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i> Boas Práticas de Segurança:</h5>
                    <ul class="mb-0">
                        <li>Nunca compartilhe suas credenciais de admin</li>
                        <li>Use senhas fortes e únicas</li>
                        <li>Monitore os logs de atividades regularmente</li>
                        <li>Verifique contas inativas periodicamente</li>
                        <li>Mantenha o sistema atualizado</li>
                    </ul>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="?step=3" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Anterior</a>
                    <form method="POST" class="d-inline">
                        <button type="submit" name="complete_onboarding" class="btn btn-success">
                            <i class="fas fa-check me-2"></i> Começar a Administrar!
                        </button>
                    </form>
                </div>
                </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
