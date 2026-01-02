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

// Query
$sql = "SELECT ag.*, a.nome as aluno_nome, a.whatsapp as aluno_whatsapp
        FROM agenda ag
        LEFT JOIN alunos a ON ag.aluno_id = a.id
        WHERE ag.professor_id = ?
        AND ag.data_inicio >= ?
        AND ag.data_fim <= ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$professor_id, $start, $end]);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$json_events = [];

foreach ($eventos as $evento) {
    $color = '#6c757d'; // Default secondary
    $textColor = '#ffffff';

    switch ($evento['status']) {
        case 'agendado':
            $color = '#4e73df'; // Primary
            break;
        case 'realizado':
            $color = '#1cc88a'; // Success
            break;
        case 'cancelado':
            $color = '#e74a3b'; // Danger
            break;
    }

    $json_events[] = [
        'id' => $evento['id'],
        'title' => $evento['titulo'] . ($evento['aluno_nome'] ? ' - ' . $evento['aluno_nome'] : ''),
        'start' => $evento['data_inicio'],
        'end' => $evento['data_fim'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'textColor' => $textColor,
        'extendedProps' => [
            'aluno_nome' => $evento['aluno_nome'],
            'status' => $evento['status'],
            'observacoes' => $evento['observacoes'],
            'whatsapp' => $evento['aluno_whatsapp'],
            'presenca' => $evento['presenca']
        ]
    ];
}

echo json_encode($json_events);
