<?php

class HelperAssuntoProcesso extends HelperGeral
{
    public $primarykey = 'CODASSUNTOPROCESSO';
    public $campos = array(
        'CODASSUNTOPROCESSO', 'CODPROCESSO',
        'PRINCIPAL', 'CODIGONACIONAL'
    );
    public $campos_obrigatorios = array(
        'CODPROCESSO', 'PRINCIPAL', 'CODIGONACIONAL'
    );

    public function getAssunto($id)
    {
        $params = array(
            $this->primarykey => $id
        );
        $result = $this->getAssuntos($params);
        if(isset($result['entity'])){
            $result['entity'] = $result['entity'][0];
        }
        return $result;
    }

    public function getAssuntos($params)
    {
        $resultado = $this->queryWithParams($params, 'Epassuntosprocesso');
        if(count($resultado)){
            $assuntos = array();
            foreach($resultado as $assunto){
                $assuntos[] = $this->toArray($assunto, $this->campos);
            }
            return array('status'=>true, 'entity'=>$assuntos);
        }else{
            return array('status'=>false, 'error'=>'Não foram encontrados assuntos com os dados informados.');
        }
    }

    public function getAssuntosByProcesso($codprocesso)
    {
        $params = array(
            'CODPROCESSO'=>$codprocesso
        );
        return $this->getAssuntos($params);
    }

    public function insert($dados)
    {
        $dados = $this->chavesToUpperCase($dados);
        $assuntoprocesso = new Epassuntosprocesso();
        $dados_assunto = $this->removeCamposInvalidos($dados, $this->campos);

        $validou = $this->verificaCamposObrigatorios($dados_assunto, $this->campos_obrigatorios);
        if($validou['status']){
            try{
                $dados_assunto = $this->decodificaCaracteres($dados_assunto);
                $inserido = $assuntoprocesso->insert($dados_assunto);
                $assuntoprocesso = $this->getAssunto($inserido);
                return $assuntoprocesso;
            }catch (Exception $e){
                return array('status'=>false, 'error'=>'Ocorreu um erro durante a inserção do assunto do processo no banco de dados.');
            }
        }else{
            return $validou;
        }
    }

    public function update($dados, $where='')
    {
        $entity = new Epassuntosprocesso();

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
            return array('status'=>false, 'error'=>'Ocorreu um erro ao atualizar os dados do assunto do processo no banco de dados.');
        }
        return array('status'=>false, 'error'=>'Não foi possível atualizar os dados do assunto do processo.');
    }

}