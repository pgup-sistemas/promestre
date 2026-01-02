<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$aluno_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$professor_id = $_SESSION['user_id'];

if (!$aluno_id) {
    die("ID do aluno não fornecido.");
}

// Buscar dados do aluno e tipo de aula
$stmt = $pdo->prepare("
    SELECT a.*, t.nome as tipo_aula_nome, t.preco_padrao 
    FROM alunos a 
    LEFT JOIN tipos_aula t ON a.tipo_aula_id = t.id 
    WHERE a.id = ? AND a.professor_id = ?
");
$stmt->execute([$aluno_id, $professor_id]);
$aluno = $stmt->fetch();

if (!$aluno) {
    die("Aluno não encontrado ou não pertence a você.");
}

// Buscar dados do professor
$stmt = $pdo->prepare("SELECT * FROM professores WHERE id = ?");
$stmt->execute([$professor_id]);
$professor = $stmt->fetch();

// Buscar modelo de contrato
$stmt = $pdo->prepare("SELECT conteudo FROM contratos_config WHERE professor_id = ?");
$stmt->execute([$professor_id]);
$modelo = $stmt->fetch();

if (!$modelo) {
    // Redireciona para configuração se não tiver modelo
    setFlash("Por favor, configure seu modelo de contrato primeiro.", "warning");
    redirect('contratos_config.php');
}

$texto = $modelo['conteudo'];

// Formatar data atual por extenso (ex: São Paulo, 31 de Dezembro de 2025)
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
$data_extenso = strftime('Data de emissão: %d de %B de %Y', strtotime('today'));
// Fallback simples caso setlocale falhe no ambiente Windows/XAMPP
$meses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
    7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];
$dia = date('d');
$mes = $meses[(int)date('m')];
$ano = date('Y');
$data_extenso = "$dia de $mes de $ano";


// Substituições
$substituicoes = [
    '{ALUNO_NOME}' => $aluno['nome'],
    '{ALUNO_CPF}' => $aluno['cpf'] ?: '__________________',
    '{ALUNO_ENDERECO}' => $aluno['endereco'] ?: '__________________________________________________',
    '{TIPO_AULA}' => $aluno['tipo_aula_nome'] ?: '_____________',
    '{VALOR_MENSALIDADE}' => number_format($aluno['preco_padrao'] ?: 0, 2, ',', '.'),
    '{PROFESSOR_NOME}' => $professor['nome'],
    '{CIDADE_DATA}' => $data_extenso
];

foreach ($substituicoes as $chave => $valor) {
    $texto = str_replace($chave, $valor, $texto);
}

// Converter quebras de linha para <br>
$texto_html = nl2br(htmlspecialchars($texto));

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato - <?php echo htmlspecialchars($aluno['nome']); ?></title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.6;
            margin: 0;
            padding: 2cm;
            color: #000;
        }
        .contrato-container {
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            text-align: center;
            font-size: 18pt;
            text-transform: uppercase;
            margin-bottom: 2cm;
        }
        p {
            margin-bottom: 1em;
            text-align: justify;
        }
        .assinaturas {
            margin-top: 3cm;
            display: flex;
            justify-content: space-between;
        }
        .assinatura {
            width: 45%;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 10px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
            }
            .contrato-container {
                width: 100%;
            }
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-family: sans-serif;
            font-size: 14px;
            cursor: pointer;
            border: none;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .toolbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: #f8f9fa;
            padding: 10px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        .page-content {
            margin-top: 60px; /* Espaço para a toolbar */
        }
    </style>
</head>
<body>

    <div class="toolbar no-print">
        <button onclick="window.print()" class="btn"><i class="fas fa-print"></i> Imprimir / Salvar PDF</button>
        <a href="alunos.php" class="btn btn-secondary">Voltar</a>
    </div>

    <div class="page-content contrato-container">
        <!-- Renderiza o texto processado, mas mantendo a formatação HTML básica -->
        <?php echo $texto_html; ?>
    </div>

</body>
</html>
