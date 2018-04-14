<?php

abstract class HelperGeral
{
    public function getUri()
    {
        if(isset($_SERVER['HTTPS'])){
            $uri = 'https';
        }else{
            $uri = 'http';
        }
        $uri .= '://'.$_SERVER['SERVER_NAME'].'/api-webservice/';
        return $uri;
    }
    public function decode64($valor){
        for ($i = 0; $i < 4; $i++) {
            $valor = base64_decode($valor);
        }
        return $valor;
    }

    public function query($sql, $tabela)
    {
        return EntidadeBanco::busca($sql, $tabela);
    }

    public function queryWithParams($params, $tabela)
    {
        $from = strtoupper($tabela);
        $orderby = '';
        if(array_key_exists('orderby', $params)){
            $orderby = $params['orderby'];
            unset($params['orderby']);
        }
        $limit = '';
        if(array_key_exists('limit', $params)){
            $limit = $params['limit'];
            unset($params['limit']);
        }
        $sql = "SELECT * FROM ".$from;

        if(is_array($params)){
            $size = count($params);
            if($size){
                $sql .= " WHERE ";
                $cont = 0;

                foreach ($params as $param=>$value) {
                    $value = trim($value);

                    $op = ' = ';
                    if($value[0] == '>') {
                        $op = ' > ';
                        $value = str_replace('>','',$value);
                    } elseif ($value[0] == '<'){
                        $op = ' < ';
                        $value = str_replace('<','',$value);
                    }
                    if(is_string($value)){
                        $value = '"'.$value.'"';
                    }

                    $sql .= $param . $op . $value;
                    if(($cont + 1) < $size){
                        $sql .= " AND ";
                    }
                    $cont++;
                }
            }
        } else{
            $sql .= " WHERE ".$params;
        }

        if($orderby != ''){
            $orderby = explode(',',$orderby);

            $sql .= " ORDER BY ";
            $sizeorder = count($orderby);
            $cont = 0;
            foreach($orderby as $order){
                $order = trim($order);
                $valorOrdem = trim($order);
                $valorOrdem = str_replace('+', '', $valorOrdem);
                $valorOrdem = str_replace('-', '', $valorOrdem);
                $sql .= $valorOrdem;
                if($order[0]=='-'){
                    $sql .= " DESC ";
                }else{
                    $sql .= " ASC ";
                }
                if(($cont + 1) < $sizeorder){
                    $sql .= ", ";
                }
                $cont++;
            }
        }
        if($limit != ''){
            $sql .= " LIMIT ".$limit;
        }

        return $this->query($sql, $tabela);
    }

    public function toArray($dados, $campos)
    {
        $array_obj = array();
        foreach($campos as $campo){
            $valor = utf8_encode($dados->pegaCampo($campo));
            $array_obj[$campo] = $valor;
        }
        return $array_obj;
    }
    public function chavesToUpperCase($dados)
    {
        $array = array();
        foreach($dados as $k=>$dado){
            $array[strtoupper($k)] = $dado;
        }
        return $array;
    }
    public function chavesToLowerCase($dados)
    {
        $array = array();
        foreach($dados as $k=>$dado){
            $array[strtolower($k)] = $dado;
        }
        return $array;
    }
    public function valoresToLowerCase($dados)
    {
        $array = array();
        foreach($dados as $k=>$dado){
            $array[$k] = strtolower($dado);
        }
        return $array;
    }
    //valida os dados em um array de acordo com os campos obrigatorios para a tabela do banco de dados
    public function verificaCamposObrigatorios($dados, $camposobrigatorios)
    {
        foreach ($camposobrigatorios as $campo){

            if(!key_exists($campo, $dados)){
                return array('status'=>false, 'error'=>'O campo '.$campo.' é obrigatório!', 'dados-informados'=>$dados);
            }else{
//                if(empty($dados[$campo])){
//                    return array('status'=>false, 'error'=>'O campo '.$campo.' não pode ser vazio!');
//                }
                if($dados[$campo] === null){
                    return array('status'=>false, 'error'=>'O campo '.$campo.' não pode ser vazio!');
                }
            }
        }
        return array('status'=>true);
    }

    public function removeCamposInvalidos($dados, $camposvalidos)
    {
        $dados = $this->chavesToUpperCase($dados);

        $dados_validos = array();
        foreach($dados as $k=>$dado){
            if(in_array($k, $camposvalidos)){
                $dados_validos[$k] = $dado;
            }
        }
        return $dados_validos;
    }
}