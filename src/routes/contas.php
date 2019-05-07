<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


//Busca contas
$app->get('/contas', function(Request $request, Response $response) {
    $params = $request->getQueryParams();
    $helperContas = new HelperContas();
    $resultado = $helperContas->getContas($params);
    if ($resultado['status']) {
        return $response->withJson($resultado['entity'], 200);
    } else {
        return $response->withJson($resultado, 404);
    }
});


//Busca conta
$app->get('/contas/{id}', function(Request $request, Response $response) {
    $id = $request->getAttribute('id');

    $helperContas = new HelperContas();
    $conta = $helperContas->getConta($id);
    if ($conta['status']) {
        $conta = $conta['entity'];
        return $response->withJson($conta, 200);
    } else {

        return $response->withJson($conta, 404);
    }
});


//Cria nova conta
$app->post('/contas', function(Request $request, Response $response) {
    $dados = $request->getParsedBody();

    $helperContas = new HelperContas();

    $resultado = $helperContas->insert($dados);
    if ($resultado['status']) {
        $json = $resultado['entity'];
        return $response->withJson($json, 201);
    } else {
        return $response->withJson($resultado, 500);
    }
});


//Atualiza conta
$app->put('/contas/{id}', function(Request $request, Response $response) {
    $id = $request->getAttribute('id');
    $dados = $request->getParsedBody();

    $helperContas = new HelperContas();
    $dados[$helperContas->primarykey] = $id;
    $resultado = $helperContas->update($dados);
    if ($resultado['status']) {
        return $response->withJson($resultado, 200);
    } else {
        return $response->withJson($resultado, 404);
    }
});


//Deleta conta
$app->delete('/contas/{id}', function(Request $request, Response $response) {
    $dados_body = $request->getParsedBody();

    $id = $request->getAttribute('id');

    $responsavel = '';
    if(array_key_exists('EXCLUIDOPOR', $dados_body)){
        $responsavel = $dados_body['EXCLUIDOPOR'];
    }
    $helperContas = new HelperContas();

    $conta = $helperContas->getConta($id);
    if($conta['status']){
        $resultado = $helperContas->deleteById($id, $responsavel);
        if($resultado['status']){
            $helperCompromisso = new HelperCompromisso();
            $helperCompromisso->delete('CODCONTA = '.$id." AND EXCLUIDO = 'N'");
            return $response->withJson(array('message'=>'Exclusão realizada com sucesso.'), 200);
        }else{
            return $response->withJson($resultado, 500);
        }
    }else{
        return $response->withJson($conta,404);
    }
});

//Busca de valores totais por mês
$app->get('/contas/{codempresa}/saldo/{ano}/{mes}', function(Request $request, Response $response) {
    $codemp = $request->getAttribute('codempresa');
    $ano = $request->getAttribute('ano');
    $mes = $request->getAttribute('mes');

    $helperContas = new HelperContas();

    $totais = $helperContas->getTotaisPorMes($codemp, $ano, $mes);
    if ($totais['status']) {
        $totais = $totais['entity'];
        return $response->withJson($totais, 200);
    } else {

        return $response->withJson($totais, 404);
    }
});


//Busca de valores totais em um periodo
$app->get('/contas/{codempresa}/saldo/{ano}/{mesini}/{mesfim}', function(Request $request, Response $response) {
    $codemp = $request->getAttribute('codempresa');
    $ano = $request->getAttribute('ano');
    $mesini = $request->getAttribute('mesini');
    $mesfim = $request->getAttribute('mesfim');

    $helperContas = new HelperContas();

    $totais = $helperContas->getTotaisPorMeses($codemp, $ano, $mesini, $mesfim);
    if ($totais['status']) {
        $totais = $totais['entity'];
        return $response->withJson($totais, 200);
    } else {

        return $response->withJson($totais, 404);
    }
});


//Busca de valores totais em um ano
$app->get('/contas/{codempresa}/saldo/{ano}', function(Request $request, Response $response) {
    $codemp = $request->getAttribute('codempresa');
    $ano = $request->getAttribute('ano');

    $helperContas = new HelperContas();

    $totais = $helperContas->getTotaisPorMeses($codemp, $ano, 1, 12);
    if ($totais['status']) {
        $totais = $totais['entity'];
        return $response->withJson($totais, 200);
    } else {

        return $response->withJson($totais, 404);
    }
});