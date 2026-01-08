<?php
/**
 * Funções de Gestão de Usuários e Suporte
 */

/**
 * Verifica se usuário é admin
 */
function isAdmin() {
    return isset($_SESSION['user_nivel']) && $_SESSION['user_nivel'] === 'admin';
}

/**
 * Verifica se usuário tem permissão para acessar recurso
 */
function hasPermission($resource) {
    if (isAdmin()) {
        return true; // Admin tem acesso a tudo
    }
    
    // Permissões específicas para professores
    $professor_permissions = [
        'dashboard' => true,
        'alunos' => true,
        'alunos_cadastro' => true,
        'alunos_detalhes' => true,
        'alunos_excluir' => true,
        'mensalidades' => true,
        'agenda' => true,
        'perfil' => true,
        'tipos_aula' => true,
        'relatorios' => true
    ];
    
    return isset($professor_permissions[$resource]) && $professor_permissions[$resource];
}

/**
 * Redireciona se não for admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        setFlash('Acesso negado. Você não tem permissão de administrador.', 'danger');
        redirect('dashboard.php');
    }
}

/**
 * Registra log de atividade
 */
function logActivity($acao, $tabela = null, $registro_id = null, $descricao = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO logs_atividades (professor_id, acao, tabela, registro_id, descricao, ip, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $acao,
            $tabela,
            $registro_id,
            $descricao,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        // Silenciar erros de log para não quebrar a aplicação
        error_log("Erro ao registrar log: " . $e->getMessage());
    }
}

/**
 * Atualizar último login do usuário
 */
function updateLastLogin($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE professores SET ultimo_login = NOW() WHERE id = ?");
        $stmt->execute([$user_id]);
    } catch (Exception $e) {
        error_log("Erro ao atualizar último login: " . $e->getMessage());
    }
}

/**
 * Gerar token de reset de senha (admin)
 */
function generateResetToken($user_id, $admin_id) {
    global $pdo;
    
    try {
        // Limpar tokens antigos do mesmo usuário
        $stmt = $pdo->prepare("DELETE FROM reset_tokens WHERE professor_id = ? OR expiracao < NOW()");
        $stmt->execute([$user_id]);
        
        // Gerar novo token
        $token = bin2hex(random_bytes(32));
        $expiracao = date('Y-m-d H:i:s', strtotime('+2 hours'));
        
        $stmt = $pdo->prepare("
            INSERT INTO reset_tokens (professor_id, token, criado_por, expiracao) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $token, $admin_id, $expiracao]);
        
        return $token;
    } catch (Exception $e) {
        error_log("Erro ao gerar token de reset: " . $e->getMessage());
        return false;
    }
}

/**
 * Validar token de reset
 */
function validateResetToken($token) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT rt.*, p.email, p.nome 
            FROM reset_tokens rt 
            JOIN professores p ON rt.professor_id = p.id 
            WHERE rt.token = ? AND rt.expiracao > NOW() AND rt.usado = FALSE
        ");
        $stmt->execute([$token]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Erro ao validar token: " . $e->getMessage());
        return false;
    }
}

/**
 * Marcar token como usado
 */
function markTokenAsUsed($token) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE reset_tokens SET usado = TRUE WHERE token = ?");
        $stmt->execute([$token]);
    } catch (Exception $e) {
        error_log("Erro ao marcar token como usado: " . $e->getMessage());
    }
}

/**
 * Listar todos os usuários (admin)
 */
function getAllUsers($page = 1, $limit = 20, $search = '') {
    global $pdo;
    
    try {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT id, nome, email, telefone, nivel, ativo, data_cadastro, ultimo_login 
                FROM professores WHERE 1=1";
        $params = [];
        
        if ($search) {
            $sql .= " AND (nome LIKE ? OR email LIKE ?)";
            $search_like = "%$search%";
            $params[] = $search_like;
            $params[] = $search_like;
        }
        
        $sql .= " ORDER BY data_cadastro DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Erro ao listar usuários: " . $e->getMessage());
        return [];
    }
}

/**
 * Contar total de usuários (para paginação)
 */
function countUsers($search = '') {
    global $pdo;
    
    try {
        $sql = "SELECT COUNT(*) as total FROM professores WHERE 1=1";
        $params = [];
        
        if ($search) {
            $sql .= " AND (nome LIKE ? OR email LIKE ?)";
            $search_like = "%$search%";
            $params[] = $search_like;
            $params[] = $search_like;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['total'];
    } catch (Exception $e) {
        error_log("Erro ao contar usuários: " . $e->getMessage());
        return 0;
    }
}

/**
 * Obter dados do usuário por ID
 */
function getUserById($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM professores WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Erro ao buscar usuário: " . $e->getMessage());
        return false;
    }
}

/**
 * Atualizar usuário
 */
function updateUser($user_id, $data) {
    global $pdo;
    
    try {
        $fields = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            if ($field !== 'id' && $field !== 'senha') {
                $fields[] = "$field = ?";
                $params[] = $value;
            }
        }
        
        if (!empty($fields)) {
            $sql = "UPDATE professores SET " . implode(', ', $fields) . " WHERE id = ?";
            $params[] = $user_id;
            
            $stmt = $pdo->prepare($sql);
            return $stmt->execute($params);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Erro ao atualizar usuário: " . $e->getMessage());
        return false;
    }
}

/**
 * Atualizar senha do usuário
 */
function updateUserPassword($user_id, $new_password) {
    global $pdo;
    
    try {
        $senha_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE professores SET senha = ? WHERE id = ?");
        return $stmt->execute([$senha_hash, $user_id]);
    } catch (Exception $e) {
        error_log("Erro ao atualizar senha: " . $e->getMessage());
        return false;
    }
}

/**
 * Alternar status do usuário (ativo/inativo)
 */
function toggleUserStatus($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE professores SET ativo = NOT ativo WHERE id = ?");
        $result = $stmt->execute([$user_id]);
        
        if ($result) {
            $user = getUserById($user_id);
            logActivity('TOGGLE_STATUS', 'professores', $user_id, 
                "Status do usuário {$user['nome']} alterado para " . ($user['ativo'] ? 'inativo' : 'ativo'));
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Erro ao alternar status: " . $e->getMessage());
        return false;
    }
}

/**
 * Excluir usuário (soft delete)
 */
function deleteUser($user_id) {
    global $pdo;
    
    try {
        // Não permitir excluir o próprio usuário admin
        if ($user_id == $_SESSION['user_id']) {
            return false;
        }
        
        $stmt = $pdo->prepare("UPDATE professores SET ativo = FALSE WHERE id = ?");
        $result = $stmt->execute([$user_id]);
        
        if ($result) {
            $user = getUserById($user_id);
            logActivity('DELETE_USER', 'professores', $user_id, 
                "Usuário {$user['nome']} desativado");
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Erro ao excluir usuário: " . $e->getMessage());
        return false;
    }
}
?>
