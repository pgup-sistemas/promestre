<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

// Verificar se o PHPExcel está disponível
if (!class_exists('PHPExcel')) {
    // Carregar PHPExcel se estiver disponível
    $phpexcel_path = 'assets/vendor/PHPExcel/PHPExcel.php';
    if (file_exists($phpexcel_path)) {
        require_once $phpexcel_path;
    } else {
        die('PHPExcel não encontrado. Por favor, instale a biblioteca.');
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

// Gerar Excel
$objPHPExcel = new PHPExcel();

// Configurações do documento
$objPHPExcel->getProperties()->setCreator("Promestre")
                             ->setLastModifiedBy("Promestre")
                             ->setTitle($tipo === 'frequencia' ? "Relatório de Frequência" : "Relatório Financeiro")
                             ->setSubject($tipo === 'frequencia' ? "Frequência de Aulas" : "Mensalidades")
                             ->setDescription("Relatório gerado pelo sistema Promestre");

// Definir a planilha ativa
$objPHPExcel->setActiveSheetIndex(0);
$activeSheet = $objPHPExcel->getActiveSheet();

// Cabeçalhos
if ($tipo === 'frequencia') {
    $activeSheet->setCellValue('A1', 'Data');
    $activeSheet->setCellValue('B1', 'Horário');
    $activeSheet->setCellValue('C1', 'Aluno');
    $activeSheet->setCellValue('D1', 'Aula');
    $activeSheet->setCellValue('E1', 'Status');
    $activeSheet->setCellValue('F1', 'Presença');
    
    // Preencher dados
    $row = 2;
    foreach ($dados as $dado) {
        $activeSheet->setCellValue('A' . $row, date('d/m/Y', strtotime($dado['data_inicio'])));
        $activeSheet->setCellValue('B' . $row, date('H:i', strtotime($dado['data_inicio'])));
        $activeSheet->setCellValue('C' . $row, $dado['aluno_nome'] ?: 'N/A');
        $activeSheet->setCellValue('D' . $row, $dado['titulo']);
        
        if ($dado['status'] === 'agendado') {
            $activeSheet->setCellValue('E' . $row, 'Agendado');
        } elseif ($dado['status'] === 'realizado') {
            $activeSheet->setCellValue('E' . $row, 'Realizado');
        } else {
            $activeSheet->setCellValue('E' . $row, 'Cancelado');
        }
        
        if ($dado['presenca'] === 'presente') {
            $activeSheet->setCellValue('F' . $row, 'Presente');
        } elseif ($dado['presenca'] === 'ausente') {
            $activeSheet->setCellValue('F' . $row, 'Ausente');
        } elseif ($dado['presenca'] === 'justificada') {
            $activeSheet->setCellValue('F' . $row, 'Justificada');
        } else {
            $activeSheet->setCellValue('F' . $row, 'Não marcada');
        }
        
        $row++;
    }
} else {
    $activeSheet->setCellValue('A1', 'Aluno');
    $activeSheet->setCellValue('B1', 'Valor');
    $activeSheet->setCellValue('C1', 'Vencimento');
    $activeSheet->setCellValue('D1', 'Status');
    $activeSheet->setCellValue('E1', 'Pagamento');
    $activeSheet->setCellValue('F1', 'Forma');
    $activeSheet->setCellValue('G1', 'Observações');
    
    // Preencher dados
    $row = 2;
    foreach ($dados as $dado) {
        $activeSheet->setCellValue('A' . $row, $dado['aluno_nome']);
        $activeSheet->setCellValue('B' . $row, $dado['valor']);
        $activeSheet->setCellValue('C' . $row, date('d/m/Y', strtotime($dado['data_vencimento'])));
        
        $activeSheet->setCellValue('D' . $row, ucfirst($dado['status']));
        
        if ($dado['data_pagamento']) {
            $activeSheet->setCellValue('E' . $row, date('d/m/Y', strtotime($dado['data_pagamento'])));
        }
        
        $activeSheet->setCellValue('F' . $row, $dado['forma_pagamento']);
        $activeSheet->setCellValue('G' . $row, $dado['observacoes']);
        
        $row++;
    }
}

// Ajustar largura das colunas
foreach (range('A', $activeSheet->getHighestColumn()) as $columnID) {
    $activeSheet->getColumnDimension($columnID)
        ->setAutoSize(true);
}

// Nome do arquivo
$mes_nome = date('F', mktime(0, 0, 0, $mes, 1));
$filename = $tipo === 'frequencia' ? 
    "frequencia_{$mes_nome}_{$ano}.xlsx" : 
    "financeiro_{$mes_nome}_{$ano}.xlsx";

// Enviar cabeçalhos para download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
exit;