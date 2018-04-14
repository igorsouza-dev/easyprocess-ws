<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

//Busca empresas
$app->get('/empresas', function(Request $request, Response $response) {
    $params = $request->getQueryParams();
    $helperEmpresa = new HelperEmpresa();
    $resultado = $helperEmpresa->getEmpresas($params);
    if ($resultado['status']) {
        return $response->withJson($resultado['entity'], 200);
    } else {
        return $response->withJson($resultado, 404);
    }
});

$app->get('/empresas/{id}', function(Request $request, Response $response) {
    $id = $request->getAttribute('id');

    $helperEmpresa = new HelperEmpresa();
    $empresa = $helperEmpresa->getEmpresa($id);
    if ($empresa['status']) {
        $empresa = $empresa['entity'];
        return $response->withJson($empresa, 200);
    } else {

        return $response->withJson($empresa, 404);
    }
});

$app->post('/empresas', function(Request $request, Response $response) {
    $dados = $request->getParsedBody();

    $helperEmpresa = new HelperEmpresa();

    $restultado = $helperEmpresa->insert($dados);
    if ($restultado['status']) {
        $json = $restultado['entity'];
        return $response->withJson($json, 201);
    } else {
        return $response->withJson($restultado, 500);
    }
});

$app->put('/empresas/{id}', function(Request $request, Response $response) {
    $id = $request->getAttribute('id');
    $dados = $request->getParsedBody();

    $helperEmpresa = new HelperEmpresa();
    $dados[$helperEmpresa->primarykey] = $id;
    $resultado = $helperEmpresa->update($dados);
    if ($resultado['status']) {
        return $response->withJson($resultado, 200);
    } else {
        return $response->withJson($resultado, 404);
    }
});

$app->delete('/empresas/{id}', function(Request $request, Response $response) {
    $dados_body = $request->getParsedBody();

    $id = $request->getAttribute('id');

    $responsavel = '';
    if(array_key_exists('EXCLUIDOPOR', $dados_body)){
        $responsavel = $dados_body['EXCLUIDOPOR'];
    }
    $helperEmpresa = new HelperEmpresa();

    $empresa = $helperEmpresa->getEmpresa($id);
    if($empresa['status']){
        $resultado = $helperEmpresa->deleteById($id, $responsavel);
        if($resultado['status']){
            return $response->withJson(array('message'=>'Exclusão realizada com sucesso.'), 200);
        }else{
            return $response->withJson($resultado, 500);
        }
    }else{
        return $response->withJson($empresa,404);
    }
});
