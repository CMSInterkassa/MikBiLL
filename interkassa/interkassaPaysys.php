<?php

class interkassaPaysys
{
    CONST url_action = 'https://sci.interkassa.com/';

    public $test_mode = true; // тестовый режим
    public $api_enable = false; // вкл. API режим
    public $cashbox_id = ''; // id кассы
    public $secret_key = ''; // секретный ключ
    public $test_key = ''; // тестовый ключ
    public $api_id = ''; // API id
    public $api_key = ''; // API ключ
    public $currency = 'UAH'; // ISO валюта, USD | EUR | UAH | RUB | BYR | XAU
    public $url_page_success = '';
    public $url_page_fail = '';

    public function __construct($user_id = 0, $config = array())
    {
        $this->user_id = $user_id;

        if(empty($config)) {
            $config = include 'config.php';
        }

        if(is_array($config) && !empty($config)){
            foreach ($config as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }

    public function selectPaySys($request)
    {
        if (isset($request['ik_act']) && $request['ik_act'] == 'process'){
            $request['ik_sign'] = self::ikSignFormation($request, $this->secret_key);
            $data = $this->getAnswerFromAPI($request);
        }
        else
            $data = self::ikSignFormation($request, $this->secret_key);

        return $data;
    }

    public function getDataForm($order_id, $amount)
    {
        $FormData = array();
        $FormData['ik_am'] = $amount;
        $FormData['ik_pm_no'] = $order_id;
        $FormData['ik_co_id'] = $this -> cashbox_id;
        $FormData['ik_desc'] = "#{$order_id}";
        $FormData['ik_cur'] = $this -> currency;

        $url = 'https://' . $_SERVER['HTTP_HOST'] . '/';

        $FormData['ik_ia_u'] = $url . 'interkassa.php?callback';
        $FormData['ik_suc_u'] = !empty($this -> url_page_success)? $this -> url_page_success : $url;
        $FormData['ik_fal_u'] = !empty($this -> url_page_fail)? $this -> url_page_fail : $url;
        $FormData['ik_pnd_u'] = $url;

        if ($this -> test_mode) {
            $FormData['ik_pw_via'] = 'test_interkassa_test_xts';
        }

        $FormData['ik_sign'] = self::ikSignFormation($FormData, $this -> secret_key);

        return $FormData;
    }

    public static function ikSignFormation($data, $secret_key)
    {
        if (!empty($data['ik_sign'])) unset($data['ik_sign']);

        $dataSet = array();
        foreach ($data as $key => $value) {
            if (!preg_match('/ik_/', $key)) continue;

            $dataSet[$key] = $value;
        }

        ksort($dataSet, SORT_STRING);
        array_push($dataSet, $secret_key);
        $arg = implode(':', $dataSet);
        $ik_sign = base64_encode(md5($arg, true));

        return $ik_sign;
    }

    public static function getAnswerFromAPI($data)
    {
        $ch = curl_init('https://sci.interkassa.com/');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        return $result;
    }

    public function getIkPaymentSystems()
    {
        $remote_url = 'https://api.interkassa.com/v1/paysystem-input-payway?checkoutId=' . $this->cashbox_id;
        // Create a stream
        $opts = array(
            'http' => array(
                'method' => "GET",
                'header' => "Authorization: Basic " . base64_encode("{$this->api_id}:{$this->api_key}")
            )
        );
        $context = stream_context_create($opts);
        $response = file_get_contents($remote_url, false, $context);
        $json_data = json_decode($response);

        if(empty($response))
            return '<strong style="color:red;">Error!!! System response empty!</strong>';

        if ($json_data->status != 'error') {
            $payment_systems = array();
            if(!empty($json_data->data)){
                foreach ($json_data->data as $ps => $info) {
                    $payment_system = $info->ser;
                    if (!array_key_exists($payment_system, $payment_systems)) {
                        $payment_systems[$payment_system] = array();
                        foreach ($info->name as $name) {
                            if ($name->l == 'en') {
                                $payment_systems[$payment_system]['title'] = ucfirst($name->v);
                            }
                            $payment_systems[$payment_system]['name'][$name->l] = $name->v;
                        }
                    }
                    $payment_systems[$payment_system]['currency'][strtoupper($info->curAls)] = $info->als;
                }
            }
            return !empty($payment_systems)? $payment_systems : '<strong style="color:red;">API connection error or system response empty!</strong>';
        } else {
            if(!empty($json_data->message))
                return '<strong style="color:red;">API connection error!<br>' . $json_data->message . '</strong>';
            else
                return '<strong style="color:red;">API connection error or system response empty!</strong>';
        }
    }

    public function checkIP(){
        $ip_stack = array(
            'ip_begin'=>'151.80.190.97',
            'ip_end'=>'151.80.190.104'
        );
        $ip = ip2long($_SERVER['REMOTE_ADDR'])? ip2long($_SERVER['REMOTE_ADDR']) : !ip2long($_SERVER['REMOTE_ADDR']);
        if(($ip >= ip2long($ip_stack['ip_begin'])) && ($ip <= ip2long($ip_stack['ip_end']))){
            return true;
        }
        return false;
    }
}