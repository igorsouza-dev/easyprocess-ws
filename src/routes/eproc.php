<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

/**
 Dados para teste:
 * idConsultante: advteste
 * senhaConsultante: 1545
 * numeroProcesso: [ 00372467120178272729, 00351508320178272729, 00316495820168272729 ]
*/

/**
   Params
 * idConsultante = string
 * senhaConsultante = string
 * numeroProcesso = integer
 */
$app->get('/eproc/consultarAlteracao', function(Request $request, Response $response){
    $helper = new HelperEproc();
    $params = $request->getQueryParams();
    $result = $helper->consultarAlteracao($params);
    if($result['status']) {
        return $response->withJson($result['entity'], 200);
    } else {
        return $response->withJson($result, 500);
    }
});

$app->options('/eproc/consultarAlteracao', function (Request $request, Response $response) {
    return $response->withStatus(200);
});


/**
   Params
 * idRepresentado = string
 * idConsultante = string
 * senhaConsultante = string
 * dataReferencia = datetime
 */
$app->get('/eproc/consultarAvisosPendentes', function(Request $request, Response $response){
    $helper = new HelperEproc();
    $params = $request->getQueryParams();
    $result = $helper->consultarAvisosPendentes($params);
    if($result['status']) {
        return $response->withJson($result['entity'], 200);
    } else {
        return $response->withJson($result, 500);
    }
});
$app->options('/eproc/consultarAvisosPendentes', function (Request $request, Response $response) {
    return $response->withStatus(200);
});


/**
   Params
 * idRepresentado = string
 * idConsultante = string
 * senhaConsultante = string
 * numeroProcesso = integer
 * dataReferencia = datetime
 * movimentos = boolean
 * ** incluirCabecalho = boolean
 * ** incluirDocumentos = boolean
 * ** documentos = string
 */
$app->get('/eproc/consultarProcesso', function(Request $request, Response $response){
    $helper = new HelperEproc();
    $params = $request->getQueryParams();

    $token = $request->getHeader('HTTP_AUTHORIZATION')[0];

    $token = str_replace('Bearer ', '', $token);

    $helperUsuario = new HelperUsuario();
    $coduser = HelperToken::getDataFromPayload('coduser', $token);
    $premium = false;

    $usuario = $helperUsuario->getUsuario($coduser);
    if($usuario['status']){
        $premium = $usuario['entity']['USUARIOPREMIUM'] == 'S';
        $params['CODUSUARIO'] = $coduser;
        if(!isset($params['idConsultante'])) {
            $params['idConsultante'] = $usuario['entity']['IDCONSULTANTE'];
            $params['senhaConsultante'] = $usuario['entity']['SENHACONSULTANTE'];
        }

        $helperPessoa = new HelperPessoa();
        $pessoa = $helperPessoa->getPessoa($usuario['entity']['CODPESSOA']);
        if($pessoa['status']){
            $params['CPF'] = $pessoa['entity']['CPF'];
        }
    }

    if(isset($params['documento'])) {
        $file = $helper->downloadAnexo($params);
        if($file['status']) {
            return $response->withHeader("Content-type",$file['type'])
                ->write($file['file']);
        } else {
            return $response->withJson($file, 500);
        }
    } else {

        $result = $helper->consultarProcesso($params, $premium);
        if($result['status']) {
            return $response->withJson($result, 200);
        } else {
            return $response->withJson($result, 500);
        }
    }
});
$app->options('/eproc/consultarProcesso', function (Request $request, Response $response) {
    return $response->withStatus(200);
});
//esse método é provavelmente inútil
$app->post('/eproc/consultarProcessoXmlRetorno', function(Request $request, Response $response){
    $helper = new HelperEproc();
    /* dados para teste
     * $params = array(
        'dadosBasicos' => array(
            'polo'=> array(
                "parte"=> array(
                    'pessoa' => array(
                        'documento' => array(
                            'codigoDocumento' => '00911228136',
                            'emissorDocumento' => '0',
                            'tipoDocumento' => 'CMF',
                            'nome' => ''
                        ),
                        'nome' => 'VANIA DOS SANTOS PEREIRA GOMES',
                        'sexo' => 'F',
                        'nomeGenitor' => 'EDSON JOSE PEREIRA',
                        'nomeGenitora' => 'VALCI DOS SANTOS PEREIRA',
                        'dataNascimento' => '19831010',
                        'numeroDocumentoPrincipal' => '00911228136',
                        'tipoPessoa' => 'fisica',
                        'cidadeNatural' => '',
                        'nacionalidade' => 'BR'
                    ),
                    'assistenciaJudiciaria' => false,
                    'intimacaoPendente' => 0
                ),
                'polo' => 'PA'
            ),
            "assunto" => array(
                "codigoNacional" => 7664,
                "principal"=> true
            ),
            "prioridade"=> "SEM PRIORIDADE",
            "outroParametro"=> array(),
            "valorCausa"=> 0,
            "orgaoJulgador" =>array(
                "idOrgao"=> "270000673",
                "codigoOrgao"=> "33614",
                "nomeOrgao"=> "JUIZO DO CENTRO JUDICIARIO DE SOLUCAO DE CONFLITOS E CIDADANIA (CEJUSC) - PALMAS",
                "instancia"=> "ORIG",
                "codigoMunicipioIBGE"=> 0
            ),
            "outrosnumeros" => "",
            "numero" => "00351508320178272729",
            "competencia" => 1,
            "classeProcessual" => 11875,
            "codigoLocalidade" => "2729",
            "nivelSigilo" => 0,
            "intervencaoMP" => false,
            "tamanhoProcesso" => 145075,
            "dataAjuizamento" => "20171023152555"
        )
    );*/
    $params = $request->getParsedBody();
    $result = $helper->consultarProcessoXmlRetorno($params);
    if($result['status']) {
        return $response->getBody()->write($result['entity'], 200);
    } else {
        return $response->withJson($result, 500);
    }
});


/**
   Params
 * idConsultante = string
 * senhaConsultante = string
 * ** numeroProcesso = integer
 * ** identificadorAviso = string
 */
$app->get('/eproc/consultarTeorComunicacao', function(Request $request, Response $response){
    $helper = new HelperEproc();
    $params = $request->getQueryParams();
    $result = $helper->consultarTeorComunicacao($params);
    if($result['status']) {
        return $response->withJson($result['entity'], 200);
    } else {
        return $response->withJson($result, 500);
    }
});
$app->options('/eproc/consultarTeorComunicacao', function (Request $request, Response $response) {
    return $response->withStatus(200);
});
