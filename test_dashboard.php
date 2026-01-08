<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Funcionalidades - Promestre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-vial me-2"></i>
                            Teste de Funcionalidades - Promestre
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php
                        if (!defined('FORCE_DISPLAY_ERRORS')) define('FORCE_DISPLAY_ERRORS', true);

                        require_once 'includes/config.php';
                        require_once 'includes/user_management.php';
                        
                        $tests = [];
                        $passed = 0;
                        $failed = 0;
                        
                        // Teste 1: Conexão com banco
                        try {
                            $stmt = $pdo->query("SELECT 1");
                            $tests['Conexão com Banco'] = $stmt ? 'success' : 'danger';
                            if ($stmt) $passed++; else $failed++;
                        } catch (Exception $e) {
                            $tests['Conexão com Banco'] = 'danger';
                            $failed++;
                        }
                        
                        // Teste 2: Tabela professores
                        try {
                            $stmt = $pdo->query("DESCRIBE professores");
                            $tests['Tabela professores'] = $stmt ? 'success' : 'danger';
                            if ($stmt) $passed++; else $failed++;
                        } catch (Exception $e) {
                            $tests['Tabela professores'] = 'danger';
                            $failed++;
                        }
                        
                        // Teste 3: Tabela alunos
                        try {
                            $stmt = $pdo->query("DESCRIBE alunos");
                            $tests['Tabela alunos'] = $stmt ? 'success' : 'danger';
                            if ($stmt) $passed++; else $failed++;
                        } catch (Exception $e) {
                            $tests['Tabela alunos'] = 'danger';
                            $failed++;
                        }
                        
                        // Teste 4: Campo nivel
                        try {
                            $stmt = $pdo->query("SHOW COLUMNS FROM professores LIKE 'nivel'");
                            $tests['Campo nivel (professores)'] = $stmt->rowCount() > 0 ? 'success' : 'danger';
                            if ($stmt->rowCount() > 0) $passed++; else $failed++;
                        } catch (Exception $e) {
                            $tests['Campo nivel (professores)'] = 'danger';
                            $failed++;
                        }
                        
                        // Teste 5: Campo ativo
                        try {
                            $stmt = $pdo->query("SHOW COLUMNS FROM professores LIKE 'ativo'");
                            $tests['Campo ativo (professores)'] = $stmt->rowCount() > 0 ? 'success' : 'danger';
                            if ($stmt->rowCount() > 0) $passed++; else $failed++;
                        } catch (Exception $e) {
                            $tests['Campo ativo (professores)'] = 'danger';
                            $failed++;
                        }
                        
                        // Teste 6: Tabela logs_atividades
                        try {
                            $stmt = $pdo->query("DESCRIBE logs_atividades");
                            $tests['Tabela logs_atividades'] = $stmt ? 'success' : 'warning';
                            if ($stmt) $passed++; else $failed++;
                        } catch (Exception $e) {
                            $tests['Tabela logs_atividades'] = 'warning';
                            $failed++;
                        }
                        
                        // Teste 7: Tabela reset_tokens
                        try {
                            $stmt = $pdo->query("DESCRIBE reset_tokens");
                            $tests['Tabela reset_tokens'] = $stmt ? 'success' : 'warning';
                            if ($stmt) $passed++; else $failed++;
                        } catch (Exception $e) {
                            $tests['Tabela reset_tokens'] = 'warning';
                            $failed++;
                        }
                        
                        // Teste 8: Usuário admin
                        try {
                            $stmt = $pdo->prepare("SELECT id FROM professores WHERE email = 'admin@promestre.com' AND nivel = 'admin'");
                            $stmt->execute();
                            $tests['Usuário admin@promestre.com'] = $stmt->rowCount() > 0 ? 'success' : 'danger';
                            if ($stmt->rowCount() > 0) $passed++; else $failed++;
                        } catch (Exception $e) {
                            $tests['Usuário admin@promestre.com'] = 'danger';
                            $failed++;
                        }
                        
                        // Teste 9: Funções essenciais
                        $functions_test = function_exists('isLoggedIn') && function_exists('clean') && function_exists('formatMoney');
                        $tests['Funções essenciais'] = $functions_test ? 'success' : 'danger';
                        if ($functions_test) $passed++; else $failed++;
                        
                        // Teste 10: Arquivos de gestão
                        $files_test = file_exists('gestao_usuarios.php') && file_exists('dashboard_admin.php') && file_exists('onboarding.php');
                        $tests['Arquivos de gestão'] = $files_test ? 'success' : 'danger';
                        if ($files_test) $passed++; else $failed++;
                        
                        $total = $passed + $failed;
                        $percent = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
                        ?>
                        
                        <!-- Resumo -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h2 class="text-primary"><?php echo $total; ?></h2>
                                        <p class="mb-0">Total de Testes</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h2 class="text-success"><?php echo $passed; ?></h2>
                                        <p class="mb-0">Passaram</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h2 class="text-danger"><?php echo $failed; ?></h2>
                                        <p class="mb-0">Falharam</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h2 class="text-info"><?php echo $percent; ?>%</h2>
                                        <p class="mb-0">Taxa de Sucesso</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="mb-4">
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percent; ?>%">
                                    <?php echo $percent; ?>% Completo
                                </div>
                            </div>
                        </div>
                        
                        <!-- Resultados Detalhados -->
                        <h5 class="mb-3">Resultados Detalhados:</h5>
                        <div class="list-group">
                            <?php foreach ($tests as $test => $status): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <?php if ($status == 'success'): ?>
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                        <?php elseif ($status == 'warning'): ?>
                                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle text-danger me-2"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($test); ?>
                                    </span>
                                    <span class="badge bg-<?php echo $status; ?>">
                                        <?php 
                                        echo $status == 'success' ? 'OK' : 
                                             ($status == 'warning' ? 'AVISO' : 'FALHOU'); 
                                        ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Recomendações -->
                        <div class="mt-4">
                            <h5>Recomendações:</h5>
                            <?php if ($failed == 0): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Excelente!</strong> Todos os testes passaram. O sistema está pronto para produção.
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Atenção:</strong> Alguns testes falharam. Verifique os itens marcados em vermelho antes de subir para produção.
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Próximos Passos -->
                        <div class="mt-4">
                            <h5>Próximos Passos para Produção:</h5>
                            <ol>
                                <li>Execute o arquivo <code>database_updates_gestao_usuarios.sql</code> no banco de dados</li>
                                <li>Envie todos os arquivos PHP atualizados para o servidor</li>
                                <li>Teste o login com: admin@promestre.com / password</li>
                                <li>Verifique o dashboard administrativo</li>
                                <li>Teste a gestão de usuários</li>
                            </ol>
                        </div>
                        
                        <!-- Links Rápidos -->
                        <div class="mt-4">
                            <h5>Links para Teste:</h5>
                            <div class="btn-group" role="group">
                                <a href="index.php" class="btn btn-outline-primary" target="_blank">
                                    <i class="fas fa-sign-in-alt me-1"></i> Login
                                </a>
                                <a href="dashboard_admin.php" class="btn btn-outline-danger" target="_blank">
                                    <i class="fas fa-tachometer-alt me-1"></i> Dashboard Admin
                                </a>
                                <a href="gestao_usuarios.php" class="btn btn-outline-warning" target="_blank">
                                    <i class="fas fa-users-cog me-1"></i> Gestão
                                </a>
                                <a href="onboarding.php" class="btn btn-outline-info" target="_blank">
                                    <i class="fas fa-graduation-cap me-1"></i> Onboarding
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
