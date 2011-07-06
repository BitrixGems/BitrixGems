<?php
$sDir = dirname(__FILE__);
require_once( $sDir.'/classes/general/BitrixGems.class.php' );
require_once( $sDir.'/classes/general/BaseBitrixGem.class.php' );
require_once( $sDir.'/classes/general/BaseComponentGem.class.php' );
if( defined('ADMIN_SECTION') ){
	global $APPLICATION;

	$sStyle = '
	<style type="text/css">
		#bitrixGems_small_icon {
			background-image: url("/bitrix/images/iv.bitrixgems/small/ruby_gear.png");
		}
		#bitrixGems_big_icon {
			background-image: url("/bitrix/images/iv.bitrixgems/ruby_gear.png");
		}
	</style>
	';

	$APPLICATION->AddHeadString( $sStyle );
}
$oBitrixGemsManager = BitrixGems::getInstance();
?>