<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$professor_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($id) {
    // Verificar se aluno pertence ao professor
    $stmt = $pdo->prepare("SELECT id FROM alunos WHERE id = ? AND professor_id = ?");
    $stmt->execute([$id, $professor_id]);
    
    if ($stmt->rowCount() > 0) {
        // Soft Delete conforme RF002.3
        try {
            $stmt = $pdo->prepare("UPDATE alunos SET deleted_at = NOW(), status = 'inativo' WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('Aluno movido para lixeira com sucesso.', 'success');
        } catch (PDOException $e) {
            setFlash('Erro ao excluir aluno: ' . $e->getMessage(), 'danger');
        }
    } else {
        setFlash('Aluno não encontrado ou permissão negada.', 'danger');
    }
}

redirect('alunos.php');
