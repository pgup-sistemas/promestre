<?php
require_once 'includes/config.php';
require_once 'includes/user_management.php';

// Verificar permissão
requireAdmin();

$page_title = 'Gestão de Usuários';

// Parâmetros de paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$search = isset($_GET['search']) ? clean($_GET['search']) : '';

// Ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? 0;
    
    switch ($action) {
        case 'toggle_status':
            if (toggleUserStatus($user_id)) {
                setFlash('Status do usuário atualizado com sucesso!', 'success');
            } else {
                setFlash('Erro ao atualizar status do usuário.', 'danger');
            }
            break;
            
        case 'delete_user':
            if (deleteUser($user_id)) {
                setFlash('Usuário desativado com sucesso!', 'success');
            } else {
                setFlash('Erro ao desativar usuário.', 'danger');
            }
            break;
            
        case 'reset_password':
            $token = generateResetToken($user_id, $_SESSION['user_id']);
            if ($token) {
                $user = getUserById($user_id);
                $reset_link = SITE_URL . "/admin_reset_senha.php?token=" . $token;
                
                // Aqui você poderia enviar por email
                setFlash("Link de reset gerado: <a href='$reset_link' target='_blank'>$reset_link</a>", 'info');
                logActivity('RESET_PASSWORD', 'professores', $user_id, "Reset de senha gerado para {$user['nome']}");
            } else {
                setFlash('Erro ao gerar link de reset.', 'danger');
            }
            break;
    }
    
    redirect('gestao_usuarios.php?page=' . $page . '&search=' . urlencode($search));
}

// Buscar usuários
$users = getAllUsers($page, $limit, $search);
$total_users = countUsers($search);
$total_pages = ceil($total_users / $limit);

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3"><i class="fas fa-users-cog me-2"></i> Gestão de Usuários</h1>
    <a href="usuarios_cadastro.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Novo Usuário
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <input type="text" name="search" class="form-control" placeholder="Buscar por nome ou email..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="fas fa-search me-1"></i> Buscar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Usuários -->
<div class="card">
    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="text-center py-4">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5>Nenhum usuário encontrado</h5>
                <p class="text-muted">Nenhum usuário corresponde aos critérios de busca.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Nível</th>
                            <th>Status</th>
                            <th>Último Login</th>
                            <th>Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                            <?php echo strtoupper(substr($user['nome'], 0, 2)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-medium"><?php echo htmlspecialchars($user['nome']); ?></div>
                                            <small class="text-muted">ID: <?php echo $user['id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo $user['telefone'] ? formatPhone($user['telefone']) : '-'; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['nivel'] === 'admin' ? 'danger' : 'primary'; ?>">
                                        <?php echo ucfirst($user['nivel']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $user['ativo'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $user['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $user['ultimo_login'] ? formatDateTime($user['ultimo_login']) : '-'; ?>
                                </td>
                                <td><?php echo formatDate($user['data_cadastro']); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" 
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="usuarios_editar.php?id=<?php echo $user['id']; ?>">
                                                    <i class="fas fa-edit me-1"></i> Editar
                                                </a>
                                            </li>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="reset_password">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="fas fa-key me-1"></i> Resetar Senha
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="fas fa-toggle-<?php echo $user['ativo'] ? 'off' : 'on'; ?> me-1"></i>
                                                            <?php echo $user['ativo'] ? 'Desativar' : 'Ativar'; ?>
                                                        </button>
                                                    </form>
                                                </li>
                                                <?php if ($user['nivel'] !== 'admin'): ?>
                                                    <li>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="delete_user">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <button type="submit" class="dropdown-item text-danger" 
                                                                    onclick="return confirm('Tem certeza que deseja desativar este usuário?')">
                                                                <i class="fas fa-trash me-1"></i> Desativar
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Paginação">
                    <ul class="pagination justify-content-center mt-3">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Estatísticas -->
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo $total_users; ?></h4>
                        <small>Total de Usuários</small>
                    </div>
                    <i class="fas fa-users fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">
                            <?php 
                            $ativos = array_filter($users, fn($u) => $u['ativo']);
                            echo count($ativos); 
                            ?>
                        </h4>
                        <small>Usuários Ativos</small>
                    </div>
                    <i class="fas fa-user-check fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">
                            <?php 
                            $admins = array_filter($users, fn($u) => $u['nivel'] === 'admin');
                            echo count($admins); 
                            ?>
                        </h4>
                        <small>Administradores</small>
                    </div>
                    <i class="fas fa-user-shield fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">
                            <?php 
                            $professores = array_filter($users, fn($u) => $u['nivel'] === 'professor');
                            echo count($professores); 
                            ?>
                        </h4>
                        <small>Professores</small>
                    </div>
                    <i class="fas fa-chalkboard-teacher fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
