<?php
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
class HelperToken{
    protected $key;
    protected $audience = 'App EasyProcess';
    protected $user;
    protected $issuer = "http://www.easyprocess.com.br/";

    public function __construct($key)
    {
        $this->key = $key;
    }
    public function generate($payload)
    {
        $this->user = $payload['user'];

        $signer = new Sha256();
        $token = (new Builder())->setIssuer($this->issuer)
            ->setAudience($this->audience)
            ->set('user', $this->user)
            ->set('coduser', $payload['coduser'])
            ->setSubject($this->user)
            ->setIssuedAt(time())
            ->setNotBefore(time())
            ->setExpiration(time() + 3600)
            ->sign($signer, $this->key)
            ->getToken();
        return $token;
    }

    public function validateToken($token)
    {
        $signer = new Sha256();
        $validationData = new ValidationData();

        $validationData->setIssuer($this->issuer);
        $validationData->setAudience($this->audience);

        try{
            $token = (new Parser())->parse((string) $token);
            $verificado = $token->verify($signer, $this->key);
            if($verificado) {
                return $token->validate($validationData);
            }

        }catch (UnexpectedValueException $e){

        }catch (InvalidArgumentException $e){

        }
        return false;

    }
    public static function getDataFromPayload($param, $token)
    {

        try{
            $token = (new Parser())->parse((string) $token); // Parses from a string
            return $token->getClaim($param);

        } catch (UnexpectedValueException $e){

        }catch (InvalidArgumentException $e){

        }
        return false;

    }
}