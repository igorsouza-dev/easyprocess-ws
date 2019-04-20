<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/includes.php';
require_once $_SERVER["DOCUMENT_ROOT"].'/files/php/class/Funcoes.php';

$config = [
    'settings' => [
        'displayErrorDetails' => false
    ],
    'secretkey'=>'698ee85b52b7b65dde71e42705f3aa3aa276b173',
    'urls_permitidas'=>[
        '/auth',
        '/auth/new',
        '/auth/esqueci',
        '/',
//        '/eproc',
//        '/eproc/consultarAlteracao',
//        '/eproc/consultarAvisosPendentes',
//        '/eproc/consultarProcesso',
//        '/eproc/processos',
//        '/eproc/consultarProcessoXmlRetorno',
//        '/eproc/consultarTeorComunicacao'
    ]
];

$app = new \Slim\App($config);

$app->add(function(Request $request, Response $response, $next){
    $urls = $this->get('urls_permitidas');
    foreach($urls as $url){
        $target = $request->getRequestTarget();
        if(strpos($target, '?')) {
            $target = substr($target, 0,strpos($target, '?'));
        }

        if( $target == '/api-webservice'.$url || $request->isOptions()){
            return $next($request, $response);
        }
    }

    $token = $request->getHeader('HTTP_AUTHORIZATION')[0];
    $key = $this->get('secretkey');
    $token = str_replace('Bearer ', '', $token);
    if($token != ''){
        $helperToken = new HelperToken($key);
        if($helperToken->validateToken($token)){
            return $next($request, $response);
        }
    }
    return $response->withJson(array('error'=>utf8_encode('Token inválido.')), 401);
});

$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

$app->get('/', function(Request $request, Response $response){
    return $response->getBody()->write("Webservice OK");
});


require 'src/routes/usuarios.php';
require 'src/routes/pessoas.php';
require 'src/routes/processos.php';
require 'src/routes/empresas.php';
require 'src/routes/compromissos.php';
require 'src/routes/auth.php';
require 'src/routes/eproc.php';
require 'src/routes/anotacoes.php';
require 'src/routes/contas.php';
require 'src/routes/clientes.php';
require 'src/routes/fornecedores.php';

$app->run();