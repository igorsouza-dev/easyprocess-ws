<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

//busca todos os usuarios
/*$app->get('/usuarios', function(Request $request, Response $response){

    $params = $request->getQueryParams();
    $helperUsuario = new HelperUsuario();
    $resultado = $helperUsuario->getUsuarios($params);
    if($resultado['status']){
        return $response->withJson($resultado['entity'], 200);
    }else{
        return $response->withJson($resultado, 404);
    }
});*/

$app->options('/usuarios', function (Request $request, Response $response) {
    return $response->withStatus(200);
});

//busca um usuario
$app->get('/usuarios/{id}', function(Request $request, Response $response){

    $id = $request->getAttribute('id');

    $helper_usuario = new HelperUsuario();
    $helper_pessoa = new HelperPessoa();
    $usuario = $helper_usuario->getUsuario($id);

    if($usuario['status']){
        $json = $usuario['entity'];

        $pessoa = $helper_pessoa->getPessoa($json[$helper_pessoa->primarykey]);
        if($pessoa['status']){
            $json['PESSOA'] = $pessoa['entity'];
        }
        return $response->withJson($json, 200);
    }else{
        return $response->withJson($usuario, 404);
    }
});

$app->options('/usuarios/{id}', function (Request $request, Response $response) {
    return $response->withStatus(200);
});

//atualiza usuario
$app->put('/usuarios/{id}', function(Request $request, Response $response){
    $id = $request->getAttribute('id');
    $dados_usuario = $request->getParsedBody();

    $helperUsuario = new HelperUsuario();

    if(isset($dados_usuario['SENHA'])){
        $dados_usuario['SENHA'] = $helperUsuario->decode64($dados_usuario['SENHA']);
    }
    $dados_usuario[$helperUsuario->primarykey] = $id;

    $resultado = $helperUsuario->update($dados_usuario);
    if($resultado['status']){
        return $response->withJson($resultado, 200);
    }else{
        return $response->withJson($resultado, 500);
    }
});

//delete usuario
$app->delete('/usuarios/{id}', function(Request $request, Response $response){
    $id = $request->getAttribute('id');

    $helperUsuario = new HelperUsuario();
    $usuario = $helperUsuario->getUsuario($id);

    if($usuario['status']){
        try{
            $result = $helperUsuario->deleteById($id, 'APP EasyProcess');
            if($result['status']){
                return $response->withJson(array('message'=>'Dados excluidos com sucesso.'), 200);
            }else{
                return $response->withJson($result, 500);
            }
        }catch (Exception $e){}

        return $response->withJson(array('error'=>'Ocorreu um erro ao tentar excluir os dados de usuário.'),500);
    }else{

        return $response->withJson(array('error'=>'Usuário informado não foi encontrado.'),404);
    }
});
