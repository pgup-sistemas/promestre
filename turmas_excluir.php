<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$professor_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($id) {
    try {
        // Verificar se a turma pertence ao professor
        $stmt = $pdo->prepare("SELECT id FROM turmas WHERE id = ? AND professor_id = ?");
        $stmt->execute([$id, $professor_id]);
        
        if ($stmt->fetch()) {
            // Verificar dependências (Agenda)
            // Se houver aulas agendadas, impedir exclusão ou deletar em cascata?
            // Por segurança, vamos apenas marcar como inativa se houver histórico, ou deletar se estiver vazia.
            
            // Mas o usuário clicou em EXCLUIR. Vamos tentar excluir.
            // As FKs devem tratar cascata ou erro.
            // O DB schema criado define ON DELETE CASCADE para turma_alunos.
            // Para agenda, definimos CONSTRAINT fk_agenda_turma mas não o ON DELETE. O padrão é RESTRICT.
            
            // Verificar se tem agendamentos
            $stmtAgenda = $pdo->prepare("SELECT COUNT(*) FROM agenda WHERE turma_id = ?");
            $stmtAgenda->execute([$id]);
            $count = $stmtAgenda->fetchColumn();
            
            if ($count > 0) {
                // Tem aulas. Melhor não excluir para não perder histórico, ou avisar.
                // Como é uma implementação simples, vamos impedir.
                setFlash("Não é possível excluir esta turma pois existem $count aulas agendadas/realizadas vinculadas a ela. Tente inativar a turma.", 'warning');
            } else {
                $stmtDelete = $pdo->prepare("DELETE FROM turmas WHERE id = ?");
                $stmtDelete->execute([$id]);
                setFlash('Turma excluída com sucesso.', 'success');
            }
            
        } else {
            setFlash('Turma não encontrada.', 'danger');
        }
    } catch (PDOException $e) {
        setFlash('Erro ao excluir: ' . $e->getMessage(), 'danger');
    }
}

redirect('turmas.php');
