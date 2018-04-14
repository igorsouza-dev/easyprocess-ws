<?php

class HelperEmail
{
    /*
     * Dados:
     * EMAIL,
     * LOGIN,
     * CODUSUARIO
     * */
    function envia($dados) {
        $senha = strtoupper(substr(md5(uniqid(microtime(),1)).getmypid(), 0, 8)); // 8 caracteres
        $vars = new Vars();
        $email = strtolower($dados['EMAIL']);

        $msg = "<span style='font-weight: bold; color:#FF0000'>Por favor, não responda este email!</span><br /> <br /> <br />
				Prezado(a) ".$dados['NOME'].", sua senha foi alterada com socesso em ".date('d/m/Y H:i:s').".
				Seguem abaixo os dados para acessar a área restrita do Easy Process:<br /><br />
				<b>Login</b>: ".$dados['LOGIN']."<br />
				<b>Senha: </b>".$senha."<br /><br /><br />
				área de acesso <br />
				
				Atenciosamente,<br /><br />
				
				Equipe Easy Process<br />
				<b>".$vars->_MAIL_RAZAOSOCIAL." - Easy Process</b>";

        $mail = new PHPMailer();
        $mail->IsHTML(true);
        //Informa que será utilizado o SMTP para envio do e-mail
        $mail->IsSMTP();
        //Informa que a conexão com o SMTP será autênticado
        $mail->SMTPAuth   = true;

        //Titulo do e-mail que será enviado
        $mail->Subject = 'Nova senha';

        //Preenchimento do campo FROM do e-mail
        //$mail->From = $mail->Username;
        //$mail->FromName = "LIFESISTEMAS";

        //E-mail para a qual o e-mail será enviado
        $mail->AddAddress($email);

        //Conteúdo do e-mail
        $mail->Body = $msg;
        $mail->AltBody = $mail->Body;

        //Dispara o e-mail
        $enviado = $mail->Send();
//        $enviado = true;
        if ($enviado) {

            $helperUsuario = new HelperUsuario();
            $dados_usuario = array(
                "SENHA" => "PASSWORD('".$senha."')",
                "DATASENHA"=>date("Y-m-d H:i:s"),
                "RESPSENHA"=>'RECUPERACAO DE SENHA'
            );
            $dados_usuario[$helperUsuario->primarykey] = $dados[$helperUsuario->primarykey];

            $resultado = $helperUsuario->update($dados_usuario);
            if ($resultado['status']) {
                return array('status'=>true, 'message'=>'E-mail enviado.');
            } else {
               return array('status'=>false, 'error'=>'E-mail enviado, porém ocorreu um erro ao atualizar a nova senha no banco de dados.');
            }

        } else {
            return array('status'=>false, 'error'=>'Não foi possível enviar o e-mail.');
        }
    }
}