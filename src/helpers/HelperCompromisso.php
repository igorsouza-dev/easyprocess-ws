<?php

class HelperCompromisso extends HelperGeral
{
    public $primarykey = 'CODORDENDESERVICO';
    public $campos = array(
        'CODORDENDESERVICO','DATAHORAINCLUSAO',
        'RESPINCLUSAO','DATAHORAALTERACAO',
        'RESPALTERACAO','CODEMPRESA',
        'CODFORNECEDOR','CODCLIENTE',
        'TITULO','SOLICITACAO',
        'DATASOLICITACAO','HORASOLICITACAO',
        'EXECUTADA','OBS',
        'EXCLUIDO','DATAHORAEXCLUSAO',
        'EXCLUIDOPOR'
    );

    public $campos_obrigatorios = array(
        'CODEMPRESA','TITULO',
        'SOLICITACAO','DATASOLICITACAO',
        'EXECUTADA'
    );

    public function getCompromisso($id)
    {
        $params = array(
            $this->primarykey => $id,
            'EXCLUIDO'=>"N"
        );

        $result = $this->getCompromissos($params);
        if(isset($result['entity'])){
            $result['entity'] = $result['entity'][0];
        }
        return $result;
    }

    public function getCompromissos($params)
    {
        $resultado = $this->queryWithParams($params, 'Mordendeservico');

        if(count($resultado)){
            $compromissos = array();
            foreach($resultado as $compromisso){
                $compromissos[] = $this->toArray($compromisso, $this->campos);
            }
            return array('status'=>true, 'entity'=>$compromissos);
        }else{
            return array('status'=>false, 'error'=>'Não foram encontrados compromissos com os dados informados.');
        }
    }
    public function insert($dados)
    {
        $dados = $this->chavesToUpperCase($dados);

        $compromisso = new Mordendeservico();
        $dados_compromisso = $this->removeCamposInvalidos($dados, $this->campos);

        if (!isset($dados['RESPINCLUSAO'])) {
            $compromisso->setResponsavel('APP EasyProcess');
        } else {
            $compromisso->setResponsavel($dados['RESPINCLUSAO']);
        }

        $validou = $this->verificaCamposObrigatorios($dados_compromisso, $this->campos_obrigatorios);
        if ($validou['status']) {
            try{
                $compromisso->setCodempresa($dados_compromisso['CODEMPRESA']);
                $dados_compromisso = $this->chavesToLowerCase($dados_compromisso);
                $inserido = $compromisso->insert($dados_compromisso);
                $compromisso = $this->getCompromisso($inserido);
                return $compromisso;
            }catch (Exception $e){
                return array(
                    'status'=>false,
                    'error'=>'Ocorreu um erro durante a inserção do compromisso no banco de dados.',
                    'error_db'=>$e->getMessage()
                );
            }
        } else {
            return $validou;
        }
    }


    public function update($dados, $where=''){
        $entity = new Mordendeservico();

        if($where == ''){
            if(isset($dados[$this->primarykey])){
                $where = $this->primarykey.' = '.$dados[$this->primarykey];
            }
        }

        $isExclusao = false;
        if(isset($dados['EXCLUIDO'])){
            $isExclusao = $dados['EXCLUIDO']=='S';

            if($dados['EXCLUIDO']=='S'){
                $isExclusao = true;
                $entity->setResponsavel('APP EasyProcess');
                if(isset($dados['EXCLUIDOPOR'])){
                    $entity->setResponsavel($dados['EXCLUIDOPOR']);
                }
            }
        }

        if(!$isExclusao){
            $dados['DATAHORAALTERACAO'] = date('Y-m-d H:i:s');
            $entity->setResponsavel('APP EasyProcess');
            if(array_key_exists('RESPALTERACAO', $dados)){
                $entity->setResponsavel($dados['RESPALTERACAO']);
            }
        }
        $dados = $this->removeCamposInvalidos($dados, $this->campos);
        try{

            $atualizado = $entity->update($dados, $where);
            if($atualizado){
                return array('status'=>true, 'message'=>'Dados atualizados com sucesso.');
            }
        }catch (Exception $e){
            return array(
                'status'=>false,
                'error'=>'Ocorreu um erro ao atualizar os dados do compromisso no banco de dados.',
                'error_db'=>$e->getMessage()
            );
        }
        return array('status'=>false, 'error'=>'Não foi possível atualizar os dados do compromisso.');
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