<?php
/**
 * Script de diagnóstico para verificar usuários no banco
 */
require_once 'includes/config.php';

try {
    // Verificar todos os usuários no banco
    echo "=== DIAGNÓSTICO DE USUÁRIOS ===\n\n";
    
    // Total de usuários
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM professores");
    $total = $stmt->fetch()['total'];
    echo "Total de usuários no banco: $total\n\n";
    
    // Usuários por status
    $stmt = $pdo->query("SELECT ativo, COUNT(*) as total FROM professores GROUP BY ativo");
    $status = $stmt->fetchAll();
    echo "Distribuição por status:\n";
    foreach ($status as $s) {
        $status_label = $s['ativo'] ? 'Ativo' : 'Inativo';
        echo "  - $status_label: {$s['total']} usuários\n";
    }
    echo "\n";
    
    // Verificar usuários específicos mencionados
    echo "=== USUÁRIOS ESPECÍFICOS ===\n\n";
    
    $emails_para_verificar = [
        'admin@promestre.com',
        'oezios.normando@gmail.com'
    ];
    
    foreach ($emails_para_verificar as $email) {
        $stmt = $pdo->prepare("SELECT id, nome, email, nivel, ativo, data_cadastro FROM professores WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "Usuário: $email\n";
            echo "  - ID: {$user['id']}\n";
            echo "  - Nome: {$user['nome']}\n";
            echo "  - Nível: {$user['nivel']}\n";
            echo "  - Ativo: " . ($user['ativo'] ? 'Sim' : 'Não') . "\n";
            echo "  - Data cadastro: {$user['data_cadastro']}\n\n";
        } else {
            echo "Usuário $email NÃO ENCONTRADO no banco!\n\n";
        }
    }
    
    // Listar todos os usuários (primeiros 20)
    echo "=== PRIMEIROS 20 USUÁRIOS ===\n\n";
    $stmt = $pdo->query("SELECT id, nome, email, nivel, ativo FROM professores ORDER BY data_cadastro DESC LIMIT 20");
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        $status = $user['ativo'] ? 'Ativo' : 'Inativo';
        echo "ID: {$user['id']} | {$user['nome']} | {$user['email']} | {$user['nivel']} | $status\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
