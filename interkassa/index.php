<?php
//ini_set('display_errors',1);

if(file_exists(dirname(dirname(dirname(__DIR__))) . '/data/lib/gettext.inc')) {
    require_once dirname(dirname(dirname(__DIR__))) . '/data/lib/gettext.inc';
} elseif(file_exists(dirname(dirname(dirname(__DIR__))) . '/data/lib/SystemsClass.php')) {
    require_once dirname(dirname(dirname(__DIR__))) . '/data/lib/SystemsClass.php';
}

require_once 'interkassaPaysys.php';

$interkassa = new interkassaPaysys($user['uid']);

if(isset($_REQUEST['callback'])){

    $response = $_POST;

    if(empty($response)) die('LOL! Bad request!!!');

    if ($response['ik_pw_via'] == 'test_interkassa_test_xts')
        $key = $interkassa->test_key;
    else
        $key = $interkassa->secret_key;

    $ik_sign = $response['ik_sign'];
    $sign = interkassaPaysys::ikSignFormation($response, $key);

    if ($interkassa->checkIP() && $ik_sign == $sign && $response['ik_co_id'] == $interkassa->cashbox_id) {
        $order_id = intval($response['ik_pm_no']);
        $data_order = $LINK->getRow("SELECT * FROM addons_interkassa WHERE order_id = {$order_id}");

        $order_amount = to_float($data_order['amount']);
        $ik_amount = to_float($response["ik_am"]);
        $user_id = intval($data_order['uid']);

        if (!empty($data_order) && $data_order['status'] != 'success' && $order_amount == $ik_amount) {

            switch ($response['ik_inv_st']) {
                case 'success':

                    updateOrderID($LINK, $order_id, 'status', 'success', 'addons_interkassa');
                    updateOrderID($LINK, $order_id, 'description', $response['ik_desc'], 'addons_interkassa');
                    updateOrderID($LINK, $order_id, 'transaction_id', $response['ik_trn_id'], 'addons_interkassa');

                    $LINK->query("INSERT INTO `addons_pay_api`
                    (`transaction_id`, `misc_id`, `user_ref`, `amount`, `creation_time`, `status`, `comment`)
                    VALUES ('{$response['ik_trn_id']}' , 'order_id:{$order_id}' , {$user_id} , '{$order_amount}' , NOW() , 0 , 'payment from Interkassa')");


                    sleep(1);
                    # вызываем команду после пополнения счёта
                    //$execCommand = 'cd /var/www/mikbill/admin; php ./index.php do_api_terminal_payments';
                    $execCommand = 'cd ..; cd admin; php ./index.php do_api_terminal_payments';
                    exec($execCommand);

                    // запасной вариант зачисления
                    //  addUserBalance($user['uid'], $order_amount, $LINK, $systemOptions, 0);
                    break;
                case 'fail':
                case 'canceled':

                    updateOrderID($LINK, $order_id, 'status', $response['ik_inv_st'], 'addons_interkassa');
                    updateOrderID($LINK, $order_id, 'description', $response['ik_desc'], 'addons_interkassa');

                    break;
                default:

                    updateOrderID($LINK, $order_id, 'status', $response['ik_inv_st'], 'addons_interkassa');
                    $LINK->query("INSERT INTO `addons_pay_api`
                    (`transaction_id`, `misc_id`, `user_ref`, `amount`, `creation_time`, `status`, `comment`)
                    VALUES ('{$response['ik_trn_id']}' , 'order_id:{$order_id}' , '' , {$user_id} , '{$order_amount}' , NOW() , 5 , 'payment from Interkassa')");
                    break;
            }
        }
    }

    exit;
}

if(isset($_REQUEST['api'])){
    header("Pragma: no-cache");
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
    header("Content-type: text/plain");
    $request = $_POST;

    $data_user = getUser($LINK, $user['uid']);

    if(empty($data_user)){
        $response = array(
            'status' => 'error',
            'msg' => 'User not defined'
        );

        $response = array_merge($response, array('$data_user' => $data_user));

        header('Content-type: aplication/json; charset=UTF-8');
        echo json_encode($response);
        exit;
    }

    $order_id = intval($_POST['ik_pm_no']);
    $amount = to_float($_POST['ik_am']);
    updateOrderID($LINK, $order_id, 'uid', $user['uid'], 'addons_interkassa');
    updateOrderID($LINK, $order_id, 'amount', $amount, 'addons_interkassa');

    echo $interkassa->selectPaySys($_POST);
    exit;
}

$order_id = startTransaction($LINK, $user, $amount, 'addons_interkassa', 'order_date');

updateOrderID($LINK, $order_id, 'currency', $interkassa->currency, 'addons_interkassa');

$formPay = $interkassa->getDataForm($order_id, $amount);

/**
 * К О Н С Т Р У К Т О Р   Ф О Р М Ы
 */

# Название ПС
$form->setLabelForm('Interkassa');

# POST form
$form->setMethodForm('POST');

# Заполняем action URL для формы
$action_url = 'javascript:selpayIK.selPaysys()';
$form->setUrlForm($action_url);

# заполняем форму полями
$form->addFieldForm($form->_h('Информация по платежу:'));
$form->addFieldForm($form->_hr());

if($user['uid'])
    $form->addFieldForm($form->_group($form->_inputLabel('uid', $user['uid'], 'UID:')));
else
    $form->addFieldForm($form->_group($form->_inputLabel('uid', $user['uid'], 'UID:', false)));

foreach ($formPay as $field => $value) {
    if($field == 'ik_am'){
        $form->addFieldForm($form->_group($form->_inputLabel('ik_am', $amount , T_('Cумма:'), false)));
    } else {
        $form->addFieldForm($form->_input($field, $value));
    }
}

$form->addFieldForm($form->_group($form->_inputLabel(false, $formPay['ik_cur'])));
$form->addFieldForm($form->_hr());

$form->addFieldForm($form->_group($form->_button()));

$form->addScriptForm('<link href="data/template/interkassa/interkassa.css" rel="stylesheet"/>');
$form->addScriptForm('<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>');
$form->addScriptForm('<script>
        var interkassa_lang = [];
        var interkassa_conf = [];
        interkassa_lang.error_selected_currency = "'. T_('Вы не выбрали валюту (Not selected currency)') . '";
        interkassa_lang.something_wrong = "'. T_('Что то пошло не так (Something wrong)') . '";
        interkassa_lang.enter_amount = "'. T_('Введите сумму пополнения (Enter the amount of the replenishment)') . '";
        interkassa_conf.url_action = "' . interkassaPaysys::url_action . '";
        interkassa_conf.url_api = window.location.origin + "/interkassa.php?api";
    </script>');
$form->addScriptForm('<script src="data/template/interkassa/interkassa.js"></script>');

if($interkassa->api_enable){
    require_once dirname(dirname(dirname(__DIR__))) . '/data/lib/TemplateClass.php';

    $tpl = new TemplateClass(dirname(dirname(dirname(__DIR__))) . '/data/template/interkassa');

    $tpl->set('payment_systems', $interkassa->getIkPaymentSystems());

    $tpl->set('lang_select_payment_method', T_('Выберите удобный способ оплаты'));
    $tpl->set('lang_select_currency', T_('Укажите валюту'));
    $tpl->set('lang_press_pay', T_('Нажмите &laquo;Оплатить&raquo;'));
    $tpl->set('lang_pay_through', T_('Оплатить через'));


    $form->addScriptForm( $tpl->out('interkassa_ps') );
}
