<?php 

class HelperEproc extends HelperGeral {
    protected $wsdl;
    protected $client;
    protected $location;
    protected $options;
    public function __construct() {
        $this->wsdl = 'https://eproc1treinamento.tjto.jus.br/eprocV2_homolog_1grau/ws/intercomunicacao2.2.2/wsdl/intercomunicacaoMni.php?WSDL';
        $this->location = 'https://eproc1treinamento.tjto.jus.br/eprocV2_homolog_1grau/ws/controlador_ws.php?srv=intercomunicacao2.2.2';
        $this->options = array('location' => $this->location);
//        $this->client = new SoapClient($this->wsdl, array('soap_version' => SOAP_1_2, 'trace'=>true));
        $this->client = new SoapClient($this->wsdl, array('soap_version' => SOAP_1_2));
    }
    public function soapCall($function, $arguments) {

        $retorno = array();
        try{
            $result = $this->client->__soapCall($function, $arguments, $this->options);

            if ($result->sucesso === true){
                $retorno['entity'] = $result;

                $retorno['status'] = true;
            } else {
                $retorno['error'] = $result->mensagem;
                $retorno['status'] = false;
            }
        } catch (SoapFault $e) {
           
            $retorno['error'] = $e->getMessage();
            $retorno['status'] = false;
        }
        return $retorno;
    }
    public function soapCallFile($function, $arguments) {

        $retorno = array();

        try{
            $result = $this->client->__soapCall($function, $arguments, $this->options);

            if ($result->sucesso === true){

                $documentos = $result->processo->documento;
                $documento = null;
                $type = null;
                foreach($documentos as $doc) {
                    if($doc->conteudo) {
                        $documento = $doc->conteudo;
                    }
                    $type = $this->getMymeType($doc->mimetype);
                }
                //se nenhum dos arquivos no array possui um conteudo, retorna falso
                if(empty($documento)) {
                    $retorno['error'] = "Documento informado não foi encontrado";
                    $retorno['status'] = false;
                } else {
                    $retorno['file'] = $documento;
                    $retorno['type'] = $type;
                    $retorno['status'] = true;
                }

            } else {
                // print_r($this->client->__getLastResponse());exit;
                $retorno['error'] = $result->mensagem;
                $retorno['status'] = false;
            }
        } catch (SoapFault $e) {

            $retorno['error'] = $e->getMessage();
            $retorno['status'] = false;
        }
        return $retorno;
    }
    public function getMymeType($extension) {
        $mime_array = array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet'
        );
        return $mime_array[$extension];
    }
    public function checkParams($fields, $args) {

        if(empty($args) && count($fields)) {
            return 'Não foram informados argumentos!';
        }
        foreach($fields as $field) {
            if(!array_key_exists($field, $args)) {
                return "Campo {$field} é obrigatório.";
            }
        }
        return 1;
    }
    public function request($obrigInputs, $params, $function) {
        $arguments= array($function => $params);
        $checkParams = $this->checkParams($obrigInputs, $params);
        if($checkParams === 1) {
            $result = $this->soapCall($function, $arguments);
            return $result;
        } else {
            return array('status' => false, 'error'=>$checkParams);
        }
    }
    public function requestForFile($obrigInputs, $params, $function) {
        $arguments= array($function => $params);
        $checkParams = $this->checkParams($obrigInputs, $params);
        if($checkParams === 1) {
            $result = $this->soapCallFile($function, $arguments);
            return $result;
        } else {
            return array('status' => false, 'error'=>$checkParams);
        }
    }
    public function consultarAlteracao($params) {
        $function = 'consultarAlteracao';
        $obrigInputs = array(
            'idConsultante',
            'senhaConsultante',
            'numeroProcesso'
        );
        return $this->request($obrigInputs, $params, $function);
    }
    public function consultarAvisosPendentes($params) {
        $function = 'consultarAvisosPendentes';
        //não possui campos obrigatórios
        $obrigInputs = array();
        return $this->request($obrigInputs, $params, $function);
    }
    public function consultarProcesso($params, $premiumUser=false) {

        $function = 'consultarProcesso';
        $obrigInputs = array(
            'idConsultante',
            'senhaConsultante',
            'numeroProcesso'
        );
        $dadosEproc = $this->request($obrigInputs, $params, $function);
        if($premiumUser) {
            if($this->isAdvogadoParte($params['CPF'], $dadosEproc)) {
                $helperProcesso = new HelperProcesso();
                $helperProcesso->insert();
            }
        }
        return $dadosEproc;
    }
    public function isAdvogadoParte($oab, $processo) {
        return $this->findInArray($oab, $processo);
    }
    public function downloadAnexo($params) {
        $function = 'consultarProcesso';
        $obrigInputs = array(
            'idConsultante',
            'senhaConsultante',
            'numeroProcesso'
        );
        return $this->requestForFile($obrigInputs, $params, $function);
    }
    public function consultarProcessoXmlRetorno($params) {
        $function = 'consultarProcessoXmlRetorno';
        $obrigInputs = array(
            'dadosBasicos'
        );
        return $this->request($obrigInputs, $params, $function);
    }
    public function consultarTeorComunicacao($params) {
        $function = 'consultarTeorComunicacao';
        $obrigInputs = array(
//            'idConsultante',
//            'senhaConsultante'=>array(
//                'numeroProcesso',
//                'identificadorAviso'
//            )
        );
        return $this->request($obrigInputs, $params, $function);
    }
}