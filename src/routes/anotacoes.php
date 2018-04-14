<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


//Busca anotações
$app->get('/anotacoes', function(Request $request, Response $response) {
    $params = $request->getQueryParams();
    $helperAnotacoes = new HelperAnotacoes();
    $resultado = $helperAnotacoes->getAnotacoes($params);
    if ($resultado['status']) {
        return $response->withJson($resultado['entity'], 200);
    } else {
        return $response->withJson($resultado, 404);
    }
});

//Busca anotação
$app->get('/anotacoes/{id}', function(Request $request, Response $response) {
    $id = $request->getAttribute('id');

    $helperAnotacoes = new HelperAnotacoes();
    $anotacao = $helperAnotacoes->getAnotacao($id);
    if ($anotacao['status']) {
        $anotacao = $anotacao['entity'];
        return $response->withJson($anotacao, 200);
    } else {

        return $response->withJson($anotacao, 404);
    }
});

//Cria nova anotacao
$app->post('/anotacoes', function(Request $request, Response $response) {
    $dados = $request->getParsedBody();

    $helperAnotacoes = new HelperAnotacoes();

    $resultado = $helperAnotacoes->insert($dados);
    if ($resultado['status']) {
        $json = $resultado['entity'];
        return $response->withJson($json, 201);
    } else {
        return $response->withJson($resultado, 500);
    }
});

//Atualiza anotacao
$app->put('/anotacoes/{id}', function(Request $request, Response $response) {
    $id = $request->getAttribute('id');
    $dados = $request->getParsedBody();

    $helperAnotacoes = new HelperAnotacoes();
    $dados[$helperAnotacoes->primarykey] = $id;
    $resultado = $helperAnotacoes->update($dados);
    if ($resultado['status']) {
        return $response->withJson($resultado, 200);
    } else {
        return $response->withJson($resultado, 404);
    }
});

//Deleta anotacao
$app->delete('/anotacoes/{id}', function(Request $request, Response $response) {
    $dados_body = $request->getParsedBody();

    $id = $request->getAttribute('id');

    $responsavel = '';
    if(array_key_exists('EXCLUIDOPOR', $dados_body)){
        $responsavel = $dados_body['EXCLUIDOPOR'];
    }
    $helperAnotacoes = new HelperAnotacoes();

    $anotacao = $helperAnotacoes->getAnotacao($id);
    if($anotacao['status']){
        $resultado = $helperAnotacoes->deleteById($id, $responsavel);
        if($resultado['status']){
            return $response->withJson(array('message'=>'Exclusão realizada com sucesso.'), 200);
        }else{
            return $response->withJson($resultado, 500);
        }
    }else{
        return $response->withJson($anotacao,404);
    }
});
