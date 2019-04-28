<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// cria plano premium padrão
// 'amount' => '4000', (40 reais)
// 'days' => '30', (mensal)
// 'trial_days' => '20',
// 'name' => 'Plano Premium'
$app->post('/pagamentos/plano-premium', function(Request $request, Response $response) {
    $helper = new HelperPagamentos();
    $plano = $helper->criaPlanoPremium();
    if ($plano['status']) {
        return $response->withJson($plano, 200);
    } else {
        return $response->withJson($plano, 404);
    }
});

// busca planos
$app->get('/pagamentos/planos', function(Request $request, Response $response) {
    $params = $request->getParsedBody();
    if(is_null($params)){
        $params = [];
    }
    $helper = new HelperPagamentos();
    $planos = $helper->getPlanos($params);
    if ($planos['status']) {
        return $response->withJson($planos, 200);
    } else {
        return $response->withJson($planos, 404);
    }
});

// busca um plano pelo seu id
$app->get('/pagamentos/planos/{id}', function(Request $request, Response $response){
    $id = $request->getAttribute('id');
    $helper = new HelperPagamentos();
    $plano = $helper->getPlanos(['id'=>$id]);
    if ($plano['status']) {
        return $response->withJson($plano, 200);
    } else {
        return $response->withJson($plano, 404);
    }
});

//atualiza um plano
$app->put('/pagamentos/planos/{id}', function(Request $request, Response $response) {
    $id = $request->getAttribute('id');
    $params = $request->getParsedBody();
    $helper = new HelperPagamentos();
    $params['id'] = $id;
    $planos = $helper->atualizaPlano($params);
    if ($planos['status']) {
        return $response->withJson($planos, 200);
    } else {
        return $response->withJson($planos, 404);
    }
});

$app->post('/pagamentos/assinatura', function(Request $request, Response $response) {

});

$app->post('/pagamentos/postback', function(Request $request, Response $response) {
    $params = $request->getParsedBody();
    if (isset($params['current_status'])) {
        $status = in_array($params['current_status'], ['paid', 'trialing']) ? 'S' : 'N';
        $object = $params['object'];
        $dados = $params[$object];
        $metadata = [];
        parse_str($dados['metadata'], $metadata);
        if (isset($metadata['codusuario'])) {
            $codusuario = $metadata['codusuario'];
            $helperUsuario = new HelperUsuario();
            $result = $helperUsuario->update(['USUARIOPREMIUM'=>$status], 'codusuario = ' . $codusuario);
            if ($result['status']) {
                return $response->withJson(['status'=>true, 'message'=>'Situação do usuário alterada para '.$status]);
            }
        }
    }
    return $response->withJson(['status'=>false]);

});
$app->post('/pagamentos/assinatura-credito', function(Request $request, Response $response) {
    $dados = $request->getParsedBody();
    $token = $request->getHeader('HTTP_AUTHORIZATION')[0];

    $token = str_replace('Bearer ', '', $token);

    $coduser = HelperToken::getDataFromPayload('coduser', $token);
    $helper = new HelperPagamentos();
    $assinatura = $helper->criaAssinaturaCredito($dados, $coduser);
    if ($assinatura['status']) {
        return $response->withJson($assinatura, 200);
    } else {
        return $response->withJson($assinatura, 404);
    }
});

$app->post('/pagamentos/assinatura-boleto', function(Request $request, Response $response) {
    $dados = $request->getParsedBody();
    $token = $request->getHeader('HTTP_AUTHORIZATION')[0];

    $token = str_replace('Bearer ', '', $token);

    $coduser = HelperToken::getDataFromPayload('coduser', $token);

    $helper = new HelperPagamentos();
    $assinatura = $helper->criaAssinaturaBoleto($dados, $coduser);
    if ($assinatura['status']) {
        return $response->withJson($assinatura, 200);
    } else {
        return $response->withJson($assinatura, 404);
    }
});