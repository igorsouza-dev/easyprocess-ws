<?php

class HelperProcessoParteProcurador extends HelperGeral
{
    public $primarykey = 'CODPROCESSOPARTEPROCURADOR';
    public $campos = array(
        'CODPROCESSOPARTEPROCURADOR', 'CODPROCESSO',
        'CODPESSOA', 'ATIVO'
    );

    public $campos_obrigatorios = array(
        'CODPROCESSO',
        'CODPESSOA', 'ATIVO'
    );

    public function getParteProcurador($id)
    {
        $params = array(
            $this->primarykey => $id
        );
        $result = $this->getPartesProcurador($params);
        if(isset($result['entity'])){
            $result['entity'] = $result['entity'][0];
        }
        return $result;
    }

    public function getPartesProcurador($params)
    {
        $resultado = $this->queryWithParams($params, 'Epprocessoparteprocurador');
        if(count($resultado)){
            $partesprocurador = array();
            $helperPessoa = new HelperPessoa();
            foreach($resultado as $parte){
                $arrayParte = $this->toArray($parte, $this->campos);
                $codpessoa = $arrayParte['CODPESSOA'];
                $pessoa = $helperPessoa->getPessoa($codpessoa);
                if($pessoa['status']) {
                    $arrayParte['Pessoa'] = $pessoa['entity'];
                }
                $partesprocurador[] = $arrayParte;
            }
            return array('status'=>true, 'entity'=>$partesprocurador);
        }else{
            return array('status'=>false, 'error'=>'Não foram encontradas as partes do procurador com os dados informados.');
        }
    }
    public function getPartesProcuradorByProcesso($codprocesso)
    {
        $params = array(
            'CODPROCESSO'=>$codprocesso
        );
        return $this->getPartesProcurador($params);
    }
    public function insert($dados)
    {
        $dados = $this->chavesToUpperCase($dados);
        $parteprocurador = new Epprocessoparteprocurador();
        $dados_parte_procurador = $this->removeCamposInvalidos($dados, $this->campos);

        $validou = $this->verificaCamposObrigatorios($dados_parte_procurador, $this->campos_obrigatorios);
        if($validou['status']){
            try{
                $inserido = $parteprocurador->insert($dados_parte_procurador);
                $parteprocurador = $this->getParteProcurador($inserido);
                return $parteprocurador;
            }catch (Exception $e){
                return array('status'=>false, 'error'=>'Ocorreu um erro durante a inserção da parte do procurador do processo no banco de dados.');
            }
        }else{
            return $validou;
        }
    }

    public function update($dados, $where='')
    {
        $entity = new Epprocessoparteprocurador();

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
            return array('status'=>false, 'error'=>'Ocorreu um erro ao atualizar os dados da parte do procurador do processo no banco de dados.');
        }
        return array('status'=>false, 'error'=>'Não foi possível atualizar os dados da parte do procurador do processo.');
    }

}