<?php
header('Content-type: text/html; charset=UTF-8');

# сокращаем DS
define('DS', DIRECTORY_SEPARATOR);
define('BILL_SYSTEM_OPTIONS_TABLE', 'system_options');

# путь к платёжным системам
$pathToPS = '.' . DS . 'res' . DS . 'paysystems' . DS;
# вспомогательные ф-ии
include_once($pathToPS . 'helper' . DS . 'safemysql.class.php');
include_once($pathToPS . 'helper' . DS . 'functions.php');
include_once($pathToPS . 'helper' . DS . 'form.class.php');

# файл-конфиг соединения с БД
$configFilePath = './app/etc/config.xml';

# соединение с БД
$LINK = connectToDB($configFilePath);

# инициализируем класс формы.
$form = new formClass();

# получаем необходимые данные для оплаты
# данные абонента
$user['uid'] = isset($_REQUEST['uid'])? intval($_REQUEST['uid']) : 0;
# сумма платежа
$amount = round(getPostParam('amount', 100), 2);

include_once($pathToPS . 'interkassa' . DS . 'index.php');


echo $form->getForm();

exit;