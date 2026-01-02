<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('templates_mensagem.php');
}

$professor_id = $_SESSION['user_id'];
$nome = clean($_POST['nome']);
$tipo = clean($_POST['tipo']);
$template = $_POST['template'];
$ativo = isset($_POST['ativo']) ? 1 : 0;
$id = isset($_POST['id']) ? (int)$_POST['id'] : null;

if (empty($nome) || empty($template)) {
    setFlash('Preencha todos os campos obrigatórios.', 'danger');
    redirect('templates_mensagem.php');
}

try {
    if ($id) {
        // Atualizar
        $stmt = $pdo->prepare("
            UPDATE templates_mensagem 
            SET nome = ?, tipo = ?, template = ?, ativo = ? 
            WHERE id = ? AND professor_id = ?
        ");
        $stmt->execute([$nome, $tipo, $template, $ativo, $id, $professor_id]);
        setFlash('Template atualizado com sucesso!', 'success');
    } else {
        // Inserir
        $stmt = $pdo->prepare("
            INSERT INTO templates_mensagem (professor_id, nome, tipo, template, ativo) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$professor_id, $nome, $tipo, $template, $ativo]);
        setFlash('Template criado com sucesso!', 'success');
    }
} catch (PDOException $e) {
    setFlash('Erro ao salvar template: ' . $e->getMessage(), 'danger');
}

redirect('templates_mensagem.php');

