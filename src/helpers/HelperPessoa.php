<?php

class HelperPessoa extends HelperGeral {
    public $primarykey = 'CODPESSOA';
    public $campos = array(
        'CODPESSOA','DATAHORAINCLUSAO',
        'RESPINCLUSAO', 'DATAHORAALTERACAO',
        'RESPALTERACAO', 'EXCLUIDO',
        'DATAHORAEXCLUSAO','EXCLUIDOPOR',
        'NOME', 'RAZAOSOCIAL',
        'SEXO', 'DATANASCIMENTO',
        'ESTADOCIVIL', 'CPF',
        'OAB', 'OABUF',
        'CNPJ', 'RG',
        'OERG', 'DATARG',
        'CTPS', 'CNH',
        'TITULOELEITOR', 'ZONA',
        'SECAO', 'RESERVISTA',
        'NOMEPAI', 'NOMEMAE',
        'NACIONALIDADE', 'NATURALIDADE',
        'ENDERECO','NUMERO',
        'BAIRRO', 'COMPLEMENTO',
        'CIDADE', 'UF',
        'CEP', 'CONTATO',
        'EMAIL', 'EMAIL2',
        'FONE1', 'FONE2',
        'FONE3', 'FOTO',
        'LARGURAFOTO', 'ALTURAFOTO',
        'DATAHORAFOTO', 'DATAHORAFOTO',
        'TIPO', 'ATIVA'
    );

    public $campos_obrigatorios = array(
        'NOME',
        'DATAHORAINCLUSAO',
        'RESPINCLUSAO'
    );

    public function getPessoa($id)
    {
        $params = array(
            $this->primarykey => $id,
            'EXCLUIDO'=>"N"
        );
        $result = $this->getPessoas($params);
        if(isset($result['entity'])){
            $result['entity'] = $result['entity'][0];
        }
        return $result;
    }

    public function getPessoas($params)
    {
        $resultado = $this->queryWithParams($params, 'Opessoas');
        if(count($resultado)){
            $pessoas = array();
            foreach($resultado as $pessoa){
                $pessoas[] = $this->toArray($pessoa, $this->campos);
            }
            return array('status'=>true, 'entity'=>$pessoas);
        }else{
            return array('status'=>false, 'error'=>'Não foram encontradas pessoas com os dados informados.');
        }
    }
    public function findByCpf($cpf)
    {
        $params = [
            'EXCLUIDO'=>'N',
            'CPF'=>trim($cpf)
        ];
        $result = $this->getPessoas($params);
        if($result['status']){
            $result['entity'] = $result['entity'][0];
        }
        return $result;
    }

    function validaCPF($cpf)
    {
        $cpf = str_replace('.', '', $cpf);
        $cpf = str_replace('-', '', $cpf);

        $cpf = str_pad(preg_replace('/[^0-9]/', '', $cpf), 11, '0', STR_PAD_LEFT);


        if (strlen($cpf) != 11 || $cpf == '00000000000' || $cpf == '11111111111' || $cpf == '22222222222' || $cpf == '33333333333' || $cpf == '44444444444' || $cpf == '55555555555' || $cpf == '66666666666' || $cpf == '77777777777' || $cpf == '88888888888' || $cpf == '99999999999')
        {
            return false;
        }
        else
        {   // Calcula os números para verificar se o CPF é verdadeiro
            for ($t = 9; $t < 11; $t++) {
                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $cpf{$c} * (($t + 1) - $c);
                }

                $d = ((10 * $d) % 11) % 10;

                if ($cpf{$c} != $d) {
                    return false;
                }
            }

            return true;
        }
    }

    public function findByEmail($email)
    {
        $params = [
            'EXCLUIDO'=>'N',
            'EMAIL'=>trim($email)
        ];
        $result = $this->getPessoas($params);
        if($result['status']){
            $result['entity'] = $result['entity'][0];
        }
        return $result;
    }
    public function insert($dados)
    {
        $dados = $this->chavesToUpperCase($dados);
        $pessoa = new Opessoas();
        $dados_pessoa = $this->removeCamposInvalidos($dados, $this->campos);

        $dados_pessoa['DATAHORAINCLUSAO'] = date( 'Y-m-d H:i:s');
        if(!isset($dados['RESPINCLUSAO'])){
            $dados_pessoa['RESPINCLUSAO'] = 'APP EasyProcess';
        }
        $validou = $this->verificaCamposObrigatorios($dados_pessoa, $this->campos_obrigatorios);
        if($validou['status']){
            try{
                $dados_pessoa = $this->decodificaCaracteres($dados_pessoa);
                $inserido = $pessoa->insert($dados_pessoa);

                $pessoa = $this->getPessoa($inserido);
                return $pessoa;
            }catch (Exception $e){
                return array('status'=>false, 'error'=>'Ocorreu um erro durante a inserção da pessoa no banco de dados.');
            }
        }else{
            return $validou;
        }
    }
    public function update($dados, $where='')
    {
        $entity = new Opessoas();

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
                if(!isset($dados['EXCLUIDOPOR'])){
                    $dados['EXCLUIDOPOR'] = 'APP EasyProcess';
                }
            }
        }

        if(!$isExclusao){
            $dados['DATAHORAALTERACAO'] = date('Y-m-d H:i:s');

            if(!array_key_exists('RESPALTERACAO', $dados)){
                $dados['RESPALTERACAO'] = 'APP EasyProcess';
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
            return array('status'=>false, 'error'=>'Ocorreu um erro ao atualizar os dados da pessoa no banco de dados.');
        }
        return array('status'=>false, 'error'=>'Não foi possível atualizar os dados da pessoa.');
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

    public static function formataCPF($cpf)
    {
        return vsprintf('%s%s%s.%s%s%s.%s%s%s-%s%s', str_split($cpf));
    }

    public static function formataCNPJ($cnpj)
    {
        return vsprintf('%s%s.%s%s%s.%s%s%s/%s%s%s%s-%s%s', str_split($cnpj));
    }

    public static function formataCEP($cep)
    {
        return vsprintf('%s%s%s%s%s-%s%s%s', str_split($cep));
    }
}