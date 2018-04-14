<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

//Busca clientes
$app->get('/clientes', function(Request $request, Response $response) {
    $params = $request->getQueryParams();
    $helperClientes = new HelperClientes();
    $resultado = $helperClientes->getClientes($params);
    if ($resultado['status']) {
        return $response->withJson($resultado['entity'], 200);
    } else {
        return $response->withJson($resultado, 404);
    }
});

//Busca cliente
$app->get('/clientes/{id}', function(Request $request, Response $response) {
    $id = $request->getAttribute('id');

    $helperClientes = new HelperClientes();
    $cliente = $helperClientes->getCliente($id);
    if ($cliente['status']) {
        $cliente = $cliente['entity'];
        return $response->withJson($cliente, 200);
    } else {

        return $response->withJson($cliente, 404);
    }
});


//Cria novo cliente
$app->post('/clientes', function(Request $request, Response $response) {
    $dados = $request->getParsedBody();

    $helperClientes = new HelperClientes();

    $resultado = $helperClientes->insert($dados);
    if ($resultado['status']) {
        $json = $resultado['entity'];
        return $response->withJson($json, 201);
    } else {
        return $response->withJson($resultado, 500);
    }
});

//Atualiza cliente
$app->put('/clientes/{id}', function(Request $request, Response $response) {
    $id = $request->getAttribute('id');
    $dados = $request->getParsedBody();

    $helperClientes = new HelperClientes();
    $dados[$helperClientes->primarykey] = $id;
    $resultado = $helperClientes->update($dados);
    if ($resultado['status']) {
        return $response->withJson($resultado, 200);
    } else {
        return $response->withJson($resultado, 404);
    }
});

//Deleta cliente
$app->delete('/clientes/{id}', function(Request $request, Response $response) {
    $dados_body = $request->getParsedBody();

    $id = $request->getAttribute('id');

    $responsavel = '';
    if(array_key_exists('EXCLUIDOPOR', $dados_body)){
        $responsavel = $dados_body['EXCLUIDOPOR'];
    }
    $helperClientes = new HelperClientes();

    $cliente = $helperClientes->getCliente($id);
    if($cliente['status']){
        $resultado = $helperClientes->deleteById($id, $responsavel);
        if($resultado['status']){
            return $response->withJson(array('message'=>'Exclusão realizada com sucesso.'), 200);
        }else{
            return $response->withJson($resultado, 500);
        }
    }else{
        return $response->withJson($cliente,404);
    }
});

