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
// busca planos
$app->post('/pagamentos/planos', function(Request $request, Response $response) {
    $params = $request->getParsedBody();
    if(is_null($params)){
        $params = [];
    }
    $helper = new HelperPagamentos();
    $planos = $helper->criaPlano($params);
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
    $planos = $helper->atualizaPlano($params, $id);
    if ($planos['status']) {
        return $response->withJson($planos, 200);
    } else {
        return $response->withJson($planos, 404);
    }
});

$app->post('/pagamentos/postback', function(Request $request, Response $response) {
    $params = $request->getParsedBody();
    if (isset($params['current_status'])) {
        $status = in_array($params['current_status'], ['paid', 'trialing']) ? 'S' : 'N';
        $object = $params['object'];
        $dados = $params[$object];
        $metadata = $dados['metadata'];
        if (isset($metadata['codusuario'])) {
            $codusuario = $metadata['codusuario'];
            $helperUsuario = new HelperUsuario();
            $result = $helperUsuario->update(['USUARIOPREMIUM'=>$status], 'codusuario = ' . $codusuario);
            if ($result['status']) {
                return $response->withJson(['status'=>true, 'message'=>'Situação premium do usuário alterada para '.$status]);
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

$app->post('/pagamentos/cancela-assinaturas', function(Request $request, Response $response) {
    $token = $request->getHeader('HTTP_AUTHORIZATION')[0];

    $token = str_replace('Bearer ', '', $token);

    $coduser = HelperToken::getDataFromPayload('coduser', $token);

    $helper = new HelperPagamentos();
    $assinaturas = $helper->getAssinaturas(['metadata'=>['codusuario'=>$coduser]]);

    $canceladas = 0;
    if($assinaturas['status']) {
        foreach($assinaturas['entity'] as $assinatura) {
            $cancelada = $helper->cancelaAssinatura($assinatura['id']);
            if($cancelada['status']) {
                $canceladas++;
            }
        }
        if($canceladas) {
            $helperUsuario = new HelperUsuario();
            $result = $helperUsuario->update(['USUARIOPREMIUM'=>'N'], 'codusuario = ' . $coduser);
            if ($result['status']) {
                return $response->withJson(['status'=>true, 'message'=>'Situação premium do usuário alterada para N']);
            }
        }
    }

    if ($canceladas) {
        return $response->withJson(['status'=>true, 'message'=> 'Assinatura cancelada.'], 200);
    } else {
        return $response->withJson(['status'=>false, 'message'=>'Nenhuma assinatura cancelada.'], 404);
    }
});

$app->get('/pagamentos/assinaturas', function(Request $request, Response $response) {
    $dados = $request->getParsedBody();
    $helper = new HelperPagamentos();
    $assinatura = $helper->getAssinaturas($dados);
    if ($assinatura['status']) {
        return $response->withJson($assinatura, 200);
    } else {
        return $response->withJson($assinatura, 404);
    }
});

$app->get('/pagamentos/assinaturas/{id}', function(Request $request, Response $response) {
    $id = $request->getAttribute('id');
    $helper = new HelperPagamentos();
    $assinatura = $helper->getAssinatura($id);
    if ($assinatura['status']) {
        return $response->withJson($assinatura, 200);
    } else {
        return $response->withJson($assinatura, 404);
    }
});

$app->put('/pagamentos/assinaturas/{id}', function(Request $request, Response $response) {
    $id = $request->getAttribute('id');
    $dados = $request->getParsedBody();
    $helper = new HelperPagamentos();
    $assinatura = $helper->atualizaAssinatura($id, $dados);
    if ($assinatura['status']) {
        return $response->withJson($assinatura, 200);
    } else {
        return $response->withJson($assinatura, 404);
    }
});


$app->get('/pagamentos/assinaturas-usuario', function(Request $request, Response $response) {
    $token = $request->getHeader('HTTP_AUTHORIZATION')[0];

    $token = str_replace('Bearer ', '', $token);

    $coduser = HelperToken::getDataFromPayload('coduser', $token);

    $helper = new HelperPagamentos();
    $assinaturas = $helper->getAssinaturas(['metadata'=>['codusuario'=>$coduser]]);

    if ($assinaturas['status']) {
        return $response->withJson($assinaturas, 200);
    } else {
        return $response->withJson($assinaturas, 404);
    }
});