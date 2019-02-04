<?php

class HelperAnotacoes extends HelperGeral
{
    //ANOTACAO = OCORRENCIA
    public $primarykey = 'CODOCORRENCIA';
    public $campos = array(
        'CODOCORRENCIA','CODEMPRESA',
        'CODUSUARIO', 'CODPROCESSO',
        'CODTIPOOCORRENCIA', 'CODFORNECEDOR',
        'CODCLIENTE','DATAOCORRENCIA',
        'HORAOCORRENCIA', 'TITULO', 'DESCRICAO',
        'DATAHORAINCLUSAO', 'RESPINCLUSAO',
        'DATAHORAALTERACAO', 'RESPALTERACAO',
        'DATAHORAEXCLUSAO','EXCLUIDOPOR',
        'EXCLUIDO'
    );
    public $campos_obrigatorios = array(
        'CODEMPRESA', 'DATAOCORRENCIA'
    );

    public function getAnotacao($id)
    {
        $params = array(
            $this->primarykey => $id,
            'EXCLUIDO'=>"N"
        );

        $result = $this->getAnotacoes($params);
        if(isset($result['entity'])){
            $result['entity'] = $result['entity'][0];
        }
        return $result;
    }

    public function getAnotacoes($params)
    {
        $resultado = $this->queryWithParams($params, 'Mocorrencias');

        if(count($resultado)){
            $ocorrencias = array();
            foreach($resultado as $ocorrencia){
                $ocorrencias[] = $this->toArray($ocorrencia, $this->campos);
            }
            return array('status'=>true, 'entity'=>$ocorrencias);
        }else{
            return array('status'=>false, 'error'=>'Não foram encontradas ocorrências com os dados informados.');
        }
    }

    public function insert($dados)
    {
        $ocorrencia = new Mocorrencias();
        $dadosOcorrencia = $this->removeCamposInvalidos($dados, $this->campos);

        $helperEmpresa = new HelperEmpresa();
        $empresa = null;

        //verifica se foi informada uma empresa para a anotacao
        if (isset($dados[$helperEmpresa->primarykey])) {
            //se foi, verifica se é valida
            $codempresa = $dados[$helperEmpresa->primarykey];

            $empresa = $helperEmpresa->getEmpresa($codempresa);
            if (!$empresa['status']) {
               return array('status'=>false, 'error'=>'Empresa informada é inválida');
            }

            $dadosOcorrencia[$helperEmpresa->primarykey] = $codempresa;
            $ocorrencia->setEmpresa($codempresa);

        } else {
            return array('status'=>false, 'error'=>'Não foi informada uma empresa');
        }

        $helperUsuario = new HelperUsuario();
        $usuario = null;

        //verifica se foi informado um usuario para a anotacao
        if (isset($dados[$helperUsuario->primarykey])) {
            //se foi, verifica se existe
            $codusuario = $dados[$helperUsuario->primarykey];

            $usuario = $helperUsuario->getUsuario($codusuario);
            if (!$usuario['status']) {
                return array('status'=>false, 'error'=>'Usuário informado é inválido');
            }
            $dadosOcorrencia[$helperUsuario->primarykey] = $codusuario;
        }

        $helperProcesso = new HelperProcesso();
        $processo = null;

        //verifica se foi informado um processo para a ocorrencia
        if (isset($dados[$helperProcesso->primarykey])) {
            //se foi, verifica se existe
            $codprocesso = $dados[$helperProcesso->primarykey];

            $processo = $helperProcesso->getProcesso($codprocesso);
            if (!$processo['status']) {
                return array('status'=>false, 'error'=>'Processo informado é inválido');
            }
            $dadosOcorrencia[$helperProcesso->primarykey] = $codprocesso;
        }


        if (!isset($dados['RESPINCLUSAO'])) {
            $ocorrencia->setResponsavel('APP EasyProcess');
        } else {
            $ocorrencia->setResponsavel($dados['RESPINCLUSAO']);
        }

        $validou = $this->verificaCamposObrigatorios($dadosOcorrencia, $this->campos_obrigatorios);

        if ($validou['status']) {
            try {
                $dadosOcorrencia = $this->chavesToLowerCase($dadosOcorrencia);
                $dadosOcorrencia = $this->decodificaCaracteres($dadosOcorrencia);

                $inserido = $ocorrencia->insert($dadosOcorrencia);
                if ($inserido) {
                    $ocorrencia = $this->getAnotacao($inserido);
                    return $ocorrencia;
                }
            } catch (Exception $e) {
                return array(
                    'status'=>false,
                    'error'=>'Ocorreu um erro ao inserir a anotação no banco de dados.',
                    'error_db'=>$e->getMessage()
                );
            }
        } else {
            return $validou;
        }
        return array('status'=>false, 'error'=>'Não foi possível cadastrar anotação');
    }

    public function update($dados, $where = '')
    {
        $entity = new Mocorrencias();
        if ($where == '') {
           if (isset($dados[$this->primarykey])) {
               $where = $this->primarykey.' = '.$dados[$this->primarykey];
           }
        }
        $tipoupdate = "atualizar";
        $isExclusao = false;
        if (isset($dados['EXCLUIDO'])) {
            $isExclusao = $dados['EXCLUIDO']=='S';

            if ($isExclusao) {
                $tipoupdate = "excluir";
                $entity->setResponsavel('APP EasyProcess');
                if(isset($dados['EXCLUIDOPOR'])){
                    $entity->setResponsavel($dados['EXCLUIDOPOR']);
                }
            }
        }
        if (!$isExclusao) {
            $entity->setResponsavel('APP EasyProcess');
            if(array_key_exists('RESPALTERACAO', $dados)){
                $entity->setResponsavel($dados['RESPALTERACAO']);
            }
        }
        $dados = $this->removeCamposInvalidos($dados, $this->campos);

        try{
            $dados = $this->chavesToLowerCase($dados);
            if ($isExclusao) {
                $excluido = $entity->delete($where);
                if($excluido){
                    return array('status'=>true, 'message'=>'Dados excluídos com sucesso.');
                }
            } else {
                $dados = $this->decodificaCaracteres($dados);
                $atualizado = $entity->update($dados, $where);
                if($atualizado){
                    return array('status'=>true, 'message'=>'Dados atualizados com sucesso.');
                }
            }
        }catch (Exception $e){
            return array(
                'status'=>false,
                'error'=>'Ocorreu um erro ao '.$tipoupdate.' os dados da anotação no banco de dados.',
                'error_db'=>$e->getMessage()
            );
        }
        return array('status'=>false, 'error'=>'Não foi possível '.$tipoupdate.' os dados da anotação.');
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