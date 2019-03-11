<?php

class HelperFornecedores extends HelperGeral
{
    public $primarykey = 'CODFORNECEDOR';
    public $campos = array(
        'CODFORNECEDOR', 'CODPESSOA',
        'CODEMPRESA',
        'DATAHORAINCLUSAO', 'RESPINCLUSAO',
        'DATAHORAALTERACAO', 'RESPALTERACAO',
        'DATAHORAEXCLUSAO','EXCLUIDOPOR',
        'EXCLUIDO'
    );

    public $campos_obrigatorios = array(
        'CODPESSOA','CODEMPRESA'
    );

    public function getFornecedor($id)
    {
        $params = array(
            $this->primarykey => $id,
            'EXCLUIDO'=>"N"
        );

        $result = $this->getFornecedores($params);
        if(isset($result['entity'])){
            $result['entity'] = $result['entity'][0];
        }
        return $result;
    }

    public function getFornecedores($params)
    {
        $resultado = $this->queryWithParams($params, 'Mfornecedores');

        if(count($resultado)){
            $fornecedores = array();
            foreach($resultado as $fornecedor){
                $array = $this->toArray($fornecedor, $this->campos);
                $helperPessoa = new HelperPessoa();
                if($array['CODPESSOA']) {
                    $pessoa = $helperPessoa->getPessoa($array['CODPESSOA']);
                    if($pessoa['status']){
                        $array['Pessoa'] = $pessoa['entity'];
                    }
                }
                $fornecedores[] = $array;

            }
            return array('status'=>true, 'entity'=>$fornecedores);
        }else{
            return array('status'=>false, 'error'=>'Não foram encontrados fornecedores com os dados informados.');
        }
    }

    public function insert($dados)
    {
        $fornecedor = new Mfornecedores();
        $dadosFornecedor = $this->removeCamposInvalidos($dados, $this->campos);

        $helperEmpresa = new HelperEmpresa();

        //verifica se foi informada uma empresa para o fornecedor
        if (isset($dados[$helperEmpresa->primarykey])) {
            //se foi, verifica se é valida
            $codempresa = $dados[$helperEmpresa->primarykey];

            $empresa = $helperEmpresa->getEmpresa($codempresa);
            if (!$empresa['status']) {
                return array('status'=>false, 'error'=>'Empresa informada é inválida');
            }

            $dadosFornecedor[$helperEmpresa->primarykey] = $codempresa;
            $fornecedor->setEmpresa($codempresa);

        } else {
            return array('status'=>false, 'error'=>'Não foi informada uma empresa');
        }

        $helperPessoa = new HelperPessoa();

        //verifica se foi informada uma pessoa para o fornecedor
        if (isset($dados[$helperPessoa->primarykey])) {
            //se foi, verifica se é valida
            $codpessoa = $dados[$helperPessoa->primarykey];

            $pessoa = $helperPessoa->getPessoa($codpessoa);
            if (!$pessoa['status']) {
                return array('status'=>false, 'error'=>'Pessoa informada é inválida');
            }

            $dadosFornecedor[$helperPessoa->primarykey] = $codpessoa;
        } else {
            if(isset($dados['Pessoa'])) {
                $dadosPessoa = $dados['Pessoa'];
                $pessoa = $helperPessoa->insert($dadosPessoa);
                if($pessoa['status']){
                    $pessoa = $pessoa['entity'];
                    $dadosFornecedor['CODPESSOA'] = $pessoa['CODPESSOA'];
                } else {
                    return $pessoa;
                }
            } else {
                return array('status'=>false, 'error'=>'Não foi informada uma pessoa');
            }
        }

        if (!isset($dados['RESPINCLUSAO'])) {
            $fornecedor->setResponsavel('APP EasyProcess');
        } else {
            $fornecedor->setResponsavel($dados['RESPINCLUSAO']);
        }
        $validou = $this->verificaCamposObrigatorios($dadosFornecedor, $this->campos_obrigatorios);

        if ($validou['status']) {
            try {
                $dadosFornecedor = $this->chavesToLowerCase($dadosFornecedor);
                $dadosFornecedor = $this->decodificaCaracteres($dadosFornecedor);

                $inserido = $fornecedor->insert($dadosFornecedor);
                if ($inserido) {
                    $fornecedor = $this->getFornecedor($inserido);
                    return $fornecedor;
                }
            } catch (Exception $e) {
                return array(
                    'status'=>false,
                    'error'=>'Ocorreu um erro ao inserir fornecedor no banco de dados.',
                    'error_db'=>$e->getMessage()
                );
            }
        } else {
            return $validou;
        }
        return array('status'=>false, 'error'=>'Não foi possível cadastrar fornecedor');
    }

    public function update($dados, $where = '')
    {
        $entity = new Mfornecedores();
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
        } catch (Exception $e){
            return array(
                'status'=>false,
                'error'=>'Ocorreu um erro ao '.$tipoupdate.' os dados da anotação no banco de dados.',
                'error_db'=>$e->getMessage()
            );
        }
        return array('status'=>false, 'error'=>'Não foi possível '.$tipoupdate.' os dados do fornecedor.');
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