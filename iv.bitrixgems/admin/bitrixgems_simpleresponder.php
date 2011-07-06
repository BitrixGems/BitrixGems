<?php
require( $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php" );
require( $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iv.bitrixgems/classes/general/BitrixGemsHelper.class.php" );
IncludeModuleLangFile(__FILE__);
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/iv.bitrixgems/admin/bitrixgems_simpleresponder.php');
if( !isset( $_GET['gem'] ) ) {
	require( $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php" );
	$adminPage->ShowSectionIndex("menu_bitrixGems_Gems", "bitrixGems");
	require( $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php" );
	die();
}
$oGemManager = BitrixGems::getInstance();
if( !$oGemManager->GemInstalled( @$_GET['gem'] ) ) LocalRedirect( '/bitrix/admin/bitrixgems_manager.php' );
$oGem = $oGemManager->getLoadedGem( @$_GET['gem'] );
if( empty( $oGem ) ) LocalRedirect('/bitrix/admin/bitrixgems_manager.php');
global $USER;
if( ( !$oGemManager->userHasAccess2Gem( $USER->GetUserGroupArray(), $oGem->getCode() ) && !$USER->IsAdmin()) ) LocalRedirect('/bitrix/admin/bitrixgems_manager.php');

if( isset( $_GET['AJAXREQUEST'] ) ){
	try{
		$sResult = $oGem->processAjaxRequest( $_REQUEST );
	echo $sResult;
	}catch( Exception $e ){
		echo $e->GetMessage();
	}
	die();
}

if( $REQUEST_METHOD=="POST" && check_bitrix_sessid() ){
	try{
		$oGem->processAdminPage( $_REQUEST );
	}catch( Exception $e ){
		$oGem->addMessage( $e->getMessage(), 'ERROR' );
	}
}

try{
	$oGem->beforeShowAdminPage( $_REQUEST );
}catch( Exception $e ){
	$oGem->addMessage( $e->getMessage(), 'ERROR' );
}

require( $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php" );
?>
<h2><?=$oGem->getName()?></h2>
<?
$oGem->showMessages();
try{
	$oGem->showAdminPage();
}catch(Exception $e){
	$oGem->addMessage( $e->getMessage(), 'ERROR' );
	$oGem->showMessages();
}
?>
<?php
require( $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php" );
?>
