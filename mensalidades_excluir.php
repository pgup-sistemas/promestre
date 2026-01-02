<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

requireActiveSystemSubscription();

$professor_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($id) {
    // Verificar se pertence ao professor
    $stmt = $pdo->prepare("SELECT id FROM mensalidades WHERE id = ? AND professor_id = ?");
    $stmt->execute([$id, $professor_id]);
    
    if ($stmt->rowCount() > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM mensalidades WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('Mensalidade excluída com sucesso.', 'success');
        } catch (PDOException $e) {
            setFlash('Erro ao excluir.', 'danger');
        }
    } else {
        setFlash('Mensalidade não encontrada.', 'danger');
    }
}

redirect('mensalidades.php');
