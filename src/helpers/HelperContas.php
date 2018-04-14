<?php

class HelperContas extends HelperGeral
{
    public $primarykey = 'CODCONTA';
    public $campos = array(
        'CODCONTA', 'CODEMPRESA',
        'CODUSUARIO', 'CODPROCESSO',
        'CODCLIENTE', 'CODFORNECEDOR',
        'CONTRATO', 'NUMERONOTA',
        'DESCRICAO', 'DATANOTA',
        'DATAVENCIMENTO', 'DATABAIXA',
        'VALORTOTAL',
//        'FIXA', 'PERCENTAGEMJUROS',
        'GRUPO',
        'VALOR', 'SALDO',
        'PARCELA', 'TOTALPARCELAS',
        'SITUACAO', 'TIPOCONTA',
        'JUROS', 'MULTA',
        'DESCONTO', 'OBS',
        'DATAHORAINCLUSAO', 'RESPINCLUSAO',
        'DATAHORAALTERACAO', 'RESPALTERACAO',
        'DATAHORAEXCLUSAO','EXCLUIDOPOR',
        'EXCLUIDO'
    );

    public $campos_obrigatorios = array(
        'DESCRICAO', 'CODEMPRESA',
        'DATAVENCIMENTO', 'VALORTOTAL',
//        'FIXA', 'PERCENTAGEMJUROS', 'GRUPO',
        'PARCELA',
        'TOTALPARCELAS', 'SITUACAO',
        'TIPOCONTA'
    );

    public function getConta($id) {
        $params = array(
            $this->primarykey => $id,
            'EXCLUIDO'=>"N"
        );

        $result = $this->getContas($params);
        if(isset($result['entity'])){
            $result['entity'] = $result['entity'][0];
        }
        return $result;
    }

    public function getContas($params)
    {
        $resultado = $this->queryWithParams($params, 'Mcontas');

        if(count($resultado)){
            $contas = array();
            foreach($resultado as $conta){
                $contas[] = $this->toArray($conta, $this->campos);
            }
            return array('status'=>true, 'entity'=>$contas);
        }else{
            return array('status'=>false, 'error'=>'Não foram encontradas contas com os dados informados.');
        }
    }

    public function insert($dados) {
        $conta = new Mcontas();
        $dadosConta = $this->removeCamposInvalidos($dados, $this->campos);

        $helperEmpresa = new HelperEmpresa();

        //verifica se foi informada uma empresa para a conta
        if (isset($dados[$helperEmpresa->primarykey])) {
            //se foi, verifica se é válida
            $codempresa = $dados[$helperEmpresa->primarykey];

            $empresa = $helperEmpresa->getEmpresa($codempresa);
            if (!$empresa['status']) {
                return array('status'=>false, 'error'=>'Empresa informada é inválida');
            }

            $dadosConta[$helperEmpresa->primarykey] = $codempresa;
            $conta->setEmpresa($codempresa);
        } else {
            return array('status'=>false, 'error'=>'Não foi informada uma empresa');
        }

        $helperUsuario = new HelperUsuario();
        $usuario = null;
        //verifica se foi informado um usuario para a conta
        if (isset($dados[$helperUsuario->primarykey])) {
            //se foi, verifica se existe
            $codusuario = $dados[$helperUsuario->primarykey];

            $usuario = $helperUsuario->getUsuario($codusuario);
            if (!$usuario['status']) {
                return array('status'=>false, 'error'=>'Usuário informado é inválido');
            }
            $dadosConta[$helperUsuario->primarykey] = $codusuario;
        }

        $helperProcesso = new HelperProcesso();
        $processo = null;

        //verifica se foi informado um processo para a conta
        if (isset($dados[$helperProcesso->primarykey])) {
            //se foi, verifica se existe
            $codprocesso = $dados[$helperProcesso->primarykey];

            $processo = $helperProcesso->getProcesso($codprocesso);
            if (!$processo['status']) {
                return array('status'=>false, 'error'=>'Processo informado é inválido');
            }
            $dadosConta[$helperProcesso->primarykey] = $codprocesso;
        }

        if (!isset($dados['RESPINCLUSAO'])) {
            $conta->setResponsavel('APP EasyProcess');
        } else {
            $conta->setResponsavel($dados['RESPINCLUSAO']);
        }

        //esses campos atualmente não estão sendo usados
        $dadosConta['GRUPO'] = sha1(date('Y-m-d H:i:s'));
        $dadosConta['FIXA'] = 'N';
        $dadosConta['PERCENTAGEMJUROS'] = 0;

        $dadosConta['PARCELA'] = 1;
        $valor = $dadosConta['VALORTOTAL']/$dadosConta['TOTALPARCELAS'];
        //converte para o formato de numero decimal separado por virgula
        $dadosConta['VALOR'] = number_format($valor, 2, ',', '.');

        $validou = $this->verificaCamposObrigatorios($dadosConta, $this->campos_obrigatorios);
        if ($validou['status']) {
            try {
                $dadosConta = $this->chavesToLowerCase($dadosConta);

                $inserido = $conta->insert($dadosConta);
                if ($inserido) {
                    $inserido = $this->getConta($inserido);
                    $totalparcelas = $dadosConta['totalparcelas'];

                    //verifica se tem mais de uma parcela
                    if ($totalparcelas > 1) {
                        $arrayparcelas = array();
                        //joga a primeira parcela em um array
                        array_push($arrayparcelas, $inserido['entity']);
                        $datavenc = $dadosConta['datavencimento'];
                        //cria o restante das parcelas
                        for($i=1;$i<$totalparcelas;$i++) {
                            $datavenc = strtotime(date("Y-m-d", strtotime($datavenc)) . " +30 days");
                            $datavenc = date("Y-m-d",$datavenc);

                            $dadosConta['datavencimento'] = $datavenc;
                            $dadosConta['parcela'] = $i+1;
                            try {
                                $parcela = $conta->insert($dadosConta);
                                if ($parcela) {
                                    $parcela = $this->getConta($parcela);
                                   array_push($arrayparcelas, $parcela['entity']);
                                }
                            } catch (Exception $e) {
                                return array(
                                    'status'=>false,
                                    'error'=>'Ocorreu um erro ao inserir a parcela da conta no banco de dados.',
                                    'error_db'=>$e->getMessage()
                                );
                            }
                        }
                        return $arrayparcelas;
                    }
                    return $inserido;
                }
            } catch (Exception $e) {
                return array(
                    'status'=>false,
                    'error'=>'Ocorreu um erro ao inserir a conta no banco de dados.',
                    'error_db'=>$e->getMessage()
                );
            }
        } else {
            return $validou;
        }
        return array('status'=>false, 'error'=>'Não foi possível cadastrar a conta');
    }

    public function update($dados, $where = '') {
        $entity = new Mcontas();
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
                $atualizado = $entity->update($dados, $where);
                if($atualizado){
                    return array('status'=>true, 'message'=>'Dados atualizados com sucesso.');
                }
            }
        }catch (Exception $e){
            return array(
                'status'=>false,
                'error'=>'Ocorreu um erro ao '.$tipoupdate.' os dados da conta no banco de dados.',
                'error_db'=>$e->getMessage()
            );
        }
        return array('status'=>false, 'error'=>'Não foi possível '.$tipoupdate.' os dados da conta.');
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