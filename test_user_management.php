<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Gestão de Usuários - Promestre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-users-cog me-2"></i>
                            Teste de Gestão de Usuários - Promestre
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
                        
                        // Teste 1: Verificar se admin existe
                        try {
                            $stmt = $pdo->prepare("SELECT id, nome, email, senha, nivel, ativo FROM professores WHERE email = 'admin@promestre.com'");
                            $stmt->execute();
                            $admin = $stmt->fetch();
                            
                            if ($admin && $admin['nivel'] === 'admin') {
                                $tests['Admin padrão existe'] = 'success';
                                $passed++;
                            } else {
                                $tests['Admin padrão existe'] = 'danger';
                                $failed++;
                            }
                        } catch (Exception $e) {
                            $tests['Admin padrão existe'] = 'danger';
                            $failed++;
                        }
                        
                        // Teste 2: Verificar senha do admin
                        if (isset($admin)) {
                            if (!empty($admin['senha']) && password_verify('password', $admin['senha'])) {
                                $tests['Senha admin válida'] = 'success';
                                $passed++;
                            } else {
                                $tests['Senha admin válida'] = 'danger';
                                $failed++;
                            }
                        } else {
                            $tests['Senha admin válida'] = 'danger';
                            $failed++;
                        }
                        
                        // Teste 3: Função de log de atividade
                        try {
                            $before = 0;
                            try {
                                $stmt = $pdo->query("SELECT COUNT(*) FROM logs_atividades");
                                $before = (int)$stmt->fetchColumn();
                            } catch (Exception $e) {
                                $before = 0;
                            }

                            logActivity('TEST', 'professores', 1, 'Teste automatizado');

                            $after = $before;
                            try {
                                $stmt = $pdo->query("SELECT COUNT(*) FROM logs_atividades");
                                $after = (int)$stmt->fetchColumn();
                            } catch (Exception $e) {
                                $after = $before;
                            }

                            // Passa se a tabela existe e o contador aumentou
                            $ok = ($after > $before);
                            $tests['Log de atividade'] = $ok ? 'success' : 'danger';
                            if ($ok) $passed++; else $failed++;
                        } catch (Exception $e) {
                            $tests['Log de atividade'] = 'danger';
                            $failed++;
                        }
                        
                        // Teste 4: Geração de token de reset
                        try {
                            if (isset($admin)) {
                                $token = generateResetToken($admin['id'], $admin['id']);
                                $tests['Geração de token reset'] = !empty($token) ? 'success' : 'danger';
                                if (!empty($token)) $passed++; else $failed++;
                            } else {
                                $tests['Geração de token reset'] = 'danger';
                                $failed++;
                            }
                        } catch (Exception $e) {
                            $tests['Geração de token reset'] = 'danger';
                            $failed++;
                        }
                        
                        // Teste 5: Validação de token
                        try {
                            if (isset($admin)) {
                                $token = generateResetToken($admin['id'], $admin['id']);
                                $validation = validateResetToken($token);
                                $tests['Validação de token'] = $validation ? 'success' : 'danger';
                                if ($validation) $passed++; else $failed++;
                            } else {
                                $tests['Validação de token'] = 'danger';
                                $failed++;
                            }
                        } catch (Exception $e) {
                            $tests['Validação de token'] = 'danger';
                            $failed++;
                        }
                        
                        // Teste 6: Listagem de usuários
                        try {
                            $users = getAllUsers(1, 20, '');
                            $tests['Listagem de usuários'] = is_array($users) ? 'success' : 'danger';
                            if (is_array($users)) $passed++; else $failed++;
                        } catch (Exception $e) {
                            $tests['Listagem de usuários'] = 'danger';
                            $failed++;
                        }
                        
                        // Teste 7: Contagem de usuários
                        try {
                            $count = countUsers();
                            $tests['Contagem de usuários'] = is_numeric($count) ? 'success' : 'danger';
                            if (is_numeric($count)) $passed++; else $failed++;
                        } catch (Exception $e) {
                            $tests['Contagem de usuários'] = 'danger';
                            $failed++;
                        }
                        
                        // Teste 8: Obter usuário por ID
                        try {
                            if (isset($admin)) {
                                $user = getUserById($admin['id']);
                                $tests['Obter usuário por ID'] = $user && $user['id'] == $admin['id'] ? 'success' : 'danger';
                                if ($user && $user['id'] == $admin['id']) $passed++; else $failed++;
                            } else {
                                $tests['Obter usuário por ID'] = 'danger';
                                $failed++;
                            }
                        } catch (Exception $e) {
                            $tests['Obter usuário por ID'] = 'danger';
                            $failed++;
                        }
                        
                        // Teste 9: Verificar separação de dados
                        try {
                            $stmt = $pdo->query("
                                SELECT COUNT(*) as total_professores,
                                       COUNT(CASE WHEN nivel = 'admin' THEN 1 END) as total_admins,
                                       COUNT(CASE WHEN nivel = 'professor' THEN 1 END) as total_professores_tipo
                                FROM professores
                            ");
                            $stats = $stmt->fetch();
                            $tests['Estatísticas de usuários'] = $stats ? 'success' : 'danger';
                            if ($stats) $passed++; else $failed++;
                        } catch (Exception $e) {
                            $tests['Estatísticas de usuários'] = 'danger';
                            $failed++;
                        }
                        
                        // Teste 10: Verificar logs recentes
                        try {
                            $stmt = $pdo->query("
                                SELECT COUNT(*) as total 
                                FROM logs_atividades 
                                WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                            ");
                            $recent_logs = $stmt->fetch();
                            $tests['Logs recentes (1h)'] = $recent_logs ? 'success' : 'warning';
                            if ($recent_logs) $passed++; else $failed++;
                        } catch (Exception $e) {
                            $tests['Logs recentes (1h)'] = 'warning';
                            $failed++;
                        }
                        
                        $total = $passed + $failed;
                        $percent = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
                        ?>
                        
                        <!-- Resumo -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h2 class="text-danger"><?php echo $total; ?></h2>
                                        <p class="mb-0">Testes Gestão</p>
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
                                        <p class="mb-0">Taxa Sucesso</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="mb-4">
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $percent; ?>%">
                                    <?php echo $percent; ?>% Testado
                                </div>
                            </div>
                        </div>
                        
                        <!-- Resultados Detalhados -->
                        <h5 class="mb-3">Resultados dos Testes:</h5>
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
                        
                        <!-- Estatísticas Atuais -->
                        <?php if (isset($stats)): ?>
                        <div class="mt-4">
                            <h5>Estatísticas Atuais do Sistema:</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h4 class="text-primary"><?php echo $stats['total_professores']; ?></h4>
                                            <p class="mb-0">Total de Professores</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h4 class="text-danger"><?php echo $stats['total_admins']; ?></h4>
                                            <p class="mb-0">Administradores</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h4 class="text-info"><?php echo $stats['total_professores_tipo']; ?></h4>
                                            <p class="mb-0">Professores</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Informações do Admin -->
                        <?php if (isset($admin)): ?>
                        <div class="mt-4">
                            <h5>Usuário Administrador Padrão:</h5>
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3"><strong>ID:</strong></div>
                                        <div class="col-md-9"><?php echo $admin['id']; ?></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3"><strong>Nome:</strong></div>
                                        <div class="col-md-9"><?php echo htmlspecialchars($admin['nome']); ?></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3"><strong>Email:</strong></div>
                                        <div class="col-md-9"><?php echo htmlspecialchars($admin['email']); ?></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3"><strong>Nível:</strong></div>
                                        <div class="col-md-9">
                                            <span class="badge bg-danger"><?php echo ucfirst($admin['nivel']); ?></span>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3"><strong>Status:</strong></div>
                                        <div class="col-md-9">
                                            <span class="badge bg-<?php echo $admin['ativo'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $admin['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Ações de Teste -->
                        <div class="mt-4">
                            <h5>Ações de Teste:</h5>
                            <div class="btn-group-vertical d-grid gap-2">
                                <a href="index.php" class="btn btn-primary" target="_blank">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Testar Login (admin@promestre.com / password)
                                </a>
                                <a href="dashboard_admin.php" class="btn btn-danger" target="_blank">
                                    <i class="fas fa-tachometer-alt me-2"></i>
                                    Testar Dashboard Admin
                                </a>
                                <a href="gestao_usuarios.php" class="btn btn-warning" target="_blank">
                                    <i class="fas fa-users-cog me-2"></i>
                                    Testar Gestão de Usuários
                                </a>
                                <a href="admin_reset_senha.php" class="btn btn-info" target="_blank">
                                    <i class="fas fa-key me-2"></i>
                                    Testar Reset de Senha
                                </a>
                            </div>
                        </div>
                        
                        <!-- Checklist para Produção -->
                        <div class="mt-4">
                            <h5>✅ Checklist para Produção:</h5>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="check1">
                                <label class="form-check-label" for="check1">
                                    Executar <code>database_updates_gestao_usuarios.sql</code> no banco
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="check2">
                                <label class="form-check-label" for="check2">
                                    Enviar arquivos PHP atualizados para o servidor
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="check3">
                                <label class="form-check-label" for="check3">
                                    Testar login com admin@promestre.com
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="check4">
                                <label class="form-check-label" for="check4">
                                    Verificar dashboard administrativo
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="check5">
                                <label class="form-check-label" for="check5">
                                    Testar gestão de usuários
                                </label>
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
