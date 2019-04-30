<?php
// Versao atual do php não permite usar a biblioteca do Pagar Me
//use \PagarMe\Exceptions\PagarMeException;

class HelperPagamentos
{
    protected $api_key = 'ak_test_2LtdFAVSWsHyScAvl8fFEI9vLLIfG1';
    protected $pagarme;
//    protected $postback_url = 'localhost:8000/api-webservice/pagamentos/postback';
//    protected $postback_url = 'https://ensdz8gapwvy.x.pipedream.net/';
    protected $postback_url = '';
    public function __construct()
    {
        $link = $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
        $link .= $_SERVER['HTTP_HOST'] . '/api-webservice/pagamentos/postback';
        $this->postback_url = $link;
        echo $this->postback_url;
        $this->pagarme = new PagarMe\Client($this->api_key);
    }
    public function criaPlanoPremium()
    {
        try{
            $plano = $this->pagarme->plans()->create([
                'amount' => '4000',
                'days' => '30',
                'trial_days' => '20',
                'name' => 'Plano Premium'
            ]);
            return ['status'=>true, 'entity'=>$plano];
        } catch (PagarMeException $e) {
            return ['status'=>false, 'error'=>$e->getMessage()];
        }

    }
    public function getPlanos($params)
    {
        try{
            $planos = $this->pagarme->plans()->get($params);
            return ['status'=>true, 'entity'=>$planos];
        } catch (PagarMeException $e) {
            return ['status'=>false, 'error'=>$e->getMessage()];
        }
    }
    public function atualizaPlano($params)
    {
        try{
            $planos = $this->pagarme->plans()->update($params);
            return ['status'=>true, 'entity'=>$planos];
        } catch (PagarMeException $e) {
            return ['status'=>false, 'error'=>$e->getMessage()];
        }
    }
    public function criaAssinatura($dados, $metodo, $codusuario)
    {
        if(!isset($dados['plan_id'])) {
            return ['status'=>false, 'error'=>'Plano não foi informado'];
        }
        $dados['payment_method'] = $metodo;
        $dados['postback_url'] = $this->postback_url;
        $dados['metadata'] = 'codusuario='.$codusuario;
        try{
            $subscription = $this->pagarme->subscriptions()->create($dados);
            return ['status'=>true, 'entity'=>$subscription];
        } catch (PagarMeException $e) {
            return ['status'=>false, 'error'=>$e->getMessage()];
        }
    }
    public function criaAssinaturaBoleto($dados, $codusuario)
    {
        return $this->criaAssinatura($dados, 'boleto', $codusuario);
    }
    public function criaAssinaturaCredito($dados, $codusuario)
    {
        return $this->criaAssinatura($dados, 'credit_card', $codusuario);
    }

    public function criaTransacao()
    {
//        $transaction = $this->pagarme->transactions()->create([
//            'amount' => 1000,
//            'payment_method' => 'credit_card',
//            'card_holder_name' => 'Anakin Skywalker',
//            'card_cvv' => '123',
//            'card_number' => '4242424242424242',
//            'card_expiration_date' => '1220',
//            'customer' => [
//                'external_id' => '1',
//                'name' => 'Nome do cliente',//                'type' => 'individual',
//                'country' => 'br',
//                'documents' => [
//                    [
//                        'type' => 'cpf',
//                        'number' => '00000000000'
//                    ]
//                ],
//                'phone_numbers' => [ '+551199999999' ],
//                'email' => 'cliente@email.com'
//            ],
//            'billing' => [
//                'name' => 'Nome do pagador',
//                'address' => [
//                    'country' => 'br',
//                    'street' => 'Avenida Brigadeiro Faria Lima',
//                    'street_number' => '1811',
//                    'state' => 'sp',
//                    'city' => 'Sao Paulo',
//                    'neighborhood' => 'Jardim Paulistano',
//                    'zipcode' => '01451001'
//                ]
//            ],
//            'shipping' => [
//                'name' => 'Nome de quem receberá o produto',
//                'fee' => 1020,
//                'delivery_date' => '2018-09-22',
//                'expedited' => false,
//                'address' => [
//                    'country' => 'br',
//                    'street' => 'Avenida Brigadeiro Faria Lima',
//                    'street_number' => '1811',
//                    'state' => 'sp',
//                    'city' => 'Sao Paulo',
//                    'neighborhood' => 'Jardim Paulistano',
//                    'zipcode' => '01451001'
//                ]
//            ],
//            'items' => [
//                [
//                    'id' => '1',
//                    'title' => 'R2D2',
//                    'unit_price' => 300,
//                    'quantity' => 1,
//                    'tangible' => true
//                ],
//                [
//                    'id' => '2',
//                    'title' => 'C-3PO',
//                    'unit_price' => 700,
//                    'quantity' => 1,
//                    'tangible' => true
//                ]
//            ]
//        ]);
        $transaction = $this->pagarme->transactions()->create([
            'amount' => 1000,
            'payment_method' => 'boleto',
            'async' => false,
            'customer' => [
                'external_id' => '1',
                'name' => 'Nome do cliente',
                'type' => 'individual',
                'country' => 'br',
                'documents' => [
                    [
                        'type' => 'cpf',
                        'number' => '00000000000'
                    ]
                ],
                'phone_numbers' => [ '+551199999999' ],
                'email' => 'cliente@email.com'
            ]
        ]);
        return $transaction;
    }
}