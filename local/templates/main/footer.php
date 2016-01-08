<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<!-- start big footer -->
</div>
</div>
<div class="big-footer">
    <div class="top-shadow-footer"><!--  --></div>
    <div class="containit">

        <div class="full-width clearfix">
            <div class="one-fourth panel">
                <div class="nopad">
                    <h4>Lorem Ipsum</h4>
                    <ul>
                        <li><a href="#">Nulla vel lorem</a></li>
                        <li><a href="#">Porttitor orci vulputate</a></li>

                        <li><a href="#">Placerat mollis</a></li>
                        <li><a href="#">Suscipit risus felis</a></li>
                        <li class="last"><a href="#">Nullam ligula felis</a></li>
                    </ul>
                </div>
            </div>
            <div class="one-fourth panel border-vert-left">

                <div class="padleft">
                    <h4>Lorem Ipsum</h4>
                    <ul>
                        <li><a href="#">Nulla vel lorem</a></li>
                        <li><a href="#">Porttitor orci vulputate</a></li>
                        <li><a href="#">Placerat mollis</a></li>
                        <li><a href="#">Suscipit risus felis</a></li>

                        <li class="last"><a href="#">Nullam ligula felis</a></li>
                    </ul>
                </div>
            </div>
            <div class="one-fourth panel border-vert-left">
                <div class="padleft">
                    <h4>Contact</h4>

                    <p>Curabitur placerat, urna eu fringilla placerat, urna eu venenatis</p>

                    <b class="big">
                        <? $APPLICATION->IncludeComponent("bitrix:main.include", "", Array("COMPONENT_TEMPLATE" => ".default", "AREA_FILE_SHOW" => "file", "AREA_FILE_SUFFIX" => "inc", "EDIT_TEMPLATE" => "", "PATH" => SITE_TEMPLATE_PATH . "/include_areas/phone.php")

                        ); ?>
                    </b><br/>
                    <b class="big">
                        <? $APPLICATION->IncludeComponent("bitrix:main.include", "", Array("COMPONENT_TEMPLATE" => ".default", "AREA_FILE_SHOW" => "file", "AREA_FILE_SUFFIX" => "inc", "EDIT_TEMPLATE" => "", "PATH" => SITE_TEMPLATE_PATH . "/include_areas/fax.php")

                        ); ?>
                    </b><br/>

                    <a href="mailto:<? $APPLICATION->IncludeComponent("bitrix:main.include", "", Array("COMPONENT_TEMPLATE" => ".default", "AREA_FILE_SHOW" => "file", "AREA_FILE_SUFFIX" => "inc", "EDIT_TEMPLATE" => "", "PATH" => SITE_TEMPLATE_PATH . "/include_areas/email.php")); ?>">
                        <? $APPLICATION->IncludeComponent("bitrix:main.include", "", Array("COMPONENT_TEMPLATE" => ".default", "AREA_FILE_SHOW" => "file", "AREA_FILE_SUFFIX" => "inc", "EDIT_TEMPLATE" => "", "PATH" => SITE_TEMPLATE_PATH . "/include_areas/email.php")); ?>
                    </a><br/>
                </div>
            </div>
            <div class="one-fourth-last panel border-vert-left newsletter">
                <div class="padleft">

                    <h4>Join Our<br/> Newsletter</h4>

                    <p>Curabitur placerat, urna eu fringilla venenatis, orci mi tincidunt nulla, vitae iaculis
                        augue.</p>
                    <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input name="" class="field"/></td>
                            <td><input type="image" name="go"
                                       src="<?= SITE_TEMPLATE_PATH; ?>/_include/images/newsletter-input-button.png"
                                       alt="Go" class="form-imagebutton"/></td>
                        </tr>

                    </table>
                    <span class="small">Lorem ipsum <a href="#">dorem mors</a>.</span>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- end big footer -->

<!-- start small footer -->
<div class="small-footer">
    <div class="containit">

        <div class="copy">Copyright &copy; <?= date("Y"); ?></div>
        <div class="social">

            <ul>
                <li><a href="#"><img src="<?= SITE_TEMPLATE_PATH; ?>/_include/images/icons/icon-twitter.png" width="26"
                                     height="27" alt="" class="vm"/>Twitter</a></li>
                <li><a href="#"><img src="<?= SITE_TEMPLATE_PATH; ?>/_include/images/icons/icon-facebook.png" width="26"
                                     height="25" alt="" class="vm"/>Facebook</a></li>
                <li><a href="#"><img src="<?= SITE_TEMPLATE_PATH; ?>/_include/images/icons/icon-linkedin.png" width="26"
                                     height="26" alt="" class="vm"/>LinkedIn</a></li>
                <li><a href="#"><img src="<?= SITE_TEMPLATE_PATH; ?>/_include/images/icons/icon-rss.png" width="26"
                                     height="25" alt="" class="vm"/>Blog RSS</a></li>
            </ul>
        </div>

        <div class="clear"></div>
    </div>
</div>
<!-- end start small footer -->
<script type="text/javascript"> Cufon.now(); </script>

</body>
</html>