<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Система электронных заявок");

if (SITE_TEMPLATE_ID == "bitrix24"):
	$html = '<div class="sidebar-buttons"><a href="#SITE_DIR#services/requests/my.php" class="sidebar-button">
			<span class="sidebar-button-top"><span class="corner left"></span><span class="corner right"></span></span>
			<span class="sidebar-button-content"><span class="sidebar-button-content-inner"><i class="sidebar-button-create"></i><b>Мои заявки</b></span></span>
			<span class="sidebar-button-bottom"><span class="corner left"></span><span class="corner right"></span></span></a></div>';
	$APPLICATION->AddViewContent("sidebar", $html);
endif?>
<p>Для оформления заявки на услугу выберите вид заявки, а затем заполните специальную форму.</p>
<table cellspacing="0" cellpadding="3" border="0" width="100%">
	<tbody>
		<tr><td colspan="6"><b>Запрос материалов и услуг</b>
		<br />

		<br />
		</td></tr>

		<tr><td align="center"><a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=VISITOR_ACCESS_#SITE_ID#"><img hspace="5" height="70" border="0" width="70" vspace="5" title="Заказ пропусков" alt="Заказ пропусков" src="#SITE_DIR#images/ru/requests/card.png" /></a>
		<br />

		<br />
		  <a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=VISITOR_ACCESS_#SITE_ID#">Пропуск
		<br />
		для посетителя</a></td>
		<td align="center"><a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=COURIER_DELIVERY_#SITE_ID#"><img hspace="5" height="70" border="0" width="70" vspace="5" title="Курьерская доставка" alt="Курьерская доставка" src="#SITE_DIR#images/ru/requests/package.jpg" /></a>
		<br />

		<br />
		<a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=COURIER_DELIVERY_#SITE_ID#">Курьерская
		<br />
		доставка </a></td><td align="center"><a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=BUSINESS_CARD_#SITE_ID#"><img hspace="5" height="70" border="0" width="70" vspace="5" title="Заказ визиток" alt="Заказ визиток" src="#SITE_DIR#images/ru/requests/viscard.png" /></a>
		<br />

		<br />
		<a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=BUSINESS_CARD_#SITE_ID#">Визитные
		<br />
		карточки</a></td><td align="center"><a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=OFFICE_SUPPLIES_#SITE_ID#"><img hspace="5" height="70" border="0" width="70" vspace="5" title="Заказ канцелярских товаров" alt="Заказ канцелярских товаров" src="#SITE_DIR#images/ru/requests/kanstov.jpg" /></a>
		<br />

		<br />
		<a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=OFFICE_SUPPLIES_#SITE_ID#">Канцелярские
		<br />
		товары</a></td><td align="center"><a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=CONSUMABLES_#SITE_ID#"><img hspace="5" height="70" border="0" width="70" vspace="5" title="Заказ расходных материалов" alt="Заказ расходных материалов" src="#SITE_DIR#images/ru/requests/printer.jpg" /></a>
		<br />

		<br />
		<a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=CONSUMABLES_#SITE_ID#">Расходные
		<br />
		материалы</a> </td><td align="center">
		<br />
		</td></tr>

		<tr><td colspan="6">
		<br />

		<br />
		</td></tr>

		<tr><td colspan="6"><b>Устранение неполадок</b>
		<br />

		<br />
		</td></tr>

		<tr><td align="center"><a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=IT_TROUBLESHOOTING_#SITE_ID#"><img hspace="5" height="70" border="0" width="70" vspace="5" title="Вопросы по оборудованию и коммуникациям" alt="Вопросы по оборудованию и коммуникациям" src="#SITE_DIR#images/ru/requests/computer.jpg" /></a> 
		<br />

		<br />
		<a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=IT_TROUBLESHOOTING_#SITE_ID#">Компьютеры,
		<br />
		оргтехника, сети</a> </td><td align="center"><a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=ADM_TROUBLESHOOTING_#SITE_ID#"><img hspace="5" height="70" border="0" width="70" vspace="5" title="Хозяйственная служба" alt="Хозяйственная служба" src="#SITE_DIR#images/ru/requests/tool.jpg" /></a>
		<br />

		<br />
		<a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=ADM_TROUBLESHOOTING_#SITE_ID#">Хозяйственная
		<br />
		служба</a> </td><td align="center">
		<br />
		</td><td align="center">
		<br />
		</td><td></td><td></td></tr>

		<tr><td colspan="6">
		<br />

		<br />
		</td></tr>

		<tr><td colspan="6"><b>Для руководителей</b>
		<br />

		<br />
		</td></tr>

		<tr><td align="center"><a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=DRIVER_SERVICES_#SITE_ID#"><img hspace="5" height="70" border="0" width="70" vspace="5" title="Услуги водителя " alt="Услуги водителя " src="#SITE_DIR#images/ru/requests/car_driver.jpg" /></a>
		<br />

		<br />
		<a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=DRIVER_SERVICES_#SITE_ID#">Услуги
		<br />
		водителя</a>
		<br />
		</td><td align="center">
		<p align="center"><a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=HR_REQUEST_#SITE_ID#"><img hspace="5" height="70" border="0" width="70" vspace="5" title="Подбор персонала " alt="Подбор персонала " src="#SITE_DIR#images/ru/requests/person.jpg" /></a>
		<br />

		<br />
		<a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=HR_REQUEST_#SITE_ID#">Подбор
		<br />
		персонала</a> </p>
		</td><td align="center"><a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=WORK_SITE_#SITE_ID#"><img hspace="5" height="70" border="0" width="70" vspace="5" title="Организация рабочего места" alt="Организация рабочего места" src="#SITE_DIR#images/ru/requests/office.jpg" /></a>
		<br />

		<br />
		<a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=WORK_SITE_#SITE_ID#">Организация
		<br />
		рабочего места</a></td><td></td><td></td><td></td></tr>
	</tbody>
</table>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>