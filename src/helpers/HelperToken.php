<?php
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Parser;

class HelperToken{
    protected $key;
    public function __construct($key)
    {
        $this->key = $key;
    }
    public function generate($login)
    {
        $signer = new Sha256();
        $token = (new Builder())->setIssuer("http://www.easyprocess.com.br/")
            ->setAudience('App EasyProcess')
            ->set('user', $login)
            ->setIssuedAt(time())
            ->setNotBefore(time() + 60)
            ->setExpiration(time() + 3600)
            ->sign($signer, $this->key)
            ->getToken();
        return $token;
    }
    public function validate($token)
    {
        $signer = new Sha256();
        try{
            $token = (new Parser())->parse((string) $token);
            return $token->verify($signer, $this->key);
        }catch (UnexpectedValueException $e){

        }catch (InvalidArgumentException $e){

        }
        return false;

    }
}