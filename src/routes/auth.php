<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->post('/auth', function(Request $request, Response $response){

    $body = $request->getParsedBody();
    $login = trim($body['LOGIN']);
    $senha = trim($body['SENHA']);
    if($login == '' || $senha == ''){
        return $response->withJson(array('message'=>utf8_encode('Necessário informar o login e a senha')));
    }

    $helper = new HelperUsuario();
    $senha = $helper->decode64($senha);

    $sql = "SELECT 
                * 
            FROM 
                OUSUARIOS AS U
                  INNER JOIN
                OPESSOAS AS P ON (P.CODPESSOA = U.CODPESSOA AND P.EXCLUIDO = 'N' AND U.EXCLUIDO = 'N') 
            WHERE 
              (U.LOGIN = '$login' AND U.SENHA = PASSWORD('$senha')) OR (P.EMAIL = '$login' AND U.SENHA = PASSWORD('$senha'))
            ORDER BY CODUSUARIO DESC
            LIMIT 1";

    $result = $helper->query($sql, "Executa_select");

    if(count($result)){
        $result = $helper->toArray($result[0], $helper->campos);
        $login = $result['LOGIN'];
        $codusuario = $result['CODUSUARIO'];
        $payload = array(
            'user'=>$login,
            'coduser'=>$codusuario
        );
        $key = $this->get('secretkey');
        $helperToken = new HelperToken($key);
        $token = (string) $helperToken->generate($payload);

        return $response->withJson(array('TOKEN'=>$token));

    }else{
        return $response->withJson(array('error'=>utf8_encode('Não foi possível validar o usuário')),401);
    }
});
$app->options('/auth', function (Request $request, Response $response) {
    return $response->withStatus(200);
});

$app->post('/auth/new', function(Request $request, Response $response){
    $dados = $request->getParsedBody();
    $helperUsuario = new HelperUsuario();
    $result = $helperUsuario->insert($dados);
    if($result['status']){
        $key = $this->get('secretkey');
        $helperToken = new HelperToken($key);
        $usuario = $result['entity'];
        $usuario['TOKEN'] = (string) $helperToken->generate($usuario['LOGIN']);
        return $response->withJson($usuario, 201);
    }else{
        return $response->withJson($result, 500);
    }
});
$app->options('/auth/new', function (Request $request, Response $response) {
    return $response->withStatus(200);
});

$app->post('/auth/esqueci', function(Request $request, Response $response){
    $dados = $request->getParsedBody();
    if(isset($dados['LOGIN'])){
        $helperUsuario = new HelperUsuario();
        $helperPessoa = new HelperPessoa();

        $usuarios = $helperUsuario->getUsuarios(array('LOGIN'=>$dados['LOGIN']));

        if($usuarios['status']) {
            $usuario = $usuarios['entity'][0];
            $pessoas = $helperPessoa->getPessoa($usuario[$helperPessoa->primarykey]);

            if($pessoas['status']){
                $pessoa = $pessoas['entity'];

                $helperEmail = new HelperEmail();
                $dadosEmail = array(
                    'NOME'=>$pessoa['NOME'],
                    'EMAIL'=>$pessoa['EMAIL'],
                    'LOGIN'=>$usuario['LOGIN'],
                    'CODUSUARIO'=>$usuario['CODUSUARIO']
                );

                $result = $helperEmail->envia($dadosEmail);

                return $response->withJson($result);
            } else {
                return $response->withJson(array('error'=>'Dados pessoais não foram encontrados.'), 404);
            }

        } else {
            return $response->withJson(array('error'=>'Usuário informado não foi encontrado.'), 404);
        }

    }else{
        return $response->withJson(array('error'=>'Necessário informar o login do usuário.'), 404);
    }
});
$app->options('/auth/esqueci', function (Request $request, Response $response) {
    return $response->withStatus(200);
});
