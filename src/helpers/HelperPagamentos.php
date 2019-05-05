<?php


class HelperPagamentos
{
    protected $api_key = 'ak_test_5dqz6ODeAqf7JM1UPkfEeU61b7ZCFo';
    protected $base_url = "https://api.pagar.me/1";
    protected $headers = [
        'Content-Type: application/json'
    ];
//    protected $postback_url = 'localhost:8000/api-webservice/pagamentos/postback';
//    protected $postback_url = 'https://ensdz8gapwvy.x.pipedream.net/';
    protected $postback_url = '';
    public function __construct()
    {
        $link = $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
        $link .= $_SERVER['HTTP_HOST'] . '/api-webservice/pagamentos/postback';
        $this->postback_url = $link;
    }

    public function post($endpoint, $params)
    {
        return $this->request('POST', $endpoint, $params);
    }
    public function get($endpoint, $params)
    {
        return $this->request('GET', $endpoint, $params);
    }
    public function request($method, $endpoint, $params)
    {
        $params['api_key'] = $this->api_key;
        $payload = json_encode($params);
        $this->headers[] = 'Content-Length: ' . strlen($payload);
        $curl = curl_init($this->base_url."/".$endpoint);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);

        $result = curl_exec($curl);

        curl_close($curl);

        $return = json_decode($result, true);
        if(is_array($return)) {
            if(isset($return['errors'])){
                return ['status'=>false, 'message'=>$return['errors']];
            } else {
                if(count($return) == 0) {
                    return ['status'=>false, 'message'=>'Nenhum objeto encontrado'];
                }
                return ['status'=>true, 'entity'=>$return];
            }
        }

        return ['status'=>false, 'message'=>'Não foi possível realizar a requisição.'];
    }
    public function put($endpoint, $params)
    {
        return $this->request('PUT', $endpoint, $params);
    }
    public function criaPlano($params)
    {
        return $this->post('plans', $params);
    }
    public function criaPlanoPremium()
    {
        return $this->criaPlano([
            'amount' => '4000',
            'days' => '30',
            'trial_days' => '20',
            'name' => 'Plano Premium'
        ]);
    }
    public function getPlanos($params)
    {
        return $this->get('plans', $params);
    }
    public function atualizaPlano($params, $idPlano)
    {
        return $this->put('plans/'.$idPlano, $params);
    }
    public function criaAssinatura($dados, $metodo, $codusuario)
    {
        if(!isset($dados['plan_id'])) {
            return ['status'=>false, 'error'=>'Plano não foi informado'];
        }
        $dados['payment_method'] = $metodo;
        $dados['postback_url'] = $this->postback_url;

        $dados['metadata'] = ['codusuario'=>$codusuario];
        return $this->post('subscriptions', $dados);
    }
    public function criaAssinaturaBoleto($dados, $codusuario)
    {
        return $this->criaAssinatura($dados, 'boleto', $codusuario);
    }
    public function criaAssinaturaCredito($dados, $codusuario)
    {
        return $this->criaAssinatura($dados, 'credit_card', $codusuario);
    }
    public function getAssinaturas($params)
    {
        return $this->get('subscriptions', $params);
    }
    public function getAssinatura($id)
    {
        return $this->get('subscriptions/'.$id, []);
    }
    public function atualizaAssinatura($id, $params)
    {
        return $this->put('subscriptions/'.$id, $params);
    }
    public function cancelaAssinatura($id)
    {
        return $this->post('subscriptions/'.$id.'/cancel', []);
    }
}