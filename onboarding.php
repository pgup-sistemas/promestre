<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Como Funciona';
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-info-circle me-2"></i> Como Funciona o Promestre</h1>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-body p-5">
                <div class="text-center mb-5">
                    <h2 class="text-primary mb-3">Bem-vindo ao Promestre!</h2>
                    <p class="lead text-muted">Seu sistema completo para gestão de aulas e alunos.</p>
                    <p>Siga o passo a passo abaixo para configurar e aproveitar ao máximo todas as funcionalidades.</p>
                </div>

                <div class="row g-4">
                    <!-- Passo 1 -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; font-size: 24px;">
                                    1
                                </div>
                                <h4 class="card-title">Tipos de Aula</h4>
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
                                <h4 class="card-title">Cadastrar Alunos</h4>
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
                                <h4 class="card-title">Agenda</h4>
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
                                <h4 class="card-title">Financeiro</h4>
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
                                <h4 class="card-title">Receba com PIX</h4>
                                <p class="card-text text-muted">Configure suas credenciais da EfiBank no seu perfil para gerar QR Codes PIX automaticamente para seus alunos.</p>
                                <a href="perfil.php" class="btn btn-outline-warning mt-2">Configurar PIX</a>
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
                                <h4 class="card-title">Link de Agendamento</h4>
                                <p class="card-text text-muted">Compartilhe seu link público para que novos alunos solicitem agendamentos diretamente pelo WhatsApp.</p>
                                <a href="agendar.php?p=<?php echo $_SESSION['user_slug'] ?? ''; ?>" target="_blank" class="btn btn-outline-info mt-2">Testar Link</a>
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
