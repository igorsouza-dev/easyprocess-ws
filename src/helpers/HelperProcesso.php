<?php
class HelperProcesso extends HelperGeral
{
    public $primarykey = 'CODPROCESSO';
    public $campos = array(
        'CODPROCESSO','CODUSUARIO',
        'NUMEROPROCESSO', 'APELIDO',
        'ORGAOJULGADOR', 'CODIGOCLASSEJUDICIAL',
        'COMPETENCIAJUDICIAL', 'JUIZRESPONSAVEL',
        'DATAHORAAUTUACAO', 'EXIBIRAPP'
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
            $helperPartes = new HelperProcessoParte();
            $helperProcurador = new HelperProcessoParteProcurador();
            $helperAssunto = new HelperAssuntoProcesso();
            foreach($resultado as $processo){
                $arrayProcesso = $this->toArray($processo, $this->campos);
                $codprocesso = $arrayProcesso['CODPROCESSO'];

                $partes = $helperPartes->getPartesByProcesso($codprocesso);
                if($partes['status']) {
                    $arrayProcesso['Partes'] = $partes['entity'];
                }

                $partesprocurador = $helperProcurador->getPartesProcuradorByProcesso($codprocesso);
                if($partes['status']) {
                    $arrayProcesso['PartesProcurador'] = $partesprocurador['entity'];
                }

                $assuntos = $helperAssunto->getAssuntosByProcesso($codprocesso);
                if($partes['status']) {
                    $arrayProcesso['Assuntos'] = $assuntos['entity'];
                }
                $processos[] = $arrayProcesso;
            }
            return array('status'=>true, 'entity'=>$processos);
        }else{
            return array('status'=>false, 'error'=>'Não foram encontrados processos com os dados informados.');
        }

    }

    public function getProcessoByNumero($numero)
    {
        return $this->getProcessos(["NUMEROPROCESSO" => $numero]);
    }

    public function insert($dados)
    {
        $dados = $this->chavesToUpperCase($dados);
        $processo = new Epprocesso();
        $dados_processo = $this->removeCamposInvalidos($dados, $this->campos);

        $validou = $this->verificaCamposObrigatorios($dados_processo, $this->campos_obrigatorios);
        if($validou['status']){
            try{
                $dados_processo = $this->decodificaCaracteres($dados_processo);
                $inserido = $processo->insert($dados_processo);
                $processo = $this->getProcesso($inserido);
                return $processo;
            }catch (Exception $e){
                return array(
                    'status'=>false,
                    'error'=>'Ocorreu um erro durante a inserção do processo no banco de dados.',
//                    'error_db'=>$e->getMessage()
                );
            }
        }else{
            return $validou;
        }
    }

    public function update($dados, $where='')
    {
        $entity = new Epprocesso();

        if($where == ''){
            if(isset($dados[$this->primarykey])){
                $where = $this->primarykey.' = '.$dados[$this->primarykey];
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
            return array(
                'status'=>false,
                'error'=>'Ocorreu um erro ao atualizar os dados do processo no banco de dados.',
                'error_db'=>$e->getMessage()
            );
        }
        return array('status'=>false, 'error'=>'Não foi possível atualizar os dados do processo.');
    }
}