<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$professor_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verificar se o template existe e pertence ao professor
$stmt = $pdo->prepare("SELECT * FROM templates_mensagem WHERE id = ? AND professor_id = ?");
$stmt->execute([$id, $professor_id]);
$template = $stmt->fetch();

if (!$template) {
    setFlash('Template não encontrado.', 'danger');
    redirect('templates_mensagem.php');
}

// Excluir
try {
    $stmt = $pdo->prepare("DELETE FROM templates_mensagem WHERE id = ? AND professor_id = ?");
    $stmt->execute([$id, $professor_id]);
    setFlash('Template excluído com sucesso!', 'success');
} catch (PDOException $e) {
    setFlash('Erro ao excluir template: ' . $e->getMessage(), 'danger');
}

redirect('templates_mensagem.php');

