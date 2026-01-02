<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$professor_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($id) {
    // Verificar se tipo pertence ao professor
    $stmt = $pdo->prepare("SELECT id FROM tipos_aula WHERE id = ? AND professor_id = ?");
    $stmt->execute([$id, $professor_id]);
    
    if ($stmt->rowCount() > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM tipos_aula WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('Tipo de aula excluído com sucesso.', 'success');
        } catch (PDOException $e) {
            // Provavelmente constraint violation
            setFlash('Não é possível excluir este tipo de aula pois existem alunos vinculados. Tente inativá-lo.', 'warning');
        }
    } else {
        setFlash('Tipo de aula não encontrado.', 'danger');
    }
}

redirect('tipos_aula.php');
