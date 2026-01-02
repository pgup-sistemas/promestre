<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Configurar Contrato';
$professor_id = $_SESSION['user_id'];
$mensagem = '';
$tipo_mensagem = '';

// Buscar modelo existente
$stmt = $pdo->prepare("SELECT * FROM contratos_config WHERE professor_id = ?");
$stmt->execute([$professor_id]);
$contrato = $stmt->fetch();

$conteudo_padrao = "CONTRATO DE PRESTAÇÃO DE SERVIÇOS EDUCACIONAIS

Pelo presente instrumento particular, de um lado:

CONTRATADO: {PROFESSOR_NOME}, doravante denominado PROFESSOR.

CONTRATANTE: {ALUNO_NOME}, CPF nº {ALUNO_CPF}, residente em {ALUNO_ENDERECO}, doravante denominado ALUNO.

As partes acima identificadas têm, entre si, justo e acertado o presente Contrato de Prestação de Serviços Educacionais, que se regerá pelas cláusulas seguintes:

CLÁUSULA 1ª - DO OBJETO
O objeto do presente contrato é a prestação de serviços educacionais de aulas de {TIPO_AULA}, a serem ministradas pelo PROFESSOR ao ALUNO.

CLÁUSULA 2ª - DO VALOR E PAGAMENTO
O ALUNO pagará ao PROFESSOR a mensalidade no valor de R$ {VALOR_MENSALIDADE}, com vencimento todo dia __ de cada mês.

CLÁUSULA 3ª - DA VIGÊNCIA
O presente contrato tem vigência indeterminada, podendo ser rescindido por qualquer uma das partes mediante aviso prévio de 30 dias.

E, por estarem assim justos e contratados, firmam o presente instrumento.

{CIDADE_DATA}

__________________________________________
{PROFESSOR_NOME}

__________________________________________
{ALUNO_NOME}";

$conteudo = $contrato ? $contrato['conteudo'] : $conteudo_padrao;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novo_conteudo = $_POST['conteudo'];
    
    if ($contrato) {
        $stmt = $pdo->prepare("UPDATE contratos_config SET conteudo = ? WHERE professor_id = ?");
        $stmt->execute([$novo_conteudo, $professor_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO contratos_config (professor_id, conteudo) VALUES (?, ?)");
        $stmt->execute([$professor_id, $novo_conteudo]);
    }
    
    $conteudo = $novo_conteudo;
    $contrato = ['conteudo' => $conteudo]; // Atualiza para exibir
    
    setFlash("Modelo de contrato salvo com sucesso!", "success");
    redirect('contratos_config.php');
}

require_once 'includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
    <h1 class="h4 mb-0"><i class="fas fa-file-contract me-2"></i> Configurar Modelo de Contrato</h1>
</div>


<ul class="nav nav-tabs" id="contratoTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-modelo" data-bs-toggle="tab" data-bs-target="#pane-modelo" type="button" role="tab" aria-controls="pane-modelo" aria-selected="true">
            Modelo
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-variaveis" data-bs-toggle="tab" data-bs-target="#pane-variaveis" type="button" role="tab" aria-controls="pane-variaveis" aria-selected="false">
            Variáveis
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-ajuda" data-bs-toggle="tab" data-bs-target="#pane-ajuda" type="button" role="tab" aria-controls="pane-ajuda" aria-selected="false">
            Como funciona
        </button>
    </li>
</ul>

<div class="tab-content border border-top-0 rounded-bottom p-3" id="contratoTabsContent">
    <div class="tab-pane fade show active" id="pane-modelo" role="tabpanel" aria-labelledby="tab-modelo" tabindex="0">
        <div class="card shadow-sm">
            <div class="card-header bg-light py-2">
                <h6 class="m-0 font-weight-bold text-primary">Modelo de Contrato</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="conteudo" class="form-label">Edite o texto do contrato abaixo:</label>
                        <textarea class="form-control" id="conteudo" name="conteudo" rows="20" style="font-family: monospace;"><?php echo htmlspecialchars($conteudo); ?></textarea>
                        <div class="form-text mt-2">
                            Dica: veja a aba <strong>Variáveis</strong> para copiar e colar os placeholders.
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Salvar Modelo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="pane-variaveis" role="tabpanel" aria-labelledby="tab-variaveis" tabindex="0">
        <div class="card shadow-sm">
            <div class="card-header bg-light py-2">
                <h6 class="m-0 font-weight-bold text-primary">Variáveis disponíveis</h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <strong>Serão substituídas automaticamente:</strong><br>
                    <code>{ALUNO_NOME}</code> - Nome do aluno<br>
                    <code>{ALUNO_CPF}</code> - CPF do aluno<br>
                    <code>{ALUNO_ENDERECO}</code> - Endereço do aluno<br>
                    <code>{CONTRATANTE_NOME}</code> - Nome do contratante (aluno ou responsável)<br>
                    <code>{CONTRATANTE_CPF}</code> - CPF do contratante (aluno ou responsável)<br>
                    <code>{CONTRATANTE_ENDERECO}</code> - Endereço do contratante<br>
                    <code>{CONTRATANTE_TELEFONE}</code> - Telefone do contratante<br>
                    <code>{CONTRATANTE_WHATSAPP}</code> - WhatsApp do contratante<br>
                    <code>{CONTRATANTE_PARENTESCO}</code> - Parentesco do contratante com o aluno (quando houver responsável)<br>
                    <code>{ALUNO_MENOR_DE_IDADE}</code> - SIM/NÃO, conforme data de nascimento<br>
                    <code>{TIPO_AULA}</code> - Nome do tipo de aula contratado<br>
                    <code>{VALOR_MENSALIDADE}</code> - Valor padrão da aula<br>
                    <code>{PROFESSOR_NOME}</code> - Seu nome<br>
                    <code>{CIDADE_DATA}</code> - Data atual por extenso
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="pane-ajuda" role="tabpanel" aria-labelledby="tab-ajuda" tabindex="0">
        <div class="card shadow-sm">
            <div class="card-header bg-light py-2">
                <h6 class="m-0 font-weight-bold text-primary">Como funciona</h6>
            </div>
            <div class="card-body">
                <p>Aqui você define o texto padrão para seus contratos.</p>
                <p>Ao clicar em <strong>"Gerar Contrato"</strong> na lista de alunos, o sistema pegará este texto e substituirá as variáveis pelos dados reais do aluno selecionado.</p>
                <hr>
                <p class="mb-0"><small>Dica: Você pode copiar e colar um modelo de contrato que já utilize e apenas inserir as variáveis onde deseja que os dados sejam preenchidos automaticamente.</small></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
