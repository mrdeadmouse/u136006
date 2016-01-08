<?
$MESS["STATUS_NEW"] = "Новое";
$MESS["STATUS_RECEIVED"] = "Принято к рассмотрению";
$MESS["STATUS_DONE"] = "Выполнено";
$MESS["STATUS_REFUSE"] = "Отказано";
$MESS["STATUS_MESSAGE"] = "Здравствуйте, #RS_USER_NAME#!

Статус заявки №#RS_RESULT_ID# изменен на [#RS_STATUS_NAME#] (#RS_FORM_NAME#)

Просмотр заявки:
http://#SERVER_NAME#/services/requests/form_view.php?WEB_FORM_ID=#RS_FORM_ID#&RESULT_ID=#RS_RESULT_ID#

Все заявки:
http://#SERVER_NAME#/services/requests/my.php

#SERVER_NAME#
-------------------------------------------------------
Письмо сгенерировано автоматически.
";
?>