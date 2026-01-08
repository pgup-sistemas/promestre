<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Integração Completo - Promestre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-dark text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-rocket me-2"></i>
                            Teste de Integração Completo - Promestre
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php
                        if (!defined('FORCE_DISPLAY_ERRORS')) define('FORCE_DISPLAY_ERRORS', true);

                        require_once 'includes/config.php';
                        require_once 'includes/user_management.php';
                        
                        $integration_tests = [];
                        $passed = 0;
                        $failed = 0;
                        
                        // ========================================
                        // TESTE 1: Login do Administrador
                        // ========================================
                        try {
                            // Simular login do admin
                            $stmt = $pdo->prepare("SELECT * FROM professores WHERE email = 'admin@promestre.com'");
                            $stmt->execute();
                            $admin = $stmt->fetch();
                            
                            if ($admin && password_verify('password', $admin['senha'])) {
                                // Simular sessão
                                $_SESSION['user_id'] = $admin['id'];
                                $_SESSION['user_name'] = $admin['nome'];
                                $_SESSION['user_email'] = $admin['email'];
                                $_SESSION['user_slug'] = $admin['slug'];
                                $_SESSION['user_nivel'] = $admin['nivel'];
                                
                                // Testar função isAdmin
                                $is_admin = isAdmin();
                                $integration_tests['Login Admin + Verificação'] = $is_admin ? 'success' : 'danger';
                                if ($is_admin) $passed++; else $failed++;
                                
                                // Registrar log
                                updateLastLogin($admin['id']);
                                logActivity('LOGIN', null, null, "Login do admin (teste)");
                                
                            } else {
                                $integration_tests['Login Admin + Verificação'] = 'danger';
                                $failed++;
                            }
                        } catch (Exception $e) {
                            $integration_tests['Login Admin + Verificação'] = 'danger';
                            $failed++;
                        }
                        
                        // ========================================
                        // TESTE 2: Redirecionamento Dashboard
                        // ========================================
                        try {
                            // Testar lógica de redirecionamento
                            if (isAdmin()) {
                                $integration_tests['Redirecionamento Dashboard Admin'] = 'success';
                                $passed++;
                            } else {
                                $integration_tests['Redirecionamento Dashboard Admin'] = 'danger';
                                $failed++;
                            }
                        } catch (Exception $e) {
                            $integration_tests['Redirecionamento Dashboard Admin'] = 'danger';
                            $failed++;
                        }
                        
                        // ========================================
                        // TESTE 3: Estatísticas do Dashboard Admin
                        // ========================================
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) FROM professores WHERE ativo = TRUE AND nivel = 'professor'");
                            $total_professores = $stmt->fetchColumn();
                            
                            $stmt = $pdo->query("SELECT COUNT(*) FROM alunos WHERE status = 'ativo'");
                            $total_alunos = $stmt->fetchColumn();
                            
                            $integration_tests['Estatísticas Dashboard Admin'] = is_numeric($total_professores) && is_numeric($total_alunos) ? 'success' : 'danger';
                            if (is_numeric($total_professores) && is_numeric($total_alunos)) $passed++; else $failed++;
                        } catch (Exception $e) {
                            $integration_tests['Estatísticas Dashboard Admin'] = 'danger';
                            $failed++;
                        }
                        
                        // ========================================
                        // TESTE 4: Listagem de Usuários
                        // ========================================
                        try {
                            $users = getAllUsers(1, 20, '');
                            $integration_tests['Listagem de Usuários'] = is_array($users) ? 'success' : 'danger';
                            if (is_array($users)) $passed++; else $failed++;
                        } catch (Exception $e) {
                            $integration_tests['Listagem de Usuários'] = 'danger';
                            $failed++;
                        }
                        
                        // ========================================
                        // TESTE 5: Criação de Token de Reset
                        // ========================================
                        try {
                            if (isset($admin)) {
                                $token = generateResetToken($admin['id'], $admin['id']);
                                $integration_tests['Criação Token Reset'] = !empty($token) ? 'success' : 'danger';
                                if (!empty($token)) $passed++; else $failed++;
                            } else {
                                $integration_tests['Criação Token Reset'] = 'danger';
                                $failed++;
                            }
                        } catch (Exception $e) {
                            $integration_tests['Criação Token Reset'] = 'danger';
                            $failed++;
                        }
                        
                        // ========================================
                        // TESTE 6: Validação de Token
                        // ========================================
                        try {
                            if (isset($admin)) {
                                $token = generateResetToken($admin['id'], $admin['id']);
                                $validation = validateResetToken($token);
                                $integration_tests['Validação Token Reset'] = $validation ? 'success' : 'danger';
                                if ($validation) $passed++; else $failed++;
                            } else {
                                $integration_tests['Validação Token Reset'] = 'danger';
                                $failed++;
                            }
                        } catch (Exception $e) {
                            $integration_tests['Validação Token Reset'] = 'danger';
                            $failed++;
                        }
                        
                        // ========================================
                        // TESTE 7: Logs de Atividades
                        // ========================================
                        try {
                            $stmt = $pdo->prepare("
                                SELECT COUNT(*) as total 
                                FROM logs_atividades 
                                WHERE professor_id = ? 
                                AND data_criacao >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                            ");
                            $stmt->execute([isset($admin) ? $admin['id'] : 0]);
                            $recent_logs = $stmt->fetch();
                            
                            $integration_tests['Logs de Atividades Recentes'] = $recent_logs && $recent_logs['total'] > 0 ? 'success' : 'warning';
                            if ($recent_logs && $recent_logs['total'] > 0) $passed++; else $failed++;
                        } catch (Exception $e) {
                            $integration_tests['Logs de Atividades Recentes'] = 'danger';
                            $failed++;
                        }
                        
                        // ========================================
                        // TESTE 8: Separação de Dados
                        // ========================================
                        try {
                            // Verificar se professores só veem seus alunos
                            $stmt = $pdo->query("
                                SELECT p.nome as professor, COUNT(a.id) as total_alunos
                                FROM professores p
                                LEFT JOIN alunos a ON p.id = a.professor_id
                                WHERE p.nivel = 'professor'
                                GROUP BY p.id, p.nome
                                ORDER BY p.nome
                            ");
                            $professor_stats = $stmt->fetchAll();
                            
                            $integration_tests['Separação de Dados (Professor-Aluno)'] = is_array($professor_stats) ? 'success' : 'danger';
                            if (is_array($professor_stats)) $passed++; else $failed++;
                        } catch (Exception $e) {
                            $integration_tests['Separação de Dados (Professor-Aluno)'] = 'danger';
                            $failed++;
                        }
                        
                        // ========================================
                        // TESTE 9: Onboarding Logic
                        // ========================================
                        try {
                            // Simular primeiro acesso
                            unset($_SESSION['onboarding_completed']);
                            
                            // Verificar se redirecionaria para onboarding
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM alunos WHERE professor_id = ?");
                            $stmt->execute([isset($admin) ? $admin['id'] : 0]);
                            $alunos_count = $stmt->fetchColumn();
                            
                            $should_redirect = $alunos_count == 0;
                            $integration_tests['Lógica Onboarding'] = $should_redirect ? 'success' : 'warning';
                            if ($should_redirect) $passed++; else $failed++;
                        } catch (Exception $e) {
                            $integration_tests['Lógica Onboarding'] = 'danger';
                            $failed++;
                        }
                        
                        // ========================================
                        // TESTE 10: Arquivos Essenciais
                        // ========================================
                        try {
                            $essential_files = [
                                'gestao_usuarios.php',
                                'dashboard_admin.php',
                                'onboarding.php',
                                'onboarding_admin.php',
                                'admin_reset_senha.php',
                                'includes/user_management.php'
                            ];
                            
                            $files_exist = true;
                            foreach ($essential_files as $file) {
                                if (!file_exists($file)) {
                                    $files_exist = false;
                                    break;
                                }
                            }
                            
                            $integration_tests['Arquivos Essenciais'] = $files_exist ? 'success' : 'danger';
                            if ($files_exist) $passed++; else $failed++;
                        } catch (Exception $e) {
                            $integration_tests['Arquivos Essenciais'] = 'danger';
                            $failed++;
                        }
                        
                        $total = $passed + $failed;
                        $percent = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
                        ?>
                        
                        <!-- Status Geral -->
                        <div class="alert alert-<?php echo $percent >= 80 ? 'success' : ($percent >= 60 ? 'warning' : 'danger'); ?> text-center">
                            <h4 class="mb-2">
                                <?php if ($percent >= 80): ?>
                                    <i class="fas fa-check-circle me-2"></i>Sistema Pronto para Produção!
                                <?php elseif ($percent >= 60): ?>
                                    <i class="fas fa-exclamation-triangle me-2"></i>Atenção: Alguns Testes Falharam
                                <?php else: ?>
                                    <i class="fas fa-times-circle me-2"></i>Problemas Críticos Encontrados
                                <?php endif; ?>
                            </h4>
                            <p class="mb-0">Taxa de Sucesso: <strong><?php echo $percent; ?>%</strong> (<?php echo $passed; ?>/<?php echo $total; ?> testes)</p>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="mb-4">
                            <div class="progress" style="height: 30px;">
                                <div class="progress-bar bg-<?php echo $percent >= 80 ? 'success' : ($percent >= 60 ? 'warning' : 'danger'); ?>" 
                                     role="progressbar" style="width: <?php echo $percent; ?>%">
                                    <strong><?php echo $percent; ?>% Integrado</strong>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Resultados Detalhados -->
                        <h5 class="mb-3">Resultados dos Testes de Integração:</h5>
                        <div class="list-group">
                            <?php foreach ($integration_tests as $test => $status): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <?php if ($status == 'success'): ?>
                                            <i class="fas fa-check-circle text-success me-3 fa-lg"></i>
                                        <?php elseif ($status == 'warning'): ?>
                                            <i class="fas fa-exclamation-triangle text-warning me-3 fa-lg"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle text-danger me-3 fa-lg"></i>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($test); ?></strong>
                                            <?php if ($status == 'warning'): ?>
                                                <small class="text-muted d-block">Pode não ser crítico, mas verifique</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="badge bg-<?php echo $status; ?> fs-6">
                                        <?php 
                                        echo $status == 'success' ? 'OK' : 
                                             ($status == 'warning' ? 'AVISO' : 'FALHOU'); 
                                        ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Informações do Sistema -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="fas fa-server me-2"></i>Informações do Sistema</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6"><strong>PHP Version:</strong></div>
                                            <div class="col-6"><?php echo PHP_VERSION; ?></div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6"><strong>Database:</strong></div>
                                            <div class="col-6"><?php echo defined('DB_NAME') ? DB_NAME : 'Não definido'; ?></div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6"><strong>Environment:</strong></div>
                                            <div class="col-6"><?php echo defined('EFI_ENV') ? EFI_ENV : 'Não definido'; ?></div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6"><strong>Site URL:</strong></div>
                                            <div class="col-6"><?php echo defined('SITE_URL') ? SITE_URL : 'Não definido'; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="fas fa-users me-2"></i>Estatísticas de Usuários</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php
                                        try {
                                            $stmt = $pdo->query("SELECT COUNT(*) FROM professores WHERE nivel = 'admin'");
                                            $admins = $stmt->fetchColumn();
                                            
                                            $stmt = $pdo->query("SELECT COUNT(*) FROM professores WHERE nivel = 'professor'");
                                            $professores = $stmt->fetchColumn();
                                            
                                            $stmt = $pdo->query("SELECT COUNT(*) FROM alunos");
                                            $alunos = $stmt->fetchColumn();
                                        } catch (Exception $e) {
                                            $admins = $professores = $alunos = 'Erro';
                                        }
                                        ?>
                                        <div class="row">
                                            <div class="col-6"><strong>Administradores:</strong></div>
                                            <div class="col-6"><?php echo $admins; ?></div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6"><strong>Professores:</strong></div>
                                            <div class="col-6"><?php echo $professores; ?></div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6"><strong>Alunos:</strong></div>
                                            <div class="col-6"><?php echo $alunos; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ações Recomendadas -->
                        <div class="mt-4">
                            <h5>🚀 Próximos Passos:</h5>
                            <?php if ($percent >= 80): ?>
                                <div class="alert alert-success">
                                    <h6><i class="fas fa-rocket me-2"></i>Pronto para Produção!</h6>
                                    <ol class="mb-0">
                                        <li>Execute <code>database_updates_gestao_usuarios.sql</code> no banco de dados</li>
                                        <li>Envie todos os arquivos atualizados para o servidor</li>
                                        <li>Teste o login com admin@promestre.com / password</li>
                                        <li>Verifique o dashboard administrativo</li>
                                        <li>Teste a gestão de usuários</li>
                                    </ol>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-tools me-2"></i>Correções Necessárias:</h6>
                                    <ol class="mb-0">
                                        <li>Verifique os testes que falharam</li>
                                        <li>Execute os scripts SQL necessários</li>
                                        <li>Verifique a configuração do banco de dados</li>
                                        <li>Confirme a existência dos arquivos essenciais</li>
                                        <li>Execute os testes novamente após as correções</li>
                                    </ol>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Links de Teste -->
                        <div class="mt-4">
                            <h5>🔗 Links para Teste Manual:</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="d-grid gap-2">
                                        <a href="index.php" class="btn btn-primary" target="_blank">
                                            <i class="fas fa-sign-in-alt me-2"></i>Login (admin@promestre.com)
                                        </a>
                                        <a href="dashboard_admin.php" class="btn btn-danger" target="_blank">
                                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard Admin
                                        </a>
                                        <a href="gestao_usuarios.php" class="btn btn-warning" target="_blank">
                                            <i class="fas fa-users-cog me-2"></i>Gestão de Usuários
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-grid gap-2">
                                        <a href="onboarding.php" class="btn btn-info" target="_blank">
                                            <i class="fas fa-graduation-cap me-2"></i>Onboarding Professor
                                        </a>
                                        <a href="onboarding_admin.php" class="btn btn-secondary" target="_blank">
                                            <i class="fas fa-shield-alt me-2"></i>Onboarding Admin
                                        </a>
                                        <a href="admin_reset_senha.php" class="btn btn-outline-primary" target="_blank">
                                            <i class="fas fa-key me-2"></i>Reset de Senha
                                        </a>
                                    </div>
                                </div>
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
