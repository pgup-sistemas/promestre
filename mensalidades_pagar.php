<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$professor_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($id) {
    // Verificar se pertence ao professor
    $stmt = $pdo->prepare("SELECT id FROM mensalidades WHERE id = ? AND professor_id = ?");
    $stmt->execute([$id, $professor_id]);
    
    if ($stmt->rowCount() > 0) {
        try {
            $data_pagamento = date('Y-m-d');
            $stmt = $pdo->prepare("UPDATE mensalidades SET status = 'pago', data_pagamento = ? WHERE id = ?");
            $stmt->execute([$data_pagamento, $id]);
            setFlash('Mensalidade marcada como paga!', 'success');
        } catch (PDOException $e) {
            setFlash('Erro ao atualizar.', 'danger');
        }
    } else {
        setFlash('Mensalidade não encontrada.', 'danger');
    }
}

redirect('mensalidades.php');
