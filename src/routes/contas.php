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
            return $response->withJson(array('message'=>'Exclusão realizada com sucesso.'), 200);
        }else{
            return $response->withJson($resultado, 500);
        }
    }else{
        return $response->withJson($conta,404);
    }
});