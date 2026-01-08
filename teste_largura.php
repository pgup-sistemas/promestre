<?php
/**
 * Script de diagnóstico para comparar largura das páginas
 */
require_once 'includes/config.php';

// Simular que estamos logados como professor
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Professor Teste';
$_SESSION['user_nivel'] = 'professor';
$_SESSION['user_slug'] = 'professor-teste';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Largura - Dashboard vs Alunos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .width-test {
            border: 2px solid red;
            background: rgba(255,0,0,0.1);
            margin: 10px 0;
            padding: 10px;
        }
        .container-comparison {
            border: 2px solid blue;
            background: rgba(0,0,255,0.1);
            margin: 10px 0;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid px-4">
        <h2>Teste de Container</h2>
        
        <div class="width-test">
            <strong>Este elemento está dentro do container-fluid px-4</strong><br>
            Largura disponível: 100% com padding de 1rem em cada lado
        </div>
        
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="width-test">
                    <strong>Card do Dashboard (col-xl-3 col-md-6)</strong><br>
                    Este é o tamanho dos cards do dashboard
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="width-test">
                    <strong>Card do Dashboard (col-xl-3 col-md-6)</strong><br>
                    Este é o tamanho dos cards do dashboard
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="width-test">
                    <strong>Card do Dashboard (col-xl-3 col-md-6)</strong><br>
                    Este é o tamanho dos cards do dashboard
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="width-test">
                    <strong>Card do Dashboard (col-xl-3 col-md-6)</strong><br>
                    Este é o tamanho dos cards do dashboard
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12 col-md-6">
                <div class="width-test">
                    <strong>Filtro de Alunos (col-12 col-md-6)</strong><br>
                    Este é o tamanho dos filtros da página alunos
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="width-test">
                    <strong>Filtro de Alunos (col-12 col-md-4)</strong><br>
                    Este é o tamanho dos filtros da página alunos
                </div>
            </div>
            <div class="col-12 col-md-2">
                <div class="width-test">
                    <strong>Botão Filtrar (col-12 col-md-2)</strong><br>
                    Este é o tamanho do botão filtrar
                </div>
            </div>
        </div>
        
        <div class="alert alert-info">
            <h4>Observações:</h4>
            <ul>
                <li>Ambos usam o mesmo container-fluid px-4</li>
                <li>O dashboard usa col-xl-3 (25% em telas grandes) para os cards</li>
                <li>A página alunos usa col-md-6 (50% em telas médias) para os filtros</li>
                <li>A diferença pode ser perceptual - os cards pequenos podem parecer que a página é mais estreita</li>
            </ul>
        </div>
    </div>
</body>
</html>