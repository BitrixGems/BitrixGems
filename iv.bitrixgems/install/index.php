<?
/**
 * Костылики для битрикса :)
 *
 * @author		Vladimir Savenkov <me@ivariable.ru>
 *
 */
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/iv.bitrixgems/install/index.php'); //ч0ртов БУГ не понимает симлинки
if(class_exists("iv_bitrixgems")) return;

class iv_bitrixgems extends CModule{

	var $MODULE_ID = "iv.bitrixgems";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;

	var $errors;

	function iv_bitrixgems(){
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion)){
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}else{
			$this->MODULE_VERSION = '0.1';
			$this->MODULE_VERSION_DATE = '1.1.2011';
		}

		$this->MODULE_NAME = 'BitrixGems';
		$this->MODULE_DESCRIPTION = GetMessage('IVBG_MODULES_DESCRIPTION');
		
		$this->PARTNER_NAME = 'iVariable';
		$this->PARTNER_URI = "http://ivariable.ru";
	}

	function InstallFiles(){
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/images/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/".$this->MODULE_ID."/", true, true);
		return true;
	}

	function UnInstallFiles(){
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/images/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/".$this->MODULE_ID."/", true, true);
		return true;
	}

	function InstallDB(){
		
		global $DB;
		
		$DB->Query( 'DROP TABLE IF EXISTS `bg_bitrixgems_custom_data`' );		
		$sCreateTableSQL = 'CREATE TABLE `bg_bitrixgems_custom_data` (
			`gem` VARCHAR( 100 ) NOT NULL ,
			`id` VARCHAR( 100 ) NOT NULL ,
			`data` TEXT NOT NULL ,
			PRIMARY KEY (  `gem` ,  `id` )
		) ENGINE = INNODB CHARACTER SET utf8 COLLATE utf8_unicode_ci;';
		/*if( strtoupper(LANG_CHARSET) == 'UTF-8' ){
			$sCreateTable .= 
		}else{
			
		}*/
		
		$DB->Query( $sCreateTableSQL );
		
	}
	
	function UnInstallDB(){
		global $DB;
		$DB->Query( 'DROP TABLE IF EXISTS `bg_bitrixgems_custom_data`' );
	}
	

	function DoInstall(){
		global $DB, $DOCUMENT_ROOT, $APPLICATION;
		$this->InstallFiles();
		$this->InstallDB();
		RegisterModule($this->MODULE_ID);
		RegisterModuleDependences( 'main', 'OnPageStart', $this->MODULE_ID );
	}

	function UnInstallGems(){
		return true;
	}

	function DoUninstall(){
		global $DB, $DOCUMENT_ROOT, $APPLICATION;
		/**
		 * СДЕЛАТЬ uninstall установленных gems'ов!
		 */
		$this->UnInstallGems();
		$this->UnInstallDB();
		$this->UnInstallFiles();
		COption::RemoveOption($this->MODULE_ID);
		UnRegisterModule($this->MODULE_ID);
	}
}
?>