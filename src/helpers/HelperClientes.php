<?php

class HelperClientes extends HelperGeral
{
    public $primarykey = 'CODCLIENTE';
    public $campos = array(
        'CODCLIENTE', 'CODPESSOA',
        'CODEMPRESA', 'ATIVO',
        'DATAHORAINCLUSAO', 'RESPINCLUSAO',
        'DATAHORAALTERACAO', 'RESPALTERACAO',
        'DATAHORAEXCLUSAO','EXCLUIDOPOR',
        'EXCLUIDO'
    );

    public $campos_obrigatorios = array(
        'CODPESSOA','CODEMPRESA'
    );

    public function getCliente($id)
    {
        $params = array(
            $this->primarykey => $id,
            'EXCLUIDO'=>"N"
        );

        $result = $this->getClientes($params);
        if(isset($result['entity'])){
            $result['entity'] = $result['entity'][0];
        }
        return $result;
    }

    public function getClientes($params)
    {
        $resultado = $this->queryWithParams($params, 'Mclientes');

        if(count($resultado)){
            $clientes = array();
            foreach($resultado as $cliente){
                $array = $this->toArray($cliente, $this->campos);
                $helperPessoa = new HelperPessoa();
                if($array['CODPESSOA']) {
                    $pessoa = $helperPessoa->getPessoa($array['CODPESSOA']);
                    if($pessoa['status']){
                        $array['Pessoa'] = $pessoa['entity'];
                    }
                }
                $clientes[] = $array;

            }
            return array('status'=>true, 'entity'=>$clientes);
        }else{
            return array('status'=>false, 'error'=>'Não foram encontrados clientes com os dados informados.');
        }
    }

    public function insert($dados)
    {
        $cliente = new Mclientes();
        $dadosCliente = $this->removeCamposInvalidos($dados, $this->campos);

        $helperEmpresa = new HelperEmpresa();

        //verifica se foi informada uma empresa para o cliente
        if (isset($dados[$helperEmpresa->primarykey])) {
            //se foi, verifica se é valida
            $codempresa = $dados[$helperEmpresa->primarykey];

            $empresa = $helperEmpresa->getEmpresa($codempresa);
            if (!$empresa['status']) {
                return array('status'=>false, 'error'=>'Empresa informada é inválida');
            }

            $dadosCliente[$helperEmpresa->primarykey] = $codempresa;
            $cliente->setEmpresa($codempresa);

        } else {
            return array('status'=>false, 'error'=>'Não foi informada uma empresa');
        }

        $helperPessoa = new HelperPessoa();

        //verifica se foi informada uma pessoa para o cliente
        if (isset($dados[$helperPessoa->primarykey])) {
            //se foi, verifica se é valida
            $codpessoa = $dados[$helperPessoa->primarykey];

            $pessoa = $helperPessoa->getPessoa($codpessoa);
            if (!$pessoa['status']) {
                return array('status'=>false, 'error'=>'Pessoa informada é inválida');
            }

            $dadosCliente[$helperPessoa->primarykey] = $codpessoa;
        } else {
            if(isset($dados['Pessoa'])) {
                $dadosPessoa = $dados['Pessoa'];
                $pessoa = $helperPessoa->insert($dadosPessoa);
                if($pessoa['status']){
                    $pessoa = $pessoa['entity'];
                    $dadosCliente['CODPESSOA'] = $pessoa['CODPESSOA'];
                } else {
                    return $pessoa;
                }
            } else {
                return array('status'=>false, 'error'=>'Não foi informada uma pessoa');
            }
        }

        if (!isset($dados['RESPINCLUSAO'])) {
            $cliente->setResponsavel('APP EasyProcess');
        } else {
            $cliente->setResponsavel($dados['RESPINCLUSAO']);
        }
        $validou = $this->verificaCamposObrigatorios($dadosCliente, $this->campos_obrigatorios);

        if ($validou['status']) {
            try {
                $dadosCliente = $this->chavesToLowerCase($dadosCliente);
                $dadosCliente = $this->decodificaCaracteres($dadosCliente);

                $inserido = $cliente->insert($dadosCliente);
                if ($inserido) {
                    $cliente = $this->getCliente($inserido);
                    return $cliente;
                }
            } catch (Exception $e) {
                return array(
                    'status'=>false,
                    'error'=>'Ocorreu um erro ao inserir cliente no banco de dados.',
                    'error_db'=>$e->getMessage()
                );
            }
        }
        else {
            return $validou;
        }
        return array('status'=>false, 'error'=>'Não foi possível cadastrar cliente');
    }

    public function update($dados, $where = '')
    {
        $entity = new Mclientes();
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