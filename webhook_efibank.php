<?php
/**
 * Webhook EfiBank - Recebe notificações de pagamento PIX
 * Endpoint: /webhook_efibank.php
 * Método: POST
 */

require_once 'includes/config.php';

// Log da requisição
$log_data = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'body' => file_get_contents('php://input'),
    'timestamp' => date('Y-m-d H:i:s')
];

// Aceitar apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

// Obter payload
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['erro' => 'Payload inválido']);
    exit;
}

try {
    // Obter assinatura do header (se disponível)
    $headers = getallheaders();
    $assinatura = isset($headers['X-GN-Signature']) ? $headers['X-Gn-Signature'] : null;
    
    // Log do webhook
    $stmt_log = $pdo->prepare("
        INSERT INTO webhook_logs (evento, txid, payload, assinatura, processado) 
        VALUES (?, ?, ?, ?, FALSE)
    ");
    
    $evento = isset($data['evento']) ? $data['evento'] : 'desconhecido';
    $txid = null;
    
    // Extrair txid do payload
    if (isset($data['pix']) && is_array($data['pix']) && count($data['pix']) > 0) {
        $txid = isset($data['pix'][0]['txid']) ? $data['pix'][0]['txid'] : null;
    } elseif (isset($data['txid'])) {
        $txid = $data['txid'];
    }
    
    $stmt_log->execute([
        $evento,
        $txid,
        $payload,
        $assinatura
    ]);
    
    $log_id = $pdo->lastInsertId();
    
    // Processar apenas eventos de PIX pagos
    if ($evento === 'pix' && isset($data['pix']) && is_array($data['pix'])) {
        foreach ($data['pix'] as $pix) {
            if (!isset($pix['txid'])) {
                continue;
            }
            
            $txid_pagamento = $pix['txid'];
            $valor_pagamento = isset($pix['valor']) ? floatval($pix['valor']) : 0;
            $horario_pagamento = isset($pix['horario']) ? $pix['horario'] : date('Y-m-d H:i:s');
            
            // Buscar mensalidade pelo txid
            $stmt_mensalidade = $pdo->prepare("
                SELECT m.*, p.id as professor_id 
                FROM mensalidades m 
                JOIN alunos a ON m.aluno_id = a.id 
                JOIN professores p ON m.professor_id = p.id
                WHERE m.txid_efi = ? 
                AND m.status IN ('pendente', 'atrasado')
            ");
            
            $stmt_mensalidade->execute([$txid_pagamento]);
            $mensalidade = $stmt_mensalidade->fetch();
            
            if ($mensalidade) {
                // Validar valor (com tolerância de 0.10 centavos)
                $valor_esperado = floatval($mensalidade['valor']);
                $diferenca = abs($valor_pagamento - $valor_esperado);
                
                if ($diferenca <= 0.10) {
                    // Atualizar mensalidade como paga
                    $stmt_update = $pdo->prepare("
                        UPDATE mensalidades 
                        SET status = 'pago',
                            data_pagamento = ?,
                            forma_pagamento = 'pix',
                            observacoes = CONCAT(COALESCE(observacoes, ''), '\nPago via PIX automático em ', ?)
                        WHERE id = ?
                    ");
                    
                    $data_pagamento = date('Y-m-d', strtotime($horario_pagamento));
                    
                    $stmt_update->execute([
                        $data_pagamento,
                        date('Y-m-d H:i:s'),
                        $mensalidade['id']
                    ]);
                    
                    // Atualizar log como processado
                    $stmt_log_update = $pdo->prepare("
                        UPDATE webhook_logs 
                        SET processado = TRUE, 
                            mensalidade_id = ?,
                            professor_id = ?,
                            processado_em = NOW()
                        WHERE id = ?
                    ");
                    
                    $stmt_log_update->execute([
                        $mensalidade['id'],
                        $mensalidade['professor_id'],
                        $log_id
                    ]);
                    
                    // Criar notificação de agradecimento (se configurado)
                    // Isso será implementado quando o sistema de templates estiver pronto
                } else {
                    // Valor não confere
                    $stmt_log_update = $pdo->prepare("
                        UPDATE webhook_logs 
                        SET processado = TRUE,
                            mensalidade_id = ?,
                            professor_id = ?,
                            mensagem_erro = ?,
                            processado_em = NOW()
                        WHERE id = ?
                    ");
                    
                    $erro_msg = "Valor não confere. Esperado: R$ " . number_format($valor_esperado, 2, ',', '.') . 
                                ", Recebido: R$ " . number_format($valor_pagamento, 2, ',', '.');
                    
                    $stmt_log_update->execute([
                        $mensalidade['id'],
                        $mensalidade['professor_id'],
                        $erro_msg,
                        $log_id
                    ]);
                }
            } else {
                // Mensalidade não encontrada
                $stmt_log_update = $pdo->prepare("
                    UPDATE webhook_logs 
                    SET processado = TRUE,
                        mensagem_erro = ?,
                        processado_em = NOW()
                    WHERE id = ?
                ");
                
                $stmt_log_update->execute([
                    "Mensalidade não encontrada para txid: " . $txid_pagamento,
                    $log_id
                ]);
            }
        }
    }
    
    // Responder com sucesso
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'processado' => true]);
    
} catch (Exception $e) {
    // Log do erro
    if (isset($log_id)) {
        $stmt_error = $pdo->prepare("
            UPDATE webhook_logs 
            SET processado = TRUE,
                mensagem_erro = ?,
                processado_em = NOW()
            WHERE id = ?
        ");
        $stmt_error->execute([$e->getMessage(), $log_id]);
    }
    
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao processar webhook', 'mensagem' => $e->getMessage()]);
}

