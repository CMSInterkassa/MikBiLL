# MikBiLL

##### Тестировался модуль на MikBiLL 2.13.03

#### Установка
Создать таблицу в БД биллинга
```
 CREATE TABLE `addons_interkassa` (   
    `order_id` int(32) unsigned NOT NULL AUTO_INCREMENT,   
    `order_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `uid` bigint(16) NOT NULL,   
    `amount` double(14,2) DEFAULT NULL,   
    `currency` char(4)  DEFAULT NULL,   
    `description` char(128)  NOT NULL,   
    `status` char(20)  NOT NULL,
    `transaction_id` char(64)  NOT NULL,
    PRIMARY KEY (`order_id`),   
    KEY `date` (`order_date`),   
    KEY `uid` (`uid`) 
 ) ENGINE=InnoDB DEFAULT CHARSET=koi8r;
```

скопировать папку __interkassa__ (основной код модуля) в директорию
*/mikbill/stat/res/paysystems/*

скопировать содержимое папки __template__ в директорию
*/mikbill/stat/data/template/interkassa/*

скопировать файл __interkassa.php__ в директорию
*/mikbill/stat/*

Открыть файл шаблона меню (__menu.tpl__) */mikbill/stat/data/template/olson/menu.tpl*

и добавить ссылку на страницу пополнения счета:

```html
<li>
    <a href="interkassa.php?uid=<?php echo $this->val['user']['uid']?>">
        <?php echo T_('Пополнить счет через');?> Interkassa
    </a>
</li>
```            

Что бы изменения шаблона непропали после будущих обновлений системы - отредактированый файл поместить в другую директорию: 
*/mikbill/stat/data/template/olson/customtpls/*

Данные настроек кассы Интеркассы нужно вводить в файле конфигурации модуля
*/mikbill/stat/res/paysystems/interkassa/config.php*


Просмотр пополнений в админке по пути:

__*отчеты → pay API → pay API подробный отчет*__




##### Еще один способ добавления отображения Interkassa в списке способов пополнения счета(при обновлении системы может перестать работать)

В файле mikbill/stat/data/lib/CabinetClass.php

После кода:
```
if (isset($this->_attributesOut['user']['use_paysoft']) and $this->_attributesOut['user']['use_paysoft'] == '1') {
    $paymentType[26] = 'PaySoft';
}
```
Добавить
```
$paymentType[200] = 'Interkassa';
```


В файле mikbill/stat/data/template/olson/payment.tpl

После кода:
```
} else if ($('#money_option').val() == 26) {
    //PaySoft\
    $('#name_ps').html('<?php echo T_("Платежная система"); ?> PaySoft');
    $("#form_payment_ps").attr("action", "pay.php?system=paysoft");
    $("#summa").attr("name", "amount");

}
```
Добавить:
```
else if ($('#money_option').val() == 200) {
    $('#name_ps').html('<?php echo T_("Платежная система"); ?> Interkassa');
    $("#form_payment_ps").attr("action", "pay.php?system=interkassa");
    $("#summa").attr("name", "amount");
}
```


В файле mikbill/stat/pay.php

После кода:
```
 case 'paysoft':
            $systemOptions = billingInitSystemOptionsByKey($LINK, '%' . $systemName . '_%');
            if (isset($systemOptions[$systemName . '_on'])) {
                $file = 'index.php';
            }
            break;
```
Добавить:
```
case 'interkassa':
            $systemOptions = billingInitSystemOptionsByKey($LINK, '%' . $systemName . '_%');
                $file = 'index.php';
            break;
```