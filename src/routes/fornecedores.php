<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

//Busca fornecedores
$app->get('/fornecedores', function(Request $request, Response $response) {
    $params = $request->getQueryParams();
    $helperFornecedores = new HelperFornecedores();
    $resultado = $helperFornecedores->getFornecedores($params);
    if ($resultado['status']) {
        return $response->withJson($resultado['entity'], 200);
    } else {
        return $response->withJson($resultado, 404);
    }
});

//Busca fornecedor
$app->get('/fornecedores/{id}', function(Request $request, Response $response) {
    $id = $request->getAttribute('id');

    $helperFornecedores = new HelperFornecedores();
    $fornecedor = $helperFornecedores->getFornecedor($id);
    if ($fornecedor['status']) {
        $fornecedor = $fornecedor['entity'];
        return $response->withJson($fornecedor, 200);
    } else {

        return $response->withJson($fornecedor, 404);
    }
});


//Cria novo fornecedor
$app->post('/fornecedores', function(Request $request, Response $response) {
    $dados = $request->getParsedBody();

    $helperFornecedores = new HelperFornecedores();

    $resultado = $helperFornecedores->insert($dados);
    if ($resultado['status']) {
        $json = $resultado['entity'];
        return $response->withJson($json, 201);
    } else {
        return $response->withJson($resultado, 500);
    }
});

//Atualiza fornecedor
$app->put('/fornecedores/{id}', function(Request $request, Response $response) {
    $id = $request->getAttribute('id');
    $dados = $request->getParsedBody();

    $helperFornecedores = new HelperFornecedores();
    $dados[$helperFornecedores->primarykey] = $id;
    $resultado = $helperFornecedores->update($dados);
    if ($resultado['status']) {
        return $response->withJson($resultado, 200);
    } else {
        return $response->withJson($resultado, 404);
    }
});

//Deleta fornecedor
$app->delete('/fornecedores/{id}', function(Request $request, Response $response) {
    $dados_body = $request->getParsedBody();

    $id = $request->getAttribute('id');

    $responsavel = '';
    if(array_key_exists('EXCLUIDOPOR', $dados_body)){
        $responsavel = $dados_body['EXCLUIDOPOR'];
    }
    $helperFornecedores = new HelperFornecedores();

    $fornecedor = $helperFornecedores->getFornecedor($id);
    if($fornecedor['status']){
        $resultado = $helperFornecedores->deleteById($id, $responsavel);
        if($resultado['status']){
            return $response->withJson(array('message'=>'Exclusão realizada com sucesso.'), 200);
        }else{
            return $response->withJson($resultado, 500);
        }
    }else{
        return $response->withJson($fornecedor,404);
    }
});

