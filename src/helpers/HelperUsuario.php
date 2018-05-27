<?php

class HelperUsuario extends HelperGeral
{
    public $primarykey = 'CODUSUARIO';
    public $campos = array(
        'CODUSUARIO',
        'DATAHORACRIACAO',
        'RESPCRIACAO','CODPESSOA',
        'CODEMPRESA', 'CODPERFIL',
        'CODCOLIGADA',
        'LOGIN', 'SENHA',
        'DATASENHA', 'RESPSENHA',
        'ULTIMOACESSO', 'DATAHORALOGIN',
        'ALTERALOGIN', 'KEYLOGIN',
        'TIPO', 'ATIVO', 'USUARIOPREMIUM', 'EXCLUIDO', 'DATAHORAEXCLUSAO',
        'EXCLUIDOPOR'
    );

    public $campos_obrigatorios = array(
        'CODCOLIGADA',
        'CODPESSOA',
        'CODPERFIL',
        'LOGIN',
        'SENHA',
        'ALTERALOGIN',
        'TIPO',
        'USUARIOPREMIUM'
    );

    public function getUsuario($id)
    {
        $params = array(
            $this->primarykey => $id,
            'EXCLUIDO'=>"N"
        );

        $result = $this->getUsuarios($params);
        if(isset($result['entity'])){
            $result['entity'] = $result['entity'][0];
        }
        return $result;
    }

    public function getUsuarios($params)
    {
        $resultado = $this->queryWithParams($params, 'Ousuarios');
        if(count($resultado)){
            $usuarios = array();
            foreach($resultado as $usuario){
                $usuarios[] = $this->toArray($usuario, $this->campos);
            }
            return array('status'=>true, 'entity'=>$usuarios);
        }else{
            return array('status'=>false, 'error'=>'Não foram encontrados usuários com os dados informados.');
        }
    }

    public function insert($dados)
    {
        $dados = $this->chavesToUpperCase($dados);
        $usuario = new Ousuarios();

        $dados_usuario = $this->removeCamposInvalidos($dados, $this->campos);
        $helperPessoa = new HelperPessoa();
        $pessoa = null;
        //verifica se foi informado uma pessoa para o usuario
        if(isset($dados[$helperPessoa->primarykey])){
            //se foi informado, verifica se é valido
            $codpessoa = $dados[$helperPessoa->primarykey];

            $pessoa = $helperPessoa->getPessoa($codpessoa);
            if(!$pessoa['status']){
                return array('status'=>false, 'error'=>'Pessoa informada não é válida');
            }else{
                $pessoa = $pessoa['entity'];
            }
        }else{
            $result = $helperPessoa->insert($dados);
            if(!$result['status']){
                return $result;
            }else{
                $pessoa = $result['entity'];
                $codpessoa = $pessoa[$helperPessoa->primarykey];
            }
        }
        $dados_usuario[$helperPessoa->primarykey] = $codpessoa;
        $helperEmpresa = new HelperEmpresa();
        $empresa = null;
        //verifica se foi informada uma empresa
        if (isset($dados[$helperEmpresa->primarykey])) {
            //se foi informado, verifica se é valida
            $codempresa = $dados[$helperEmpresa->primarykey];
            $empresa = $helperEmpresa->getEmpresa($codempresa);
            if (!$empresa['status']) {
                return array('status'=>false, 'error'=>'Empresa informada não é válida');
            } else {
                $empresa = $empresa['entity'];
            }
        } else {
            $result = $helperEmpresa->insert($dados_usuario);
            if(!$result['status']){//se nao foi possivel inserir empresa no banco, retorna com erro
                return $result;
            }else{
                $empresa = $result['entity'];
                $codempresa = $empresa[$helperEmpresa->primarykey];
            }
        }

        $dados_usuario[$helperEmpresa->primarykey] = $codempresa;

        if(!isset($dados['RESPCRIACAO'])){
            $usuario->setResponsavel("APP EasyProcess");
        }else{
            $usuario->setResponsavel($dados['RESPCRIACAO']);
        }
        $dados_usuario['CODCOLIGADA'] = 1;
        $dados_usuario['ALTERALOGIN'] = 'S';
        $dados_usuario['CODPERFIL'] = 2;
        $dados_usuario['TIPO'] = 'COL';
        $dados_usuario['SENHA'] = "PASSWORD('".$this->decode64($dados_usuario['SENHA'])."')";


        $validou = $this->verificaCamposObrigatorios($dados_usuario, $this->campos_obrigatorios);

        if($validou['status']){
            try{
                $inserido = $usuario->insert($dados_usuario);
                if($inserido) {
                    $usuario = $this->getUsuario($inserido);

                    if($usuario['status'] && $pessoa){
                        $usuario['entity']['PESSOA'] = $pessoa;
                    }
                    return $usuario;
                }
            }catch (Exception $e){
                return array(
                    'status'=>false,
                    'error'=>'Ocorreu um erro ao inserir o usuário no banco de dados.',
                    'erro_db'=>$e->getMessage()
                );
            }
        }else{
            return $validou;
        }
        return array('status'=>false, 'error'=>'Não foi possível inserir o usuário no banco de dados.');
    }

    public function update($dados, $where=''){
        $entity = new Ousuarios();
        if($where == ''){
            if(isset($dados[$this->primarykey])){
                $where = $this->primarykey.' = '.$dados[$this->primarykey];
            }
        }
        if(isset($dados['EXCLUIDO'])){
            if($dados['EXCLUIDO']=='S'){
                if(isset($dados['EXCLUIDOPOR'])){
                    $entity->setResponsavel($dados['EXCLUIDOPOR']);
                }else{
                    $entity->setResponsavel('APP EasyProcess');
                }
            }
        }
        if(isset($dados['SENHA'])){
            $dados['SENHA'] = "PASSWORD('".$dados['SENHA']."')";
            $dados['DATASENHA'] = date('Y-m-d H:i:s');
            $dados['RESPSENHA'] = 'APP EasyProcess';
        }
        $usuarios = $this->getUsuarios($where);
        if(!$usuarios['status']){
            return $usuarios;
        }
        $dados = $this->removeCamposInvalidos($dados, $this->campos);
        try{
            $atualizado = $entity->update($dados, $where);
            if($atualizado){
                return array('status'=>true, 'message'=>'Dados atualizados com sucesso.');
            }
        }catch (Exception $e){
            return array('status'=>false, 'error'=>'Ocorreu um erro ao atualizar os dados de usuário no banco de dados.');
        }
        return array('status'=>false, 'error'=>'Não foi possível atualizar os dados de usuário.');
    }

    public function delete($where, $responsavel = '')
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

    public function isPremium($codusuario) {
        $resultado = $this->getUsuario($codusuario);
        if($resultado['status']) {
            $usuario = $resultado['entity'];
            if($usuario['USUARIOPREMIUM'] == 'S') {
                return true;
            }
        }
        return false;
    }

}