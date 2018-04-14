<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

//Busca processos
$app->get('/compromissos', function(Request $request, Response $response) {
    $params = $request->getQueryParams();
    $helperCompromisso = new HelperCompromisso();
    $resultado = $helperCompromisso->getCompromissos($params);
    if ($resultado['status']) {
        return $response->withJson($resultado['entity'], 200);
    } else {
        return $response->withJson($resultado, 404);
    }
});


$app->get('/compromissos/{id}', function(Request $request, Response $response) {
    $id = $request->getAttribute('id');

    $helperCompromisso = new HelperCompromisso();
    $compromisso = $helperCompromisso->getCompromisso($id);
    if ($compromisso['status']) {
        $compromisso = $compromisso['entity'];
        return $response->withJson($compromisso, 200);
    } else {

        return $response->withJson($compromisso, 404);
    }
});

$app->post('/compromissos', function (Request $request, Response $response) {
    $dados = $request->getParsedBody();

    $helperCompromisso = new HelperCompromisso();

    $restultado = $helperCompromisso->insert($dados);
    if ($restultado['status']) {
        $json = $restultado['entity'];
        return $response->withJson($json, 201);
    } else {
        return $response->withJson($restultado, 500);
    }
});


$app->put('/compromissos/{id}', function(Request $request, Response $response) {
    $id = $request->getAttribute('id');
    $dados = $request->getParsedBody();

    $helperCompromisso = new HelperCompromisso();
    $compromisso = $helperCompromisso->getCompromisso($id);

    if ($compromisso['status']) {
        $dados[$helperCompromisso->primarykey] = $id;
        $resultado = $helperCompromisso->update($dados);

        if ($resultado['status']) {
            return $response->withJson($resultado, 200);
        } else {
            return $response->withJson($resultado, 404);
        }
    } else {
        return $response->withJson($compromisso,404);
    }
});

$app->delete('/compromissos/{id}', function(Request $request, Response $response) {
    $dados_body = $request->getParsedBody();

    $id = $request->getAttribute('id');

    $responsavel = '';
    if(array_key_exists('EXCLUIDOPOR', $dados_body)){
        $responsavel = $dados_body['EXCLUIDOPOR'];
    }
    $helperCompromisso = new HelperCompromisso();

    $compromisso = $helperCompromisso->getCompromisso($id);
    if($compromisso['status']){
        $resultado = $helperCompromisso->deleteById($id, $responsavel);
        if($resultado['status']){
            return $response->withJson(array('message'=>'Exclusão realizada com sucesso.'), 200);
        }else{
            return $response->withJson($resultado, 500);
        }
    }else{
        return $response->withJson($compromisso,404);
    }
});


//busca apenas os compromissos pendentes da empresa informada
$app->get('/compromissos_empresa/{id}/pendentes', function (Request $request, Response $response) {
    $id = $request->getAttribute('id');

    $helperCompromisso = new HelperCompromisso();
    $params = array(
        'EXCLUIDO'=>'N',
        'EXECUTADA'=>0,
        'DATASOLICITACAO'=>'>'.date('Y-m-d'),
        'CODEMPRESA'=>$id
    );
    $resultado = $helperCompromisso->getCompromissos($params);
    if ($resultado['status']) {
        return $response->withJson($resultado['entity'], 200);
    } else {
        return $response->withJson($resultado, 404);
    }
});

//busca os compromissos da empresa informada
$app->get('/compromissos_empresa/{id}', function (Request $request, Response $response) {
    $id = $request->getAttribute('id');
    $params = $request->getQueryParams();

    $params['EXCLUIDO'] = 'N';
    $params['CODEMPRESA'] = $id;
    $helperCompromisso = new HelperCompromisso();

    $resultado = $helperCompromisso->getCompromissos($params);
    if ($resultado['status']) {
        return $response->withJson($resultado['entity'], 200);
    } else {
        return $response->withJson($resultado, 404);
    }
});
