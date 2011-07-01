<?php
$module_id = "iv.bitrixgems";
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/include.php");
require( $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iv.bitrixgems/classes/general/BitrixGemsHelper.class.php" );
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/options.php");

$oGemManager = BitrixGems::getInstance();

$GEMS_RIGHT = $APPLICATION->GetGroupRight($module_id);

if($GEMS_RIGHT>="R"):

if($REQUEST_METHOD=="POST" && strlen($Update)>0 && check_bitrix_sessid()){
	foreach( $_POST['GEMS_ACCESS'] as $sGem => $aAllowedUG ){
		$oGemManager->setGemAccessRights( $sGem, $aAllowedUG );
	}
}

$aTabs = array(
	array("DIV" => "access", "TAB" => GetMessage("MAIN_TAB_ACCESS"), "ICON" => "form_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_ACCESS")),
	array("DIV" => "rights", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "form_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS"))
);

$tabControl = new CAdminTabControl("tabControl", $aTabs);

$tabControl->Begin();
?>
<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialchars($mid)?>&lang=<?=LANGUAGE_ID?>"><?=bitrix_sessid_post()?>
<?$tabControl->BeginNextTab();?>
<?
$aInstalledGems = $oGemManager->getInstalledGems( $bReturnGems = true );
?>
<tr><td>
	<table>
	<?foreach( $aInstalledGems as $sGem => $oGem ):?>
		<?
		if( $oGem->needAdminPage() === false ) continue;
		?>
		<tr>
			<td valign="top"><?=$sGem?></td>
			<td><?=BitrixGemsHelper::GetUserGroupSelect('GEMS_ACCESS['.$sGem.'][]',$oGemManager->getGemAccessRights($sGem), true)?></td>
		</tr>
	<?endforeach;?>
	</table>
</td></tr>
<?$tabControl->BeginNextTab();?>
<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>
<?$tabControl->Buttons();?>
<input <?if ($GEMS_RIGHT<"W") echo "disabled" ?> type="submit" name="Update" value="<?=GetMessage("SAVE")?>">
<input type="hidden" name="Update" value="Y">
<?$tabControl->End();?>
</form>
<?endif;?>