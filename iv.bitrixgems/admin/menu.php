<?
if( !CModule::IncludeModule( 'iv.bitrixgems' ) ) return array();// Фикс страннобага битрикса, возникающего при удалении модуля.
IncludeModuleLangFile(__FILE__);
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/iv.bitrixgems/admin/menu.php');

if($APPLICATION->GetGroupRight("iv.bitrixgems")!="D")
{
	$aMenu = array(
		"parent_menu" => "global_menu_services",
		"section" => "bitrixGems",
		"sort" => 200,
		"text" => "BitrixGems",//GetMessage("mnu_bitrixGems"),
		"title" => GetMessage("mnu_bitrixGems_title"),
		"url" => "bitrixgems_manager.php",
		"icon" => "bitrixGems_small_icon",
		"page_icon" => "bitrixGems_big_icon",
		"items_id" => "menu_bitrixGems",
		"items" => array()
	);
	$aInstallMenu = array(
		"text" => GetMessage("mnu_ivbg_manager_title"),
		"url" => "bitrixgems_manager.php",
		"more_url" => Array(),
		"icon" => "bitrixGems_small_icon",
		"title" => GetMessage("mnu_ivbg_manager_title"),
		"items_id" => "menu_bitrixGems_general",
		"items" => array(
			array(
				"text" => GetMessage("mnu_ivbg_manager_catalog_title"),
				"url" => "bitrixgems_manager.php",
				"more_url" => Array(),
				"title" => GetMessage("mnu_ivbg_manager_catalog_title"),
			),
			array(
				"text" => GetMessage("mnu_ivbg_manager_install_title"),
				"url" => "bitrixgems_manager_installed.php",
				"more_url" => Array(),
				"title" => GetMessage("mnu_ivbg_manager_install_title"),
			),
			array(
				"text" => GetMessage("mnu_ivbg_manager_local_title"),
				"url" => "bitrixgems_manager_local.php",
				"more_url" => Array(),
				"title" => GetMessage("mnu_ivbg_manager_local_title"),
			),
		)
	);
	if( $APPLICATION->GetGroupRight("iv.bitrixgems") == 'W' ) $aMenu['items'][] = $aInstallMenu;
	$oGemManager = BitrixGems::getInstance();
	if( $oGemManager ){
		$aGems = $oGemManager->getInstalledGems( $bReturnGems = true );
		if( !empty( $aGems ) ){
			$aGemsPages = array();
			global $USER;
			foreach( $aGems as $oGem ){
				if( $oGem->needAdminPage() && ( $USER->IsAdmin() || $oGemManager->userHasAccess2Gem( $USER->GetUserGroupArray(), $oGem->getCode() ) ) ){
					$sName = $oGem->getName();
					$aGemsPages[] = array(
						"text" => $sName,
						"url" => "bitrixgems_simpleresponder.php?gem=".$oGem->getCode(),
						"more_url" => Array(),
						"title" => $sName,
					);
				};
			}
			if( !empty( $aGemsPages ) ){
				$aMenu['items'][] = array(
					"text" => "Gems",//GetMessage("mnu_bitrixGems_all"),
					"url" => "bitrixgems_simpleresponder.php",
					"items_id" => "menu_bitrixGems_Gems",
					"more_url" => Array(),
					"icon" => "bitrixGems_small_icon",
					"title" => "Gems",//GetMessage("mnu_bitrixGems_all"),
					'items' => $aGemsPages,
				);
			}
		};
		if( $APPLICATION->GetGroupRight("iv.bitrixgems") == 'W' ) $aMenu['items'][] = array(
			"text" => "Gems Feedback",//GetMessage("mnu_bitrixGems_all"),
			"url" => "bitrixgems_manager.php?feedback",
			"icon" => "subscribe_menu_icon",
			"more_url" => Array(),
			"title" => "Gems Feedback",//GetMessage("mnu_bitrixGems_all"),
		);
	}
	return $aMenu;
}
return false;
?>
