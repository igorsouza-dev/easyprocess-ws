<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

//Busca processos
$app->get('/processos', function(Request $request, Response $response){
    $params = $request->getQueryParams();
    $helperProcesso = new HelperProcesso();
    $resultado = $helperProcesso->getProcessos($params);
    if($resultado['status']){
        return $response->withJson($resultado['entity'], 200);
    }else{
        return $response->withJson($resultado, 404);
    }
});
$app->options('/processos', function (Request $request, Response $response) {
    return $response->withStatus(200);
});
//Busca um processo
$app->get('/processos/{id}', function(Request $request, Response $response){
    $id = $request->getAttribute('id');

    $helperProcesso = new HelperProcesso();
    $processo = $helperProcesso->getProcesso($id);
    if($processo['status']){
        $processo = $processo['entity'];
        $helperParte = new HelperProcessoParte();
        $partes = $helperParte->getPartesByProcesso($id);
        if($partes['status']){
            $processo['Partes'] = $partes['entity'];
        }
        $helperProcurador = new HelperProcessoParteProcurador();
        $partesprocurador = $helperProcurador->getPartesProcuradorByProcesso($id);
        if($partesprocurador['status']){
            $processo['PartesProcurador'] = $partesprocurador['entity'];
        }
        $helperAssunto = new HelperAssuntoProcesso();
        $assuntos = $helperAssunto->getAssuntosByProcesso($id);
        if($assuntos['status']){
            $processo['Assuntos'] = $assuntos['entity'];
        }
        return $response->withJson($processo, 200);
    }else{
        return $response->withJson($processo, 404);
    }
});
$app->options('/processos/{id}', function (Request $request, Response $response) {
    return $response->withStatus(200);
});
//Inserção de processo
$app->post('/processos', function(Request $request, Response $response){
    $dados = $request->getParsedBody();
    $helper = new HelperProcesso();
    $resultado = $helper->insert($dados);
    if($resultado['status']){
        return $response->withJson($resultado, 201);
    }else{
        return $response->withJson($resultado, 500);
    }
});

//atualiza processo
$app->put('/processos/{id}', function(Request $request, Response $response){
    $id = $request->getAttribute('id');
    $dados = $request->getParsedBody();

    $helperProcesso = new HelperProcesso();
    $dados[$helperProcesso->primarykey] = $id;
    $resultado = $helperProcesso->update($dados);
    if($resultado['status']){
        return $response->withJson($resultado, 200);
    }else{
        return $response->withJson($resultado, 404);
    }
});

//Processos não são deletados
//$app->delete('/processos/{id}', function(Request $request, Response $response){
//
//    return $response->getBody()->write('Deletar processo');
//});

/** ASSUNTOS DOS PROCESSOS */
$app->get('/assuntosprocesso', function(Request $request, Response $response){
    $params = $request->getQueryParams();
    $helperAssunto = new HelperAssuntoProcesso();
    $resultado = $helperAssunto->getAssuntos($params);
    if($resultado['status']){
        return $response->withJson($resultado['entity'], 200);
    }else{
        return $response->withJson($resultado, 404);
    }
});
$app->options('/assuntosprocesso', function (Request $request, Response $response) {
    return $response->withStatus(200);
});

$app->get('/assuntosprocesso/{id}', function(Request $request, Response $response){
    $id = $request->getAttribute('id');

    $helperAssunto = new HelperAssuntoProcesso();
    $resultado = $helperAssunto->getAssunto($id);
    if($resultado['status']){
        return $response->withJson($resultado['entity'], 200);
    }else{
        return $response->withJson($resultado, 404);
    }
});
$app->options('/assuntosprocesso/{id}', function (Request $request, Response $response) {
    return $response->withStatus(200);
});
$app->post('/assuntosprocesso', function(Request $request, Response $response){
    $dados = $request->getParsedBody();
    $helper = new HelperAssuntoProcesso();
    $resultado = $helper->insert($dados);
    if($resultado['status']){
        return $response->withJson($resultado, 201);
    }else{
        return $response->withJson($resultado, 500);
    }
});

$app->put('/assuntosprocesso/{id}', function(Request $request, Response $response){
    $id = $request->getAttribute('id');
    $dados = $request->getParsedBody();

    $helperAssunto = new HelperAssuntoProcesso();
    $dados[$helperAssunto->primarykey] = $id;
    $resultado = $helperAssunto->update($dados);
    if($resultado['status']){
        return $response->withJson($resultado, 200);
    }else{
        return $response->withJson($resultado, 404);
    }
});
//
//$app->delete('/assuntosprocesso/{id}', function(Request $request, Response $response){
//    return $response->withJson('Deleta um assunto');
//});

/** PARTE PROCURADOR*/
$app->get('/parteprocurador', function(Request $request, Response $response){
    $params = $request->getQueryParams();
    $helper = new HelperProcessoParteProcurador();
    $resultado = $helper->getPartesProcurador($params);
    if($resultado['status']){
        return $response->withJson($resultado['entity'], 200);
    }else{
        return $response->withJson($resultado, 404);
    }
});

$app->options('/parteprocurador', function (Request $request, Response $response) {
    return $response->withStatus(200);
});

$app->get('/parteprocurador/{id}', function(Request $request, Response $response){
    $id = $request->getAttribute('id');

    $helper = new HelperProcessoParteProcurador();
    $resultado = $helper->getParteProcurador($id);
    if($resultado['status']){
        return $response->withJson($resultado['entity'], 200);
    }else{
        return $response->withJson($resultado, 404);
    }
});

$app->options('/parteprocurador/{id}', function (Request $request, Response $response) {
    return $response->withStatus(200);
});

$app->post('/parteprocurador', function(Request $request, Response $response){
    $dados = $request->getParsedBody();
    $helper = new HelperProcessoParteProcurador();
    $resultado = $helper->insert($dados);
    if($resultado['status']){
        return $response->withJson($resultado, 201);
    }else{
        return $response->withJson($resultado, 500);
    }
});

$app->put('/parteprocurador/{id}', function(Request $request, Response $response){
    $id = $request->getAttribute('id');
    $dados = $request->getParsedBody();

    $helper = new HelperProcessoParteProcurador();
    $dados[$helper->primarykey] = $id;
    $resultado = $helper->update($dados);
    if($resultado['status']){
        return $response->withJson($resultado, 200);
    }else{
        return $response->withJson($resultado, 404);
    }
});

/** PARTE */
$app->get('/parte', function(Request $request, Response $response){
    $params = $request->getQueryParams();
    $helper = new HelperProcessoParte();
    $resultado = $helper->getPartes($params);
    if($resultado['status']){
        return $response->withJson($resultado['entity'], 200);
    }else{
        return $response->withJson($resultado, 404);
    }
});

$app->options('/parte', function (Request $request, Response $response) {
    return $response->withStatus(200);
});

$app->get('/parte/{id}', function(Request $request, Response $response){
    $id = $request->getAttribute('id');

    $helper = new HelperProcessoParte();
    $resultado = $helper->getParte($id);
    if($resultado['status']){
        return $response->withJson($resultado['entity'], 200);
    }else{
        return $response->withJson($resultado, 404);
    }
});

$app->options('/parte/{id}', function (Request $request, Response $response) {
    return $response->withStatus(200);
});

$app->post('/parte', function(Request $request, Response $response){
    $dados = $request->getParsedBody();
    $helper = new HelperProcessoParte();
    $resultado = $helper->insert($dados);
    if($resultado['status']){
        return $response->withJson($resultado, 201);
    }else{
        return $response->withJson($resultado, 500);
    }
});

$app->put('/parte/{id}', function(Request $request, Response $response){
    $id = $request->getAttribute('id');
    $dados = $request->getParsedBody();

    $helper = new HelperProcessoParte();
    $dados[$helper->primarykey] = $id;

    $resultado = $helper->update($dados);
    if($resultado['status']){
        return $response->withJson($resultado, 200);
    }else{
        return $response->withJson($resultado, 404);
    }
});