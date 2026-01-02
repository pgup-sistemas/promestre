<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$professor_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($id) {
    // Verificar se pertence ao professor
    $stmt = $pdo->prepare("SELECT id FROM agenda WHERE id = ? AND professor_id = ?");
    $stmt->execute([$id, $professor_id]);
    
    if ($stmt->rowCount() > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM agenda WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('Agendamento excluído.', 'success');
        } catch (PDOException $e) {
            setFlash('Erro ao excluir.', 'danger');
        }
    } else {
        setFlash('Agendamento não encontrado.', 'danger');
    }
}

redirect('agenda.php');
