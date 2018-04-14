<?php
class HelperProcesso extends HelperGeral
{
    public $primarykey = 'CODPROCESSO';
    public $campos = array(
        'CODPROCESSO','CODUSUARIO',
        'NUMEROPROCESSO', 'ORGAOJULGADOR',
        'CODIGOCLASSEJUDICIAL', 'COMPETENCIAJUDICIAL',
        'JUIZRESPONSAVEL', 'DATAHORAAUTUACAO'
    );
    public $campos_obrigatorios = array(
        'CODUSUARIO'
    );
    public function getProcesso($id)
    {
        $params = array(
            $this->primarykey => $id
        );
        $result = $this->getProcessos($params);
        if(isset($result['entity'])){
            $result['entity'] = $result['entity'][0];
        }
        return $result;
    }
    public function getProcessos($params)
    {
        $resultado = $this->queryWithParams($params, 'Epprocesso');
        if(count($resultado)){
            $processos = array();
            foreach($resultado as $processo){
                $processos[] = $this->toArray($processo, $this->campos);
            }
            return array('status'=>true, 'entity'=>$processos);
        }else{
            return array('status'=>false, 'error'=>'Não foram encontrados processos com os dados informados.');
        }

    }

    public function insert($dados)
    {
        $dados = $this->chavesToUpperCase($dados);
        $processo = new Epprocesso();
        $dados_processo = $this->removeCamposInvalidos($dados, $this->campos);

        $validou = $this->verificaCamposObrigatorios($dados_processo, $this->campos_obrigatorios);
        if($validou['status']){
            try{
                $inserido = $processo->insert($dados_processo);
                $processo = $this->getProcesso($inserido);
                return $processo;
            }catch (Exception $e){
                return array('status'=>false, 'error'=>'Ocorreu um erro durante a inserção do processo no banco de dados.');
            }
        }else{
            return $validou;
        }
    }

    public function update($dados, $where=''){
        $entity = new Epprocesso();

        if($where == ''){
            if(isset($dados[$this->primarykey])){
                $where = $this->primarykey.' = '.$dados[$this->primarykey];
            }
        }

        $dados = $this->removeCamposInvalidos($dados, $this->campos);
        try{

            $atualizado = $entity->update($dados, $where);
            if($atualizado){
                return array('status'=>true, 'message'=>'Dados atualizados com sucesso.');
            }
        }catch (Exception $e){
            return array('status'=>false, 'error'=>'Ocorreu um erro ao atualizar os dados do processo no banco de dados.');
        }
        return array('status'=>false, 'error'=>'Não foi possível atualizar os dados do processo.');
    }
}