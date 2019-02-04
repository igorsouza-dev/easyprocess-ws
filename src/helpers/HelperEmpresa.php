<?php

class HelperEmpresa extends HelperGeral
{
    public $primarykey = 'CODEMPRESA';
    public $campos = array(
        'CODEMPRESA','DATAHORAINCLUSAO',
        'RESPINCLUSAO','DATAHORAALTERACAO',
        'RESPALTERACAO','CODRAMODEATIVIDADE',
        'CODPESSOA','CODPERFIL',
        'LOGO','PORTE',
        'INSCRICAOESTADUAL','ISENTAINSCRICAOESTADUAL',
        'SIMPLES','IMPORTAPRODUTOS',
        'INSCRICAOMUNICIPAL','CERTIFICADODIGITAL',
        'SENHACERTIFICADO','NATUREZAOPERACAOMUNICIPAL',
        'SITUACAO','PERMITIRESTAGIO',
        'DATAINICIO','DATATERMINO',
        'OBSERVACOES','TERMODEESTAGIO',
        'DIAVENCIMENTO','DATAFIMCONTRATO',
        'PLANOPAGAMENTO','DESCONTOREQUERIDO',
        'CONFIRMACAOINFORMADA','VALORCONTRATACAO',
        'DESCONTO','EXCLUIDO',
        'DATAHORAEXCLUSAO','EXCLUIDOPOR'
    );

    public $campos_obrigatorios = array(
        'CODRAMODEATIVIDADE',
        'CODPESSOA','CODPERFIL',
        'ISENTAINSCRICAOESTADUAL','SIMPLES',
        'IMPORTAPRODUTOS','SITUACAO',
        'PERMITIRESTAGIO','DESCONTOREQUERIDO',
        'CONFIRMACAOINFORMADA','EXCLUIDO'
    );

    public function getEmpresa($id)
    {
        $params = array(
            $this->primarykey => $id,
            'EXCLUIDO'=>"N"
        );

        $result = $this->getEmpresas($params);
        if(isset($result['entity'])){
            $result['entity'] = $result['entity'][0];
        }
        return $result;
    }

    public function getEmpresas($params)
    {
        $resultado = $this->queryWithParams($params, 'Oempresa');

        if(count($resultado)){
            $empresas = array();
            foreach($resultado as $empresa){
                $empresas[] = $this->toArray($empresa, $this->campos);
            }
            return array('status'=>true, 'entity'=>$empresas);
        }else{
            return array('status'=>false, 'error'=>'Não foram encontradas empresas com os dados informados.');
        }
    }

    public function insert($dados)
    {
        $empresa = new Oempresa();

        $dados_empresa = $this->removeCamposInvalidos($dados, $this->campos);

        $helperPessoa = new HelperPessoa();
        $pessoa = null;

        //verifica se foi informado uma pessoa para a empresa
        if(isset($dados[$helperPessoa->primarykey])){
            //se foi informado, verifica se é valido
            $codpessoa = $dados[$helperPessoa->primarykey];

            $pessoa = $helperPessoa->getPessoa($codpessoa);
            if(!$pessoa['status']){
                return array('status'=>false, 'error'=>'Pessoa informada não é válida');
            }else{
                $pessoa = $pessoa['entity'];
            }
        } else {
            return array('status'=>false, 'error'=>'Não foi informada uma pessoa para a empresa');
        }

        $dados_empresa[$helperPessoa->primarykey] = $codpessoa;
        if (!isset($dados['RESPINCLUSAO'])) {
            $empresa->setResponsavel('APP EasyProcess');
        } else {
            $empresa->setResponsavel($dados['RESPINCLUSAO']);
        }
        $dados_empresa['CODRAMODEATIVIDADE'] = 1904;//SERVICOS ADVOCATICIOS
        $dados_empresa['CODPERFIL'] = 4;//ACESSO NIVEL 2
        $dados_empresa['ISENTAINSCRICAOESTADUAL'] = 'N';
        $dados_empresa['SIMPLES'] = 'N';
        $dados_empresa['IMPORTAPRODUTOS'] = 'N';
        $dados_empresa['SITUACAO'] = 1;
        $dados_empresa['PERMITIRESTAGIO'] = 'N';
        $dados_empresa['DESCONTOREQUERIDO'] = 'N';
        $dados_empresa['CONFIRMACAOINFORMADA'] = 'S';
        $dados_empresa['EXCLUIDO'] = 'N';

        $validou = $this->verificaCamposObrigatorios($dados_empresa, $this->campos_obrigatorios);

        if ($validou['status']) {
            try {
                $dados_empresa = $this->decodificaCaracteres($dados_empresa);

                $inserido = $empresa->insert($dados_empresa);
                if ($inserido) {
                    $empresa = $this->getEmpresa($inserido);

                    if($empresa['status'] && $pessoa){
                        $empresa['entity']['PESSOA'] = $pessoa;
                    }
                    return $empresa;
                }
            } catch (Exception $e) {
                return array(
                    'status'=>false,
                    'error'=>'Ocorreu um erro ao inserir a empresa no banco de dados.',
                    'erro_db'=>$e->getMessage()
                );
            }
        } else {
            return $validou;
        }

        return array('status'=>false, 'error'=>'Não foi possível cadastrar a empresa');
    }

    public function update($dados, $where='')
    {
        $entity = new Oempresa();

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
            $dados = $this->decodificaCaracteres($dados);
            $atualizado = $entity->update($dados, $where);
            if($atualizado){
                return array('status'=>true, 'message'=>'Dados atualizados com sucesso.');
            }
        }catch (Exception $e){
            return array('status'=>false, 'error'=>'Ocorreu um erro ao atualizar os dados da empresa no banco de dados.');
        }
        return array('status'=>false, 'error'=>'Não foi possível atualizar os dados da empresa.');
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