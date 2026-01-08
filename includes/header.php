<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <!-- Meta Tags SEO -->
    <meta name="description" content="Promestre - Sistema de Gestão Escolar completo e eficiente. Gerencie alunos, matrículas, financeiro e muito mais.">
    <meta name="keywords" content="gestão escolar, sistema escolar, controle de alunos, mensalidades, boletim, diário de classe, promestre">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Promestre">
    <meta name="googlebot" content="index, follow">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        :root {
            --bs-primary: #009246;
            --bs-primary-rgb: 0, 146, 70;
        }
        
        /* Sidebar CSS extracted for functionality */
        #wrapper {
            overflow-x: visible;
            display: flex;
        }

        #sidebar-wrapper {
            min-height: 100vh;
            margin-left: -13rem;
            transition: margin .25s ease-out;
            width: 13rem;
            position: fixed;
            z-index: 1000;
            height: 100%;
            overflow-y: auto;
            overflow-x: visible;
            background-color: #fff;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
        }

        #sidebar-wrapper .sidebar-heading {
            padding: 0.875rem 1rem;
            font-size: 1.1rem;
        }

        #sidebar-wrapper .list-group-item {
            font-size: 0.85rem;
            padding: 0.75rem 0.5rem;
            overflow: visible;
            position: relative;
        }

        #sidebar-wrapper .list-group {
            width: 13rem;
            overflow: visible;
            min-width: 13rem;
        }

        #page-content-wrapper {
            width: 100%;
            transition: all .25s ease-out;
            min-width: 100vw;
        }

        /* Mobile Toggled State */
        #wrapper.toggled #sidebar-wrapper {
            margin-left: 0;
        }

        /* Desktop */
        @media (min-width: 768px) {
            #sidebar-wrapper {
                margin-left: 0;
                position: relative;
            }

            #page-content-wrapper {
                min-width: 0;
                width: auto;
                flex: 1;
            }

            #wrapper.toggled #sidebar-wrapper {
                margin-left: -13rem;
            }
        }

        /* Mobile Adjustments */
        @media (max-width: 767.98px) {
            #sidebar-wrapper {
                width: 80%;
                max-width: 250px;
                margin-left: -100%;
            }
            
            #wrapper.toggled #sidebar-wrapper {
                margin-left: 0;
                box-shadow: 5px 0 15px rgba(0,0,0,0.1);
            }
            
            #sidebar-wrapper .list-group {
                width: 100%;
            }

            #wrapper.toggled::after {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 900;
                animation: fadeIn 0.3s;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body class="bg-light">

<?php if (isLoggedIn()): ?>
    <?php
    // Count pending pre-registrations
    $stmt_pre = $pdo->prepare("SELECT COUNT(*) FROM alunos WHERE professor_id = ? AND status = 'inativo' AND observacoes LIKE '%Pré-matrícula realizada pelo site%'");
    $stmt_pre->execute([$_SESSION['user_id']]);
    $pending_pre_registrations = $stmt_pre->fetchColumn();
    ?>
    <div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <div class="bg-white border-end" id="sidebar-wrapper">
        <div class="sidebar-heading border-bottom bg-primary text-white">
            <div class="d-flex align-items-center">
                <i class="fas fa-graduation-cap me-2 fa-lg"></i>
                <span class="fw-bold"><?php echo SITE_NAME; ?></span>
            </div>
        </div>
        <div class="list-group list-group-flush">
            <?php if (isset($_SESSION['user_nivel']) && $_SESSION['user_nivel'] === 'admin'): ?>
                <!-- Menu Admin -->
                <a href="<?php echo SITE_URL; ?>/dashboard_admin.php" class="list-group-item list-group-item-action list-group-item-light px-2 py-2 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard_admin.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-2 fixed-width-icon"></i> Dashboard Admin
                </a>
                <a href="<?php echo SITE_URL; ?>/gestao_usuarios.php" class="list-group-item list-group-item-action list-group-item-light px-2 py-2 <?php echo basename($_SERVER['PHP_SELF']) == 'gestao_usuarios.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog me-2 fixed-width-icon"></i> Gestão de Usuários
                </a>
                <a href="<?php echo SITE_URL; ?>/onboarding_admin.php?view=guide" class="list-group-item list-group-item-action list-group-item-light px-2 py-2 <?php echo basename($_SERVER['PHP_SELF']) == 'onboarding_admin.php' ? 'active' : ''; ?>">
                    <i class="fas fa-info-circle me-2 fixed-width-icon"></i> Como Funciona
                </a>
                <hr class="my-2">
            <?php else: ?>
                <!-- Menu Professor -->
                <a href="<?php echo SITE_URL; ?>/dashboard.php" class="list-group-item list-group-item-action list-group-item-light px-2 py-2 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home me-2 fixed-width-icon"></i> Dashboard
                </a>
            <?php endif; ?>
            
            <!-- Links comuns ou exclusivos de professor (mantidos visíveis para admin por enquanto, exceto Dashboard) -->

            <a href="<?php echo SITE_URL; ?>/tipos_aula.php" class="list-group-item list-group-item-action list-group-item-light px-2 py-2 <?php echo strpos(basename($_SERVER['PHP_SELF']), 'tipos_aula') !== false ? 'active' : ''; ?>">
                <i class="fas fa-chalkboard me-2 fixed-width-icon"></i> Tipos de Aula
            </a>
            <a href="<?php echo SITE_URL; ?>/alunos.php" class="list-group-item list-group-item-action list-group-item-light px-2 py-2 <?php echo strpos(basename($_SERVER['PHP_SELF']), 'alunos') !== false ? 'active' : ''; ?>">
                <i class="fas fa-users me-2 fixed-width-icon"></i> Alunos
            </a>
            <a href="<?php echo SITE_URL; ?>/turmas.php" class="list-group-item list-group-item-action list-group-item-light px-2 py-2 <?php echo strpos(basename($_SERVER['PHP_SELF']), 'turmas') !== false ? 'active' : ''; ?>">
                <i class="fas fa-layer-group me-2 fixed-width-icon"></i> Turmas
            </a>
            <a href="<?php echo SITE_URL; ?>/agenda.php" class="list-group-item list-group-item-action list-group-item-light px-2 py-2 <?php echo strpos(basename($_SERVER['PHP_SELF']), 'agenda') !== false ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt me-2 fixed-width-icon"></i> Agenda
            </a>
            <a href="<?php echo SITE_URL; ?>/mensalidades.php" class="list-group-item list-group-item-action list-group-item-light px-2 py-2 <?php echo strpos(basename($_SERVER['PHP_SELF']), 'mensalidades') !== false ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice-dollar me-2 fixed-width-icon"></i> Financeiro
            </a>
            <a href="<?php echo SITE_URL; ?>/boletos.php" class="list-group-item list-group-item-action list-group-item-light px-2 py-2 <?php echo strpos(basename($_SERVER['PHP_SELF']), 'boletos') !== false ? 'active' : ''; ?>">
                <i class="fas fa-barcode me-2 fixed-width-icon"></i> Boletos
            </a>
            <a href="<?php echo SITE_URL; ?>/templates_mensagem.php" class="list-group-item list-group-item-action list-group-item-light px-2 py-2 <?php echo strpos(basename($_SERVER['PHP_SELF']), 'templates_mensagem') !== false ? 'active' : ''; ?>">
                <i class="fas fa-comment-dots me-2 fixed-width-icon"></i> Templates
            </a>
            <a href="<?php echo SITE_URL; ?>/contratos_config.php" class="list-group-item list-group-item-action list-group-item-light px-2 py-2 <?php echo basename($_SERVER['PHP_SELF']) == 'contratos_config.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-contract me-2 fixed-width-icon"></i> Contrato
            </a>
            <a href="<?php echo SITE_URL; ?>/assinatura_sistema.php" class="list-group-item list-group-item-action list-group-item-light px-2 py-2 <?php echo basename($_SERVER['PHP_SELF']) == 'assinatura_sistema.php' ? 'active' : ''; ?>">
                <i class="fas fa-crown me-2 fixed-width-icon"></i> Assinatura
            </a>
            <a href="<?php echo SITE_URL; ?>/perfil.php" class="list-group-item list-group-item-action list-group-item-light px-2 py-2 <?php echo basename($_SERVER['PHP_SELF']) == 'perfil.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-cog me-2 fixed-width-icon"></i> Meu Perfil
            </a>
            <a href="<?php echo SITE_URL; ?>/onboarding.php?view=guide" class="list-group-item list-group-item-action list-group-item-light px-2 py-2 <?php echo basename($_SERVER['PHP_SELF']) == 'onboarding.php' ? 'active' : ''; ?>">
                <i class="fas fa-info-circle me-2 fixed-width-icon"></i> Como Funciona
            </a>
        </div>
        
        <div class="mt-auto p-3 border-top">
             <div class="d-grid">
                <a href="<?php echo SITE_URL; ?>/logout.php" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt me-2"></i> Sair
                </a>
             </div>
        </div>
    </div>
    
    <!-- Page Content wrapper -->
    <div id="page-content-wrapper" class="w-100 flex-grow-1">
        <!-- Top navigation -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom mb-4 shadow-sm">
            <div class="container-fluid">
                <button class="btn btn-light" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                
                <div class="ms-auto d-flex align-items-center">
                    <?php if ($pending_pre_registrations > 0): ?>
                    <a href="<?php echo SITE_URL; ?>/alunos.php" class="text-decoration-none me-3 position-relative">
                        <i class="fas fa-bell fa-lg text-primary"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $pending_pre_registrations; ?>
                        </span>
                    </a>
                    <?php endif; ?>

                    <?php if (function_exists('isSystemSubscriptionActive')): ?>
                        <?php if (isSystemSubscriptionActive($_SESSION['user_id'])): ?>
                            <a href="<?php echo SITE_URL; ?>/assinatura_sistema.php" class="text-decoration-none me-3" title="Assinatura ativa">
                                <span class="badge bg-success">Plano ativo</span>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo SITE_URL; ?>/assinatura_sistema.php" class="text-decoration-none me-3" title="Assinatura inativa">
                                <span class="badge bg-warning text-dark">Plano inativo</span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle text-dark fw-medium" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1 text-primary"></i>
                            <span class="d-none d-md-inline"><?php echo $_SESSION['user_name']; ?></span>
                            <span class="d-inline d-md-none"><?php echo explode(' ', $_SESSION['user_name'])[0]; ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/perfil.php"><i class="fas fa-user me-2 text-muted"></i> Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Sair</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        
        <div class="container-fluid px-4">
<?php else: ?>
    <!-- Layout para Login/Register (sem sidebar) -->
    <div class="container-fluid p-0">
<?php endif; ?>

<?php if (isset($_SESSION['flash_message'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showToast(<?php echo json_encode($_SESSION['flash_message']); ?>, <?php echo json_encode($_SESSION['flash_type']); ?>);
});
</script>
<?php 
    unset($_SESSION['flash_message']); 
    unset($_SESSION['flash_type']);
?>
<?php endif; ?>
