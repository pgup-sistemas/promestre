<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$professor_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$page_title = $id ? 'Editar Agendamento' : 'Novo Agendamento';
$error = '';

// Buscar alunos
$stmt = $pdo->prepare("SELECT id, nome FROM alunos WHERE professor_id = ? AND status = 'ativo' ORDER BY nome");
$stmt->execute([$professor_id]);
$alunos = $stmt->fetchAll();

// Buscar turmas
$stmt = $pdo->prepare("SELECT id, nome FROM turmas WHERE professor_id = ? AND ativo = 1 ORDER BY nome");
$stmt->execute([$professor_id]);
$turmas = $stmt->fetchAll();

$evento = [
    'titulo' => '',
    'tipo_agendamento' => 'individual', // individual, turma
    'aluno_id' => '',
    'turma_id' => '',
    'data_inicio_date' => date('Y-m-d'),
    'data_inicio_time' => date('H:00'),
    'duracao' => 60,
    'status' => 'agendado',
    'observacoes' => '',
    'recorrencia_id' => null
];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM agenda WHERE id = ? AND professor_id = ?");
    $stmt->execute([$id, $professor_id]);
    $evento_db = $stmt->fetch();
    
    if ($evento_db) {
        $evento['titulo'] = $evento_db['titulo'];
        $evento['aluno_id'] = $evento_db['aluno_id'];
        $evento['turma_id'] = $evento_db['turma_id']; // Novo campo
        $evento['tipo_agendamento'] = $evento_db['turma_id'] ? 'turma' : 'individual';
        $evento['recorrencia_id'] = $evento_db['recorrencia_id'];
        
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
    $tipo_agendamento = $_POST['tipo_agendamento'];
    $aluno_id = ($tipo_agendamento == 'individual' && !empty($_POST['aluno_id'])) ? $_POST['aluno_id'] : null;
    $turma_id = ($tipo_agendamento == 'turma' && !empty($_POST['turma_id'])) ? $_POST['turma_id'] : null;
    
    $data_date = $_POST['data_date'];
    $data_time = $_POST['data_time'];
    $duracao = (int)$_POST['duracao'];
    $status = $_POST['status'];
    $observacoes = clean($_POST['observacoes']);

    // Recorrência fields
    $repetir = isset($_POST['repetir']) ? true : false;
    $frequencia = $_POST['frequencia'] ?? 'semanal';
    $repetir_ate = $_POST['repetir_ate'] ?? null;

    if (empty($titulo) || empty($data_date) || empty($data_time)) {
        $error = 'Título, Data e Hora são obrigatórios.';
    } else {
        try {
            // Preparar dados base
            $data_inicio_base = "$data_date $data_time";
            $pdo->beginTransaction();

            if ($id) {
                // EDIÇÃO (Sem suporte a alterar recorrência em massa por enquanto, edita só este)
                // TODO: Perguntar se quer editar a série inteira
                $data_fim = date('Y-m-d H:i:s', strtotime("$data_inicio_base +$duracao minutes"));
                
                $stmt = $pdo->prepare("UPDATE agenda SET aluno_id = ?, turma_id = ?, titulo = ?, data_inicio = ?, data_fim = ?, status = ?, observacoes = ? WHERE id = ? AND professor_id = ?");
                $stmt->execute([$aluno_id, $turma_id, $titulo, $data_inicio_base, $data_fim, $status, $observacoes, $id, $professor_id]);
                
                $msg = 'Agendamento atualizado!';

            } else {
                // CRIAÇÃO (Com suporte a recorrência)
                
                $eventos_para_criar = [];
                
                // Primeiro evento (sempre cria)
                $eventos_para_criar[] = $data_inicio_base;

                // Gerar recorrências
                if ($repetir && $repetir_ate) {
                    $recorrencia_id = uniqid('rec_');
                    $current_date = strtotime($data_inicio_base);
                    $end_date = strtotime($repetir_ate . ' 23:59:59');
                    
                    while (true) {
                        // Calcular próxima data
                        if ($frequencia == 'diaria') {
                            $current_date = strtotime('+1 day', $current_date);
                        } elseif ($frequencia == 'semanal') {
                            $current_date = strtotime('+1 week', $current_date);
                        } elseif ($frequencia == 'quinzenal') {
                            $current_date = strtotime('+2 weeks', $current_date);
                        } elseif ($frequencia == 'mensal') {
                            $current_date = strtotime('+1 month', $current_date);
                        }
                        
                        if ($current_date > $end_date) break;
                        
                        $eventos_para_criar[] = date('Y-m-d H:i:s', $current_date);
                    }
                } else {
                    $recorrencia_id = null;
                }

                $stmt = $pdo->prepare("INSERT INTO agenda (professor_id, aluno_id, turma_id, titulo, data_inicio, data_fim, status, observacoes, recorrencia_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

                foreach ($eventos_para_criar as $dt_inicio) {
                    $dt_fim = date('Y-m-d H:i:s', strtotime("$dt_inicio +$duracao minutes"));
                    $stmt->execute([$professor_id, $aluno_id, $turma_id, $titulo, $dt_inicio, $dt_fim, $status, $observacoes, $recorrencia_id]);
                }

                $qtd = count($eventos_para_criar);
                $msg = $qtd > 1 ? "$qtd agendamentos criados com sucesso!" : 'Agendamento criado!';
            }
            
            $pdo->commit();
            setFlash($msg, 'success');
            redirect('agenda.php');
            
        } catch (PDOException $e) {
            $pdo->rollBack();
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
                    
                    <!-- Tipo de Agendamento -->
                    <div class="row mb-3">
                        <label class="form-label">Tipo de Aula</label>
                        <div class="col-12">
                            <div class="btn-group w-100 w-md-auto" role="group">
                                <input type="radio" class="btn-check" name="tipo_agendamento" id="tipo_individual" value="individual" <?php echo $evento['tipo_agendamento'] == 'individual' ? 'checked' : ''; ?> autocomplete="off">
                                <label class="btn btn-outline-primary" for="tipo_individual"><i class="fas fa-user me-2"></i> Individual</label>

                                <input type="radio" class="btn-check" name="tipo_agendamento" id="tipo_turma" value="turma" <?php echo $evento['tipo_agendamento'] == 'turma' ? 'checked' : ''; ?> autocomplete="off">
                                <label class="btn btn-outline-primary" for="tipo_turma"><i class="fas fa-users me-2"></i> Turma</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título *</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo htmlspecialchars($evento['titulo']); ?>" placeholder="Ex: Aula de Piano" required>
                    </div>
                    
                    <!-- Seleção Dinâmica (Aluno ou Turma) -->
                    <div class="mb-3" id="div_aluno">
                        <label for="aluno_id" class="form-label">Aluno</label>
                        <select class="form-select" id="aluno_id" name="aluno_id">
                            <option value="">Selecione um aluno...</option>
                            <?php foreach ($alunos as $a): ?>
                                <option value="<?php echo $a['id']; ?>" <?php echo $evento['aluno_id'] == $a['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($a['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="div_turma">
                        <label for="turma_id" class="form-label">Turma</label>
                        <select class="form-select" id="turma_id" name="turma_id">
                            <option value="">Selecione uma turma...</option>
                            <?php foreach ($turmas as $t): ?>
                                <option value="<?php echo $t['id']; ?>" <?php echo $evento['turma_id'] == $t['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="data_date" class="form-label">Data Início *</label>
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

                    <!-- Seção Recorrência (Apenas Criação) -->
                    <?php if (!$id): ?>
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="repetir" name="repetir">
                                <label class="form-check-label fw-bold" for="repetir">Repetir este agendamento?</label>
                            </div>
                            
                            <div id="recorrencia_options" class="mt-3 d-none">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="frequencia" class="form-label">Frequência</label>
                                        <select class="form-select" id="frequencia" name="frequencia">
                                            <option value="semanal">Semanalmente (Toda semana)</option>
                                            <option value="quinzenal">Quinzenalmente (A cada 2 semanas)</option>
                                            <option value="mensal">Mensalmente (Todo mês)</option>
                                            <option value="diaria">Diariamente</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="repetir_ate" class="form-label">Repetir até</label>
                                        <input type="date" class="form-control" id="repetir_ate" name="repetir_ate">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoIndividual = document.getElementById('tipo_individual');
    const tipoTurma = document.getElementById('tipo_turma');
    const divAluno = document.getElementById('div_aluno');
    const divTurma = document.getElementById('div_turma');
    const alunoInput = document.getElementById('aluno_id');
    const turmaInput = document.getElementById('turma_id');

    function toggleTipo() {
        if (tipoTurma.checked) {
            divAluno.classList.add('d-none');
            divTurma.classList.remove('d-none');
            alunoInput.value = ''; // Limpar seleção anterior
        } else {
            divAluno.classList.remove('d-none');
            divTurma.classList.add('d-none');
            turmaInput.value = ''; // Limpar seleção anterior
        }
    }

    tipoIndividual.addEventListener('change', toggleTipo);
    tipoTurma.addEventListener('change', toggleTipo);
    
    // Run on load
    toggleTipo();

    // Recorrência Toggle
    const checkRepetir = document.getElementById('repetir');
    const divRecorrencia = document.getElementById('recorrencia_options');
    const inputRepetirAte = document.getElementById('repetir_ate');

    if (checkRepetir) {
        checkRepetir.addEventListener('change', function() {
            if (this.checked) {
                divRecorrencia.classList.remove('d-none');
                inputRepetirAte.required = true;
            } else {
                divRecorrencia.classList.add('d-none');
                inputRepetirAte.required = false;
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
