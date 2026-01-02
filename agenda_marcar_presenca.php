<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agenda_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $presenca = filter_input(INPUT_POST, 'presenca', FILTER_SANITIZE_STRING);

    if (!$agenda_id || !$presenca) {
        echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
        exit;
    }

    // Validar presença
    $presencas_validas = ['presente', 'ausente', 'justificada'];
    if (!in_array($presenca, $presencas_validas)) {
        echo json_encode(['success' => false, 'error' => 'Presença inválida']);
        exit;
    }

    try {
        // Verificar se o evento pertence ao professor
        $stmt = $pdo->prepare("SELECT * FROM agenda WHERE id = ? AND professor_id = ?");
        $stmt->execute([$agenda_id, $_SESSION['user_id']]);
        $evento = $stmt->fetch();

        if (!$evento) {
            echo json_encode(['success' => false, 'error' => 'Evento não encontrado']);
            exit;
        }

        // Atualizar presença
        $stmt = $pdo->prepare("UPDATE agenda SET presenca = ?, data_presenca = NOW() WHERE id = ?");
        if ($stmt->execute([$presenca, $agenda_id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erro ao atualizar presença']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
}