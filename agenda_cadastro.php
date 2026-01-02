<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$professor_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$page_title = $id ? 'Editar Agendamento' : 'Novo Agendamento';
$error = '';

// Buscar alunos para select
$stmt = $pdo->prepare("SELECT id, nome FROM alunos WHERE professor_id = ? AND status = 'ativo' ORDER BY nome");
$stmt->execute([$professor_id]);
$alunos = $stmt->fetchAll();

$evento = [
    'titulo' => '',
    'aluno_id' => '',
    'data_inicio_date' => date('Y-m-d'),
    'data_inicio_time' => date('H:00'),
    'duracao' => 60, // minutos
    'status' => 'agendado',
    'observacoes' => ''
];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM agenda WHERE id = ? AND professor_id = ?");
    $stmt->execute([$id, $professor_id]);
    $evento_db = $stmt->fetch();
    
    if ($evento_db) {
        $evento['titulo'] = $evento_db['titulo'];
        $evento['aluno_id'] = $evento_db['aluno_id'];
        $evento['data_inicio_date'] = date('Y-m-d', strtotime($evento_db['data_inicio']));
        $evento['data_inicio_time'] = date('H:i', strtotime($evento_db['data_inicio']));
        
        $inicio = strtotime($evento_db['data_inicio']);
        $fim = strtotime($evento_db['data_fim']);
        $evento['duracao'] = ($fim - $inicio) / 60;
        
        $evento['status'] = $evento_db['status'];
        $evento['observacoes'] = $evento_db['observacoes'];
    } else {
        setFlash('Agendamento não encontrado.', 'danger');
        redirect('agenda.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = clean($_POST['titulo']);
    $aluno_id = !empty($_POST['aluno_id']) ? $_POST['aluno_id'] : null;
    $data_date = $_POST['data_date'];
    $data_time = $_POST['data_time'];
    $duracao = (int)$_POST['duracao'];
    $status = $_POST['status'];
    $observacoes = clean($_POST['observacoes']);

    if (empty($titulo) || empty($data_date) || empty($data_time)) {
        $error = 'Título, Data e Hora são obrigatórios.';
    } else {
        $data_inicio = "$data_date $data_time";
        $data_fim = date('Y-m-d H:i:s', strtotime("$data_inicio +$duracao minutes"));

        try {
            if ($id) {
                // Atualizar
                $stmt = $pdo->prepare("UPDATE agenda SET aluno_id = ?, titulo = ?, data_inicio = ?, data_fim = ?, status = ?, observacoes = ? WHERE id = ? AND professor_id = ?");
                $stmt->execute([$aluno_id, $titulo, $data_inicio, $data_fim, $status, $observacoes, $id, $professor_id]);
                $msg = 'Agendamento atualizado!';
            } else {
                // Inserir
                $stmt = $pdo->prepare("INSERT INTO agenda (professor_id, aluno_id, titulo, data_inicio, data_fim, status, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$professor_id, $aluno_id, $titulo, $data_inicio, $data_fim, $status, $observacoes]);
                $msg = 'Agendamento criado!';
            }
            
            setFlash($msg, 'success');
            redirect('agenda.php');
            
        } catch (PDOException $e) {
            $error = 'Erro ao salvar: ' . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-calendar-plus me-2"></i> <?php echo $page_title; ?></h1>
    <a href="agenda.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Voltar</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Dados do Agendamento</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título *</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo htmlspecialchars($evento['titulo']); ?>" placeholder="Ex: Aula de Piano" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="aluno_id" class="form-label">Aluno (Opcional)</label>
                        <select class="form-select" id="aluno_id" name="aluno_id">
                            <option value="">Selecione...</option>
                            <?php foreach ($alunos as $a): ?>
                                <option value="<?php echo $a['id']; ?>" <?php echo $evento['aluno_id'] == $a['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($a['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="data_date" class="form-label">Data *</label>
                            <input type="date" class="form-control" id="data_date" name="data_date" value="<?php echo $evento['data_inicio_date']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="data_time" class="form-label">Hora *</label>
                            <input type="time" class="form-control" id="data_time" name="data_time" value="<?php echo $evento['data_inicio_time']; ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="duracao" class="form-label">Duração (minutos)</label>
                        <input type="number" class="form-control" id="duracao" name="duracao" value="<?php echo $evento['duracao']; ?>" step="5" min="5">
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="agendado" <?php echo $evento['status'] == 'agendado' ? 'selected' : ''; ?>>Agendado</option>
                            <option value="realizado" <?php echo $evento['status'] == 'realizado' ? 'selected' : ''; ?>>Realizado</option>
                            <option value="cancelado" <?php echo $evento['status'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?php echo htmlspecialchars($evento['observacoes']); ?></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="agenda.php" class="btn btn-light me-md-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
