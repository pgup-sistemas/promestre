<?php
require_once 'includes/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$professor_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $agenda_id = isset($_GET['agenda_id']) ? (int)$_GET['agenda_id'] : 0;

    if (!$agenda_id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID da agenda não fornecido']);
        exit;
    }

    try {
        // Verificar se a agenda pertence ao professor e obter detalhes
        $stmt = $pdo->prepare("SELECT * FROM agenda WHERE id = ? AND professor_id = ?");
        $stmt->execute([$agenda_id, $professor_id]);
        $agenda = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$agenda) {
            http_response_code(404);
            echo json_encode(['error' => 'Agendamento não encontrado']);
            exit;
        }

        $alunos = [];

        if ($agenda['turma_id']) {
            // Busca alunos da turma e junta com a tabela de presença da agenda
            // Importante: Left Join com agenda_alunos para pegar status já salvo se houver
            $sql = "SELECT 
                        a.id as aluno_id, 
                        a.nome, 
                        COALESCE(aa.presenca, 'pendente') as status_presenca
                    FROM turma_alunos ta
                    JOIN alunos a ON ta.aluno_id = a.id
                    LEFT JOIN agenda_alunos aa ON (aa.aluno_id = a.id AND aa.agenda_id = ?)
                    WHERE ta.turma_id = ? AND ta.ativo = 1
                    ORDER BY a.nome";
            
            $stmt_alunos = $pdo->prepare($sql);
            $stmt_alunos->execute([$agenda_id, $agenda['turma_id']]);
            $alunos = $stmt_alunos->fetchAll(PDO::FETCH_ASSOC);

        } elseif ($agenda['aluno_id']) {
            // Aula individual
            $sql = "SELECT 
                        a.id as aluno_id, 
                        a.nome, 
                        COALESCE(aa.presenca, 'pendente') as status_presenca
                    FROM alunos a
                    LEFT JOIN agenda_alunos aa ON (aa.aluno_id = a.id AND aa.agenda_id = ?)
                    WHERE a.id = ?";
            
            $stmt_alunos = $pdo->prepare($sql);
            $stmt_alunos->execute([$agenda_id, $agenda['aluno_id']]);
            $alunos = $stmt_alunos->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode(['success' => true, 'alunos' => $alunos, 'agenda_status' => $agenda['status']]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }

} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $agenda_id = isset($input['agenda_id']) ? (int)$input['agenda_id'] : 0;
    $presencas = isset($input['presencas']) ? $input['presencas'] : []; // Array de {aluno_id: X, status: Y}

    if (!$agenda_id || empty($presencas)) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados inválidos']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Verificar propriedade
        $stmt = $pdo->prepare("SELECT id FROM agenda WHERE id = ? AND professor_id = ?");
        $stmt->execute([$agenda_id, $professor_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Agendamento não encontrado ou acesso negado");
        }

        // Salvar presenças
        $sql_insert = "INSERT INTO agenda_alunos (agenda_id, aluno_id, presenca) 
                       VALUES (?, ?, ?) 
                       ON DUPLICATE KEY UPDATE presenca = VALUES(presenca)";
        $stmt_insert = $pdo->prepare($sql_insert);

        foreach ($presencas as $p) {
            $aluno_id = (int)$p['aluno_id'];
            $status = $p['status']; // presente, ausente, justificada
            
            if (!in_array($status, ['pendente', 'presente', 'ausente', 'justificada'])) {
                continue; // Skip invalid status
            }

            $stmt_insert->execute([$agenda_id, $aluno_id, $status]);
        }

        // Atualizar status da agenda para realizado se não estiver cancelado
        // (Opcional: Pode ser feito manualmente pelo usuário, mas geralmente marcar presença implica realização)
        $pdo->exec("UPDATE agenda SET status = 'realizado' WHERE id = $agenda_id AND status = 'agendado'");

        $pdo->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
}
