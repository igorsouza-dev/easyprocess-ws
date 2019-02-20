<?php

class HelperProcessoParte extends HelperGeral
{
    public $primarykey = 'CODPROCESSOPARTE';
    public $campos = array(
        'CODPROCESSOPARTE', 'CODPROCESSO',
        'CODPESSOA', 'TIPOPARTE', 'ATIVO',
        'DATAHORAEXCLUSAO', 'EXCLUIDO',
        'EXCLUIDOPOR','DATAHORAALTERACAO',
        'RESPALTERACAO', 'DATAHORAINCLUSAO'.
        'RESPINCLUSAO'
    );

    public $campos_obrigatorios = array(
        'CODPROCESSO',
        'CODPESSOA', 'ATIVO'
    );

    public function getParte($id)
    {
        $params = array(
            $this->primarykey => $id,
            'EXCLUIDO'=>"N"
        );
        $result = $this->getPartes($params);
        if(isset($result['entity'])){
            $result['entity'] = $result['entity'][0];
        }
        return $result;
    }

    public function getPartes($params)
    {
        $resultado = $this->queryWithParams($params, 'Epprocessoparte');
        if(count($resultado)){
            $partes = array();
            $helperPessoa = new HelperPessoa();
            foreach($resultado as $parte){
                $arrayParte = $this->toArray($parte, $this->campos);
                $codpessoa = $arrayParte['CODPESSOA'];
                $pessoa = $helperPessoa->getPessoa($codpessoa);
                if($pessoa['status']) {
                    $arrayParte['Pessoa'] = $pessoa['entity'];
                }
                $partes[] = $arrayParte;
            }
            return array('status'=>true, 'entity'=>$partes);
        }else{
            return array('status'=>false, 'error'=>'Não foram encontradas as partes no processo com os dados informados.');
        }
    }

    public function getPartesByProcesso($codprocesso)
    {
        $params = array(
            'CODPROCESSO'=>$codprocesso,
            'EXCLUIDO'=>"N"
        );
        return $this->getPartes($params);
    }

    public function insert($dados)
    {
        $dados = $this->chavesToUpperCase($dados);
        $parte = new Epprocessoparte();
        $dados_parte = $this->removeCamposInvalidos($dados, $this->campos);
        $dados_parte['DATAHORAINCLUSAO'] = date( 'Y-m-d H:i:s');
        if(!isset($dados['RESPINCLUSAO'])){
            $dados_parte['RESPINCLUSAO'] = 'APP EasyProcess';
        }
        $validou = $this->verificaCamposObrigatorios($dados_parte, $this->campos_obrigatorios);
        if($validou['status']){
            try{
                $dados_parte = $this->decodificaCaracteres($dados_parte);
                $inserido = $parte->insert($dados_parte);
                $parte = $this->getParte($inserido);
                return $parte;
            }catch (Exception $e){
                return array('status'=>false, 'error'=>'Ocorreu um erro durante a inserção da parte no processo no banco de dados.');
            }
        }else{
            return $validou;
        }
    }

    public function update($dados, $where='')
    {
        $entity = new Epprocessoparte();

        if($where == ''){
            if(isset($dados[$this->primarykey])){
                $where = $this->primarykey.' = '.$dados[$this->primarykey];
            }
        }
        $isExclusao = false;
        if(isset($dados['EXCLUIDO'])){
            $isExclusao = $dados['EXCLUIDO'] == 'S';

            if($isExclusao){
                if(!isset($dados['EXCLUIDOPOR'])){
                    $dados['EXCLUIDOPOR'] = 'APP EasyProcess';
                }
            }
        }

        if(!$isExclusao){
            $dados['DATAHORAALTERACAO'] = date('Y-m-d H:i:s');

            if(!array_key_exists('RESPALTERACAO', $dados)){
                $dados['RESPALTERACAO'] = 'APP EasyProcess';
            }
        }
        $dados = $this->removeCamposInvalidos($dados, $this->campos);
        try{
            $dados = $this->decodificaCaracteres($dados);
            $atualizado = $entity->update($dados, $where);
            if($atualizado){
                return array('status'=>true, 'message'=>'Dados atualizados com sucesso.');
            }
        }catch (Exception $e){
            return array('status'=>false, 'error'=>'Ocorreu um erro ao atualizar os dados da parte no processo no banco de dados.');
        }
        return array('status'=>false, 'error'=>'Não foi possível atualizar os dados da parte no processo.');
    }

    public function delete($where = '', $responsavel = '')
    {
        $dados['EXCLUIDO'] = 'S';
        if($responsavel == ''){
            $responsavel = 'APP EasyProcess';
        }
        $dados['EXCLUIDOPOR'] = $responsavel;
        $dados['DATAHORAEXCLUSAO'] = date('Y-m-d H:i:s');

        return $this->update($dados, $where);
    }
    public function deleteById($id, $responsavel = '')
    {
        if($responsavel == ''){
            $responsavel = 'APP EasyProcess';
        }

        return $this->delete($this->primarykey.' = '.$id, $responsavel);
    }
}