<?php
require_once 'includes/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode([]);
    exit;
}

$professor_id = $_SESSION['user_id'];
$start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d');
$end = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d', strtotime('+1 month'));

// Query atualizada para buscar Turmas e Alunos
$sql = "SELECT ag.*, 
               a.nome as aluno_nome, 
               a.whatsapp as aluno_whatsapp,
               t.nome as turma_nome,
               t.cor as turma_cor
        FROM agenda ag
        LEFT JOIN alunos a ON ag.aluno_id = a.id
        LEFT JOIN turmas t ON ag.turma_id = t.id
        WHERE ag.professor_id = ?
        AND ag.data_inicio >= ?
        AND ag.data_fim <= ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$professor_id, $start, $end]);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$json_events = [];

foreach ($eventos as $evento) {
    $color = '#6c757d'; // Default
    $textColor = '#ffffff';

    // Definição de Cores
    if ($evento['turma_id']) {
        // Usa a cor da turma se definida, senão usa uma padrão para turmas
        $color = $evento['turma_cor'] ?: '#4e73df';
    } else {
        // Cores por status para aulas individuais
        switch ($evento['status']) {
            case 'agendado':
                $color = '#36b9cc'; // Info/Cyan para individual
                break;
            case 'realizado':
                $color = '#1cc88a'; // Success
                break;
            case 'cancelado':
                $color = '#e74a3b'; // Danger
                break;
        }
    }

    // Título
    if ($evento['turma_id']) {
        $title = $evento['turma_nome'];
        if ($evento['titulo']) $title .= ' - ' . $evento['titulo'];
    } else {
        $title = $evento['titulo'];
        if ($evento['aluno_nome']) $title .= ' - ' . $evento['aluno_nome'];
    }

    $json_events[] = [
        'id' => $evento['id'],
        'title' => $title,
        'start' => $evento['data_inicio'],
        'end' => $evento['data_fim'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'textColor' => $textColor,
        'extendedProps' => [
            'aluno_nome' => $evento['aluno_nome'],
            'turma_nome' => $evento['turma_nome'],
            'turma_id' => $evento['turma_id'],
            'is_turma' => !empty($evento['turma_id']),
            'status' => $evento['status'],
            'observacoes' => $evento['observacoes'],
            'whatsapp' => $evento['aluno_whatsapp'],
            'presenca' => $evento['presenca']
        ]
    ];
}

echo json_encode($json_events);
