<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Slim\Http\UploadedFile;

$container = $app->getContainer();
$container['upload_directory'] = $_SERVER["DOCUMENT_ROOT"] . '/files/img/users';

$app->get('/pessoas', function(Request $request, Response $response){
    $params = $request->getQueryParams();

    $helperPessoa = new HelperPessoa();
    $resultado = $helperPessoa->getPessoas($params);
    if($resultado['status']){
        return $response->withJson($resultado['entity'], 200);
    }else{
        return $response->withJson($resultado, 404);
    }
});
$app->options('/pessoas', function (Request $request, Response $response) {
    return $response->withStatus(200);
});

// Busca pessoa
$app->get('/pessoas/{id}', function (Request $request, Response $response) {

    $id = $request->getAttribute('id');
    $helperPessoa = new HelperPessoa();
    $resultado = $helperPessoa->getPessoa($id);

    if($resultado['status']){
        return $response->withJson($resultado['entity'], 200);
    }else{
        return $response->withJson($resultado,404);
    }
});
$app->options('/pessoas/{id}', function (Request $request, Response $response) {
    return $response->withStatus(200);
});

$app->post('/pessoas', function(Request $request, Response $response){
    $dados = $request->getParsedBody();

    $helperPessoa = new HelperPessoa();

    $restultado = $helperPessoa->insert($dados);
    if($restultado['status']){
        $json = $restultado['entity'];
        return $response->withJson($json, 201);
    }else{
        return $response->withJson($restultado, 500);
    }

});

//Atualiza pessoa
$app->put('/pessoas/{id}', function(Request $request, Response $response){
    $id = $request->getAttribute('id');
    $dados = $request->getParsedBody();

    $helperPessoa = new HelperPessoa();
    $dados[$helperPessoa->primarykey] = $id;
    $resultado = $helperPessoa->update($dados);
    if($resultado['status']){
        return $response->withJson($resultado, 200);
    }else{
        return $response->withJson($resultado, 404);
    }
});

//Deleta pessoa
$app->delete('/pessoas/{id}', function(Request $request, Response $response){
    $dados_body = $request->getParsedBody();

    $id = $request->getAttribute('id');

    $responsavel = '';
    if(array_key_exists('EXCLUIDOPOR', $dados_body)){
        $responsavel = $dados_body['EXCLUIDOPOR'];
    }
    $helperPessoa = new HelperPessoa();

    $pessoa = $helperPessoa->getPessoa($id);
    if($pessoa['status']){
        $resultado = $helperPessoa->deleteById($id, $responsavel);
        if($resultado['status']){
            return $response->withJson(array('message'=>'Exclusão realizada com sucesso.'), 200);
        }else{
            return $response->withJson($resultado, 500);
        }
    }else{
        return $response->withJson($pessoa,404);
    }
});


//UPLOAD DE FOTO
$app->post('/pessoas/{id}/foto', function (Request $request, Response $response) {
    $directory = $this->get('upload_directory');
    $id = $request->getAttribute('id');
    $uploadedFiles = $request->getUploadedFiles();
    $uploadedFile = $uploadedFiles['foto'];

    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
        $formatos = array('image/jpeg', 'image/png');
        if (in_array($uploadedFile->getClientMediaType(), $formatos)) {
            $filename = moveUploadedFile($directory, $uploadedFile, $id);
            $helperPessoa = new HelperPessoa();
            $dados[$helperPessoa->primarykey] = $id;
            $dados['FOTO'] = $filename;
            $resultado = $helperPessoa->update($dados);
            if ($resultado['status']) {
                return $response->withJson(array('message'=>"Upload realizado com sucesso.", 'status'=>true), 200);
            } else {
                return $response->withJson($resultado, 500);
            }
        }
    }

    return $response->withJson(array('error'=>'Não foi possível fazer o upload da foto.'), 500);
});

function moveUploadedFile($directory, UploadedFile $uploadedFile, $name)
{
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $filename = sprintf('%s.%0.8s', $name, $extension);
    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
    return $filename;
}
//GET FOTO
$app->get('/pessoas/{id}/foto', function($request, $response) {

    $id = $request->getAttribute('id');
    $helperPessoa = new HelperPessoa();
    $resultado = $helperPessoa->getPessoa($id);
    if ($resultado['status']) {
        $directory = $this->get('upload_directory');
        $filename = $resultado['entity']['FOTO'];
        if (empty($filename)) {
            $filename = "0.gif";
        }
        $fullname = $directory."/".$filename;

        $image = @file_get_contents($fullname);
        if($image === FALSE) {
            $handler = $this->notFoundHandler;
            return $handler($request, $response);
        }

        $response->write($image);
        return $response->withHeader('Content-Type', mime_content_type($fullname));
    } else {
        return $response->withJson($resultado,404);
    }

});