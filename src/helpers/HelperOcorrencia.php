<?php

class HelperOcorrencia extends HelperGeral
{
    public $primarykey = 'CODOCORRENCIA';
    public $campos = array(
        'CODOCORRENCIA','CODEMPRESA',
        'CODUSUARIO', 'CODPROCESSO',
        'CODTIPOOCORRENCIA', 'CODFORNECEDOR',
        'CODCLIENTE','DATAOCORRENCIA',
        'HORAOCORRENCIA','DESCRICAO',
        'DATAHORAINCLUSAO', 'RESPINCLUSAO',
        'DATAHORAALTERACAO', 'RESPALTERACAO',
        'DATAHORAEXCLUSAO','EXCLUIDOPOR',
        'EXCLUIDO'
    );
    public $campos_obrigatorios = array(
        'CODEMPRESA', 'CODTIPOOCORRENCIA',
        'DATAOCORRENCIA'
    );

    public function getOcorrencia($id)
    {
        $params = array(
            $this->primarykey => $id,
            'EXCLUIDO'=>"N"
        );

        $result = $this->getOcorrencias($params);
        if(isset($result['entity'])){
            $result['entity'] = $result['entity'][0];
        }
        return $result;
    }

    public function getOcorrencias($params)
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
        $dados_ocorrencia = $this->removeCamposInvalidos($dados, $this->campos);

        $helperEmpresa = new HelperEmpresa();
        $empresa = null;

        //verifica se foi informada uma empresa para a ocorrencia
        if (isset($dados[$helperEmpresa->primarykey])) {
            //se foi, verifica se é valida
            $codempresa = $dados[$helperEmpresa->primarykey];

            $empresa = $helperEmpresa->getEmpresa($codempresa);
            if (!$empresa['status']) {
               return array('status'=>false, 'error'=>'Empresa informada é inválida');
            }

            $dados_ocorrencia[$helperEmpresa->primarykey] = $codempresa;
            $ocorrencia->setEmpresa($codempresa);

        } else {
            return array('status'=>false, 'error'=>'Não foi informada uma empresa');
        }

        $helperUsuario = new HelperUsuario();
        $usuario = null;

        //verifica se foi informado um usuario para a ocorrencia
        if (isset($dados[$helperUsuario->primarykey])) {
            //se foi, verifica se existe
            $codusuario = $dados[$helperUsuario->primarykey];

            $usuario = $helperUsuario->getUsuario($codusuario);
            if (!$usuario['status']) {
                return array('status'=>false, 'error'=>'Usuário informado é inválido');
            }
            $dados_ocorrencia[$helperUsuario->primarykey] = $codusuario;
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
            $dados_ocorrencia[$helperProcesso->primarykey] = $codprocesso;
        }


        if (!isset($dados['RESPINCLUSAO'])) {
            $ocorrencia->setResponsavel('APP EasyProcess');
        } else {
            $ocorrencia->setResponsavel($dados['RESPINCLUSAO']);
        }
    }
}