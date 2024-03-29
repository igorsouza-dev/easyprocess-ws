<?php

class HelperCompromisso extends HelperGeral
{
    public $primarykey = 'CODORDENDESERVICO';
    public $campos = array(
        'CODORDENDESERVICO','DATAHORAINCLUSAO',
        'RESPINCLUSAO','DATAHORAALTERACAO',
        'RESPALTERACAO','CODEMPRESA',
        'CODUSUARIO','CODPROCESSO', 'ALERTA',
        'CODFORNECEDOR','CODCLIENTE', 'CODCONTA',
        'TITULO','SOLICITACAO',
        'DATASOLICITACAO','HORASOLICITACAO',
        'HORAFIMSOLICITACAO',
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
                $array = $this->toArray($compromisso, $this->campos);
                if($array['CODCLIENTE']>0) {
                    $helperCliente = new HelperClientes();
                    $cliente = $helperCliente->getCliente($array['CODCLIENTE']);
                    if($cliente['status']) {
                        $array['Cliente'] = $cliente['entity'];
                    }
                }

                if($array['CODFORNECEDOR']>0) {
                    $helperFornecedor = new HelperFornecedores();
                    $fornecedor = $helperFornecedor->getFornecedor($array['CODFORNECEDOR']);
                    if($fornecedor['status']) {
                        $array['Fornecedor'] = $fornecedor['entity'];
                    }
                }

                if($array['CODPROCESSO'] > 0) {
                    $helperProcesso = new HelperProcesso();
                    $processo = $helperProcesso->getProcesso($array['CODPROCESSO']);
                    if($processo['status']) {
                        $array['Processo'] = $processo['entity'];
                    }
                }

                if($array['CODCONTA'] > 0) {
                    $helperContas = new HelperContas();
                    $conta = $helperContas->getConta($array['CODCONTA']);
                    if($conta['status']) {
                        $array['Conta'] = $conta['entity'];
                    }
                }
                $compromissos[] = $array;
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
                $dados_compromisso = $this->decodificaCaracteres($dados_compromisso);

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


    public function update($dados, $where='')
    {
        $entity = new Mordendeservico();

        if($where == ''){
            if(isset($dados[$this->primarykey])){
                $where = $this->primarykey.' = '.$dados[$this->primarykey];
            }
        }
        $tipoupdate = "atualizar";

        $isExclusao = false;
        if(isset($dados['EXCLUIDO'])){
            $isExclusao = $dados['EXCLUIDO']=='S';

            if($isExclusao){
                $tipoupdate = "excluir";
                $entity->setResponsavel('APP EasyProcess');
                if(isset($dados['EXCLUIDOPOR'])){
                    $entity->setResponsavel($dados['EXCLUIDOPOR']);
                }
            }
        }

        if(!$isExclusao){
            $entity->setResponsavel('APP EasyProcess');
            if(array_key_exists('RESPALTERACAO', $dados)){
                $entity->setResponsavel($dados['RESPALTERACAO']);
            }
        }
        $dados = $this->removeCamposInvalidos($dados, $this->campos);
        try{
            $dados = $this->chavesToLowerCase($dados);
            $dados = $this->decodificaCaracteres($dados);
            if ($isExclusao) {
                $excluido = $entity->delete($where);
                if($excluido){
                    return array('status'=>true, 'message'=>'Dados excluidos com sucesso.');
                }
            } else {
                $atualizado = $entity->update($dados, $where);
                if($atualizado){
                    return array('status'=>true, 'message'=>'Dados atualizados com sucesso.');
                }
            }

        }catch (Exception $e){
            return array(
                'status'=>false,
                'error'=>'Ocorreu um erro ao '.$tipoupdate.' os dados do compromisso no banco de dados.',
                'error_db'=>$e->getMessage()
            );
        }
        return array('status'=>false, 'error'=>'Não foi possível '.$tipoupdate.' os dados do compromisso.');
    }

    public function delete($where = '', $responsavel = '')
    {
        $dados['EXCLUIDO'] = 'S';
        if($responsavel == ''){
            $responsavel = 'APP EasyProcess';
        }
        $dados['EXCLUIDOPOR'] = $responsavel;

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