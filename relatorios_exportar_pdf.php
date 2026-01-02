<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

// Verificar se o TCPDF está disponível
if (!class_exists('TCPDF')) {
    // Carregar TCPDF se estiver disponível
    $tcpdf_path = 'assets/vendor/tcpdf/tcpdf.php';
    if (file_exists($tcpdf_path)) {
        require_once $tcpdf_path;
    } else {
        die('TCPDF não encontrado. Por favor, instale a biblioteca.');
    }
}

// Parâmetros de filtro
$mes = filter_input(INPUT_GET, 'mes', FILTER_SANITIZE_STRING) ?: date('m');
$ano = filter_input(INPUT_GET, 'ano', FILTER_SANITIZE_STRING) ?: date('Y');
$aluno_id = filter_input(INPUT_GET, 'aluno_id', FILTER_VALIDATE_INT);
$tipo = filter_input(INPUT_GET, 'tipo', FILTER_SANITIZE_STRING) ?: 'frequencia';

// Consulta de dados
if ($tipo === 'frequencia') {
    $sql = "SELECT 
        a.id,
        a.titulo,
        a.data_inicio,
        a.aluno_id,
        al.nome as aluno_nome,
        a.presenca,
        a.status
    FROM agenda a
    LEFT JOIN alunos al ON a.aluno_id = al.id
    WHERE a.professor_id = ? 
    AND MONTH(a.data_inicio) = ? 
    AND YEAR(a.data_inicio) = ?";

    $params = [$_SESSION['user_id'], $mes, $ano];

    if ($aluno_id) {
        $sql .= " AND a.aluno_id = ?";
        $params[] = $aluno_id;
    }

    $sql .= " ORDER BY a.data_inicio DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $dados = $stmt->fetchAll();
} else {
    // Relatório financeiro
    $sql = "SELECT 
        m.id,
        m.valor,
        m.data_vencimento,
        m.status,
        m.data_pagamento,
        m.forma_pagamento,
        m.observacoes,
        a.nome as aluno_nome
    FROM mensalidades m
    LEFT JOIN alunos a ON m.aluno_id = a.id
    WHERE m.professor_id = ? 
    AND MONTH(m.data_vencimento) = ? 
    AND YEAR(m.data_vencimento) = ?";

    $params = [$_SESSION['user_id'], $mes, $ano];

    if ($aluno_id) {
        $sql .= " AND m.aluno_id = ?";
        $params[] = $aluno_id;
    }

    $sql .= " ORDER BY m.data_vencimento DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $dados = $stmt->fetchAll();
}

// Criar PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurações do documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Promestre');
$pdf->SetTitle($tipo === 'frequencia' ? 'Relatório de Frequência' : 'Relatório Financeiro');
$pdf->SetSubject($tipo === 'frequencia' ? 'Frequência de Aulas' : 'Mensalidades');
$pdf->SetKeywords('Promestre, relatório, frequência, financeiro');

// Definir margens
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Remover cabeçalho padrão
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Adicionar página
$pdf->AddPage();

// Título
$mes_nome = date('F', mktime(0, 0, 0, $mes, 1));
$titulo = $tipo === 'frequencia' ? 
    "Relatório de Frequência - {$mes_nome} {$ano}" : 
    "Relatório Financeiro - {$mes_nome} {$ano}";

$html = '<h1 style="text-align:center; color:#333;">' . $titulo . '</h1>';
$html .= '<p style="text-align:center; color:#666; margin-bottom:30px;">Professor: ' . $_SESSION['user_nome'] . '</p>';

// Tabela
$html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse; width:100%;">';

if ($tipo === 'frequencia') {
    $html .= '<thead>';
    $html .= '<tr style="background-color:#f0f0f0;">';
    $html .= '<th style="font-weight:bold;">Data</th>';
    $html .= '<th style="font-weight:bold;">Horário</th>';
    $html .= '<th style="font-weight:bold;">Aluno</th>';
    $html .= '<th style="font-weight:bold;">Aula</th>';
    $html .= '<th style="font-weight:bold;">Status</th>';
    $html .= '<th style="font-weight:bold;">Presença</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    
    $html .= '<tbody>';
    foreach ($dados as $dado) {
        $html .= '<tr>';
        $html .= '<td>' . date('d/m/Y', strtotime($dado['data_inicio'])) . '</td>';
        $html .= '<td>' . date('H:i', strtotime($dado['data_inicio'])) . '</td>';
        $html .= '<td>' . ($dado['aluno_nome'] ?: 'N/A') . '</td>';
        $html .= '<td>' . $dado['titulo'] . '</td>';
        
        if ($dado['status'] === 'agendado') {
            $html .= '<td style="color:#0d6efd;">Agendado</td>';
        } elseif ($dado['status'] === 'realizado') {
            $html .= '<td style="color:#198754;">Realizado</td>';
        } else {
            $html .= '<td style="color:#dc3545;">Cancelado</td>';
        }
        
        if ($dado['presenca'] === 'presente') {
            $html .= '<td style="color:#198754;">Presente</td>';
        } elseif ($dado['presenca'] === 'ausente') {
            $html .= '<td style="color:#ffc107;">Ausente</td>';
        } elseif ($dado['presenca'] === 'justificada') {
            $html .= '<td style="color:#0dcaf0;">Justificada</td>';
        } else {
            $html .= '<td style="color:#6c757d;">Não marcada</td>';
        }
        
        $html .= '</tr>';
    }
    $html .= '</tbody>';
} else {
    $html .= '<thead>';
    $html .= '<tr style="background-color:#f0f0f0;">';
    $html .= '<th style="font-weight:bold;">Aluno</th>';
    $html .= '<th style="font-weight:bold;">Valor</th>';
    $html .= '<th style="font-weight:bold;">Vencimento</th>';
    $html .= '<th style="font-weight:bold;">Status</th>';
    $html .= '<th style="font-weight:bold;">Pagamento</th>';
    $html .= '<th style="font-weight:bold;">Forma</th>';
    $html .= '<th style="font-weight:bold;">Observações</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    
    $html .= '<tbody>';
    foreach ($dados as $dado) {
        $html .= '<tr>';
        $html .= '<td>' . $dado['aluno_nome'] . '</td>';
        $html .= '<td>R$ ' . number_format($dado['valor'], 2, ',', '.') . '</td>';
        $html .= '<td>' . date('d/m/Y', strtotime($dado['data_vencimento'])) . '</td>';
        $html .= '<td>' . ucfirst($dado['status']) . '</td>';
        
        if ($dado['data_pagamento']) {
            $html .= '<td>' . date('d/m/Y', strtotime($dado['data_pagamento'])) . '</td>';
        } else {
            $html .= '<td>-</td>';
        }
        
        $html .= '<td>' . $dado['forma_pagamento'] . '</td>';
        $html .= '<td>' . ($dado['observacoes'] ?: '-') . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody>';
}

$html .= '</table>';

// Escrever conteúdo
$pdf->writeHTML($html, true, false, true, false, '');

// Nome do arquivo
$filename = $tipo === 'frequencia' ? 
    "frequencia_{$mes_nome}_{$ano}.pdf" : 
    "financeiro_{$mes_nome}_{$ano}.pdf";

// Enviar cabeçalhos para download
$pdf->Output($filename, 'D');
exit;