<?php
require( $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php" );
if( $APPLICATION->GetGroupRight("iv.bitrixgems") != 'W' ) LocalRedirect( '/bitrix/admin/' );
IncludeModuleLangFile(__FILE__);
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/iv.bitrixgems/admin/bitrixgems_manager.php');
$oGemManager = BitrixGems::getInstance();
if( $oGemManager == null ){
	require( $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php" );

	CAdminMessage::ShowMessage(array( 'TYPE'=>'ERROR','MESSAGE' => GetMessage('ERROR_INITIALIZING_MANAGER'), 'HTML' => true ));


	require( $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php" );
	die();

}

$sError = '';
$sMessage = '';

if( isset( $_GET['updatecatalog'] ) && ( BITRIX_GEMS_MODE == 'CATALOG' )){
	try{
		$oGemManager->getGemsCatalogUpdate();
		$_SESSION['GEMSMESSAGE'] = GetMessage( 'CATALOG_UPDATE_COMPLETE' );
		LocalRedirect( '/bitrix/admin/bitrixgems_manager.php' );
	}catch( Exception $e ){
		$sError = $e->getMessage();
	}
}

if( $REQUEST_METHOD=="POST" && check_bitrix_sessid() && isset( $_POST['Gem'] )){
	try{
		if( isset( $_POST['install'] ) || isset( $_POST['install_x'] )){
			$oGemManager->installGem( $_POST['Gem'] );
			$sMessage = GetMessage( 'GEM_INSTALL_SUCCESS', array( '#GEM#' => $_POST['Gem'] ) );
		}

		if( isset( $_POST['uninstall'] ) || isset( $_POST['uninstall_x'] )){
			$oGemManager->unInstallGem( $_POST['Gem'] );
			$sMessage = GetMessage( 'GEM_UNINSTALL_SUCCESS', array( '#GEM#' => $_POST['Gem'] ) );
		}

		if( isset( $_POST['update'] ) || isset( $_POST['update_x'] )){
			$oGemManager->updateGem( $_POST['Gem'] );
			$sMessage = GetMessage( 'GEM_UPDATE_SUCCESS', array( '#GEM#' => $_POST['Gem'] ) );
		}

		if( isset( $_POST['compress'] ) || isset( $_POST['compress_x'] )){
			$sMessage = $oGemManager->archiveGem( $_POST['Gem'] );
			$sMessage = GetMessage( 'GEM_COMPRESS_SUCCESS', array( '#GEM_LINK#' => str_replace( $_SERVER['DOCUMENT_ROOT'], '' ,dirname($sMessage) ).'/'.basename( $sMessage ), '#GEM_FILENAME#' => basename( $sMessage ) ) );
			
			unset( $_GET['mode'], $_REQUEST['mode'] );
		}

		if( isset( $_POST['upload'] ) || isset( $_POST['upload_x'] )){
			$sMessage = $oGemManager->uploadGemToCatalog( $_POST['Gem'], $aAdditionalInfo = $_POST );
			$sMessage = GetMessage( 'GEM_UPLOAD_SUCCESS', array( '#GEM#' => $_POST['Gem'] ) );
			unset( $_GET['mode'], $_REQUEST['mode'] );
		}

		if( isset( $_POST['like'] ) || isset( $_POST['like_x'] )){
			$bResult = $oGemManager->likeGem( $_POST['Gem'] );
			unset( $_GET['mode'], $_REQUEST['mode'] );
			if( $bResult ){
				$sMessage = GetMessage('GEM_LIKE_SEND');
			}else{
				$sError = GetMessage('GEM_LIKE_FAIL');
			}
		}
	}catch( Exception $e ){
		$sError = $e->getMessage();
	}
}

if( $REQUEST_METHOD=="POST" && check_bitrix_sessid() && isset( $_POST['feedback'] )){
	$_POST[ 'SENDER_INFO' ] =  array(
		'SERVER' => array(
			'HTTP_HOST' => $_SERVER['HTTP_HOST'],
			'HTTP_REFERER' => $_SERVER['HTTP_REFERER'],
			'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'],
			'SERVER_NAME' => $_SERVER['SERVER_NAME'],
			'SERVER_ADDR' => $_SERVER['SERVER_ADDR'],
		),
		'BITRIX' => array(
			'SERVER_NAME' 	=> COption::GetOptionString( 'main', 'server_name' ),
			'SITE_NAME' 	=> COption::GetOptionString( 'main', 'site_name' ),
		),
	);
	/**
	 * ��� ������ �� ������!
	 */
	mail(
		'gems@bitrix-modules.ru',
		'BITRIXGEMS. Feedback.',
		var_export( $_POST, true )
	);
	$sMessage = GetMessage('FEEDBACK_SEND');
}

$aLocalCatalog = array(
	'CATALOG' => array(
		'LOCAL' => array (
			'NAME' => GetMessage('CATALOG_NAME_LOCAL'),
			'ITEMS' => $oGemManager->getLocalGems(),
		),
	)
);

$aInstalledCatalog = array(
	'CATALOG' => array(
		'INSTALLED' => array (
			'NAME' => GetMessage('CATALOG_NAME_INSTALLED'),
			'ITEMS' => $oGemManager->getInstalledGems( $sMode = 'info' ),
		),
	)
);

$aRemoteCatalog = $oGemManager->getGemsFromCatalog();

switch( BITRIX_GEMS_MODE ){
	case 'LOCAL':
		$aCatalog = $aLocalCatalog;
		if( !empty( $aCatalog['CATALOG']['LOCAL']['ITEMS'] ) ){
			foreach( $aCatalog['CATALOG']['LOCAL']['ITEMS'] as &$aGem ){
				$bInRemoteCatalog = in_array( $aGem['GEM'], $aRemoteCatalog['ALL_GEMS']);
				if( $oGemManager->GemInstalled( $aGem['GEM'], true ) ){
					if( $bInRemoteCatalog ){
						$aGem['STATE'] = 'INSTALLED';

						$aGem['LIKES'] = $aRemoteCatalog['GEMS_CATALOG']['ITEMS'][$aGem['GEM']]['LIKES'];
						$aGem['DOWNLOADS'] = $aRemoteCatalog['GEMS_CATALOG']['ITEMS'][$aGem['GEM']]['DOWNLOADS'];
						$aGem['PICTURE'] = $aRemoteCatalog['GEMS_CATALOG']['ITEMS'][$aGem['GEM']]['PICTURE'];
						$aGem['VERSION_CATALOG'] = $aRemoteCatalog['GEMS_CATALOG']['ITEMS'][$aGem['GEM']]['VERSION'];
						if( $aGem['VERSION'] < $aRemoteCatalog['GEMS_CATALOG']['ITEMS'][$aGem['GEM']]['VERSION'] ) $aGem['STATE'] = 'INSTALLED_AVAIL_NEWER';
						if( $aGem['VERSION'] > $aRemoteCatalog['GEMS_CATALOG']['ITEMS'][$aGem['GEM']]['VERSION'] ) $aGem['STATE'] = 'INSTALLED_AVAIL_OLDER';
					}else{
						$aGem['STATE'] = 'INSTALLED_LOCAL';
					}
				}else{
					if( $bInRemoteCatalog ){
						$aGem['LIKES'] = $aRemoteCatalog['GEMS_CATALOG']['ITEMS'][$aGem['GEM']]['LIKES'];
						$aGem['DOWNLOADS'] = $aRemoteCatalog['GEMS_CATALOG']['ITEMS'][$aGem['GEM']]['DOWNLOADS'];
						$aGem['PICTURE'] = $aRemoteCatalog['GEMS_CATALOG']['ITEMS'][$aGem['GEM']]['PICTURE'];
						$aGem['VERSION_CATALOG'] = $aRemoteCatalog['GEMS_CATALOG']['ITEMS'][$aGem['GEM']]['VERSION'];
						$aGem['STATE'] = 'NOT_INSTALLED';
					}else{
						$aGem['STATE'] = 'LOCAL';
					}
				};

			}
		}
		break;

	case "INSTALLED":
		$aCatalog = $aInstalledCatalog;
		if( !empty( $aCatalog['CATALOG']['INSTALLED']['ITEMS'] ) ){
			foreach( $aCatalog['CATALOG']['INSTALLED']['ITEMS'] as &$aGem ){

				$bInRemoteCatalog = in_array( $aGem['GEM'], $aRemoteCatalog['ALL_GEMS']);

				if( $bInRemoteCatalog ){

					$aGem['LIKES'] = $aRemoteCatalog['GEMS_CATALOG']['ITEMS'][$aGem['GEM']]['LIKES'];
					$aGem['DOWNLOADS'] = $aRemoteCatalog['GEMS_CATALOG']['ITEMS'][$aGem['GEM']]['DOWNLOADS'];
					$aGem['PICTURE'] = $aRemoteCatalog['GEMS_CATALOG']['ITEMS'][$aGem['GEM']]['PICTURE'];

					$aGem['STATE'] = 'INSTALLED';
					$aGem['VERSION_CATALOG'] = $aRemoteCatalog['GEMS_CATALOG']['ITEMS'][$aGem['GEM']]['VERSION'];
					if( $aGem['VERSION'] < $aRemoteCatalog['GEMS_CATALOG']['ITEMS'][$aGem['GEM']]['VERSION'] ) $aGem['STATE'] = 'INSTALLED_AVAIL_NEWER';
					if( $aGem['VERSION'] > $aRemoteCatalog['GEMS_CATALOG']['ITEMS'][$aGem['GEM']]['VERSION'] ) $aGem['STATE'] = 'INSTALLED_AVAIL_OLDER';
				}else{
					$aGem['STATE'] = 'INSTALLED_LOCAL';
				}

			}
		}
		break;

	default:
	case 'CATALOG':
		$aCatalog = $aRemoteCatalog;
		if( !empty( $aCatalog['CATALOG'] ) ){
			foreach( $aCatalog['CATALOG'] as &$aCat ){

				if( !empty( $aCat['ITEMS'] ) ){
					foreach( $aCat['ITEMS'] as &$aGem ){
						if( $oGemManager->GemInstalled( $aGem['GEM'], true ) ){
							$aGem['STATE'] = 'INSTALLED';
							$aGem['VERSION_INSTALLED'] = $aInstalledCatalog['CATALOG']['INSTALLED']['ITEMS'][$aGem['GEM']]['VERSION'];
							if( $aGem['VERSION'] > $aInstalledCatalog['CATALOG']['INSTALLED']['ITEMS'][$aGem['GEM']]['VERSION'] ) $aGem['STATE'] = 'INSTALLED_AVAIL_NEWER';
							if( $aGem['VERSION'] < $aInstalledCatalog['CATALOG']['INSTALLED']['ITEMS'][$aGem['GEM']]['VERSION'] ) $aGem['STATE'] = 'INSTALLED_AVAIL_OLDER';
						}else{
							$aGem['STATE'] = 'NOT_INSTALLED';
						}
					}
				}

			}
		}else{
			$sErrorHTML = GetMessage('CATALOG_ERROR');
		}

		break;
}

$aTabs = array();
if( !empty( $aCatalog['CATALOG']) ){
	foreach( $aCatalog['CATALOG'] as $sKey => $aSection ){
		$aTabs[] = array(
			'DIV' 		=> $sKey,
			'TAB' 		=> $aSection['NAME'],
			'ICON' 		=> 'main_channel_edit',
			'TITLE' 	=> $aSection['NAME'],
		);
	}
}
$tabControl = new CAdminTabControl("tabControl", $aTabs , false);

$aTables = array();

foreach( $aTabs as $aTab ){

	$sTableID = "tbl_bitrixGems_".$aTab['DIV'];
	$oSort = new CAdminSorting($sTableID, "LIKES", "asc");
	$lAdmin = new CAdminList($sTableID, $oSort);

	$lAdmin->InitFilter(array('filter_by_gem'));

	$headers = array(
		array("id"=>"PICTURE", "content"=>GetMessage('TH_PICTURE'), "default"=>true),
		array("id"=>"NAME", "content"=>GetMessage('TH_NAME'), "sort"=>"s_NAME", "default"=>true),
		array("id"=>"TYPE", "content"=>GetMessage('TH_TYPE'), "sort"=>"s_TYPE", "default"=>true),
		array("id"=>"DESCRIPTION", "content"=>GetMessage('TH_DESCRIPTION'), "sort"=>"s_DESCRIPTION", "default"=>true),
		array("id"=>"REQUIREMENTS", "content"=>GetMessage('TH_REQUIREMENTS'), "sort"=>"s_REQUIREMENTS", "default"=>true),
		array("id"=>"AUTHOR", "content"=>GetMessage('TH_AUTHOR'), "sort"=>"s_AUTHOR", "default"=>true),
		array("id"=>"LIKES", "content"=>GetMessage('TH_LIKES'), "sort"=>"s_LIKES", "default"=>true),
		array("id"=>"DOWNLOADS", "content"=>GetMessage('TH_DOWNLOADS'), "sort"=>"s_DOWNLOADS", "default"=>true),
	);
	$lAdmin->AddHeaders($headers);

    $rsData = new CDBResult();

	/**
	 * ������. �� ������� ������� ���� �����. ���� ����� �������� ������ ��������. �� �� ��� �������.
	 */
	$aFilteredCat = array();
	if( $filter_by_gem ){
		foreach( $aCatalog['CATALOG'][ $aTab['DIV'] ]['ITEMS'] as $aGem ){
			if( stripos( $aGem['GEM'], $filter_by_gem ) !== false ){
				$aFilteredCat[ $aGem['GEM'] ] = $aGem;
			}
		}
	}else{
		$aFilteredCat = $aCatalog['CATALOG'][ $aTab['DIV'] ]['ITEMS'];
	}

	if( !empty($filter_by_type) && $filter_by_type != 'all'){
		foreach( $aFilteredCat as $sKey => $aGem ){
			if( $aGem['TYPE'] != $filter_by_type )unset( $aFilteredCat[ $sKey ] );
		}
	}

	/**
	 * ����������. ��. ������� ����. ���� ���� ����� �� �������� ������, � �� �����������. �� ��� � ����, ��� ��� ������ ��������� :)
	 */
	if( !empty( $aFilteredCat ) ){

		if( !function_exists( 'array_orderby' ) ){
			function array_orderby(){
				$args = func_get_args();
				$data = array_shift($args);
				foreach ($args as $n => $field) {
					if (is_string($field)) {
						$tmp = array();
						foreach ($data as $key => $row)
							$tmp[$key] = $row[$field];
						$args[$n] = $tmp;
						}
				}
				$args[] = &$data;
				call_user_func_array('array_multisort', $args);
				return array_pop($args);
			}
		}
		// ��� ����� � ����������� � ����...
		$mSortBy = SORT_ASC;
		if( strtolower($GLOBALS[$oSort->ord_name]) == "desc" ){
			$mSortBy = SORT_DESC;
		};
		$aFilteredCat = array_orderby( $aFilteredCat, substr($GLOBALS[$oSort->by_name],2), $mSortBy , 'GEM', SORT_ASC );
	}

    $rsData->InitFromArray( $aFilteredCat );

	$rsData = new CAdminResult($rsData, $sTableID);
	$rsData->NavStart();

	$lAdmin->NavText($rsData->GetNavPrint("BitrixGems"));

    while($arRes = $rsData->NavNext(true, "f_")){
		$row =& $lAdmin->AddRow($f_NAME, $arRes);
		$bCanBeInstalled = BitrixGems::checkRequirements( $arRes );

		$row->AddViewField("TYPE", GetMessage('GEM_TYPE_'.$f_TYPE) );

		if( empty( $f_PICTURE ) ){
			$f_PICTURE = '/bitrix/images/iv.bitrixgems/ruby.png';
		}
		$row->AddViewField("PICTURE", '<div style="text-align:center;"><img src="'.$f_PICTURE.'" alt="'.$f_NAME.'" title="'.$f_NAME.'"/></div>' );

		$row->AddViewField("DESCRIPTION", nl2br($f_DESCRIPTION) );
		
		$row->AddViewField(
			"REQUIREMENTS",
			'<font style="color:'.(( $bCanBeInstalled )?'green':'red').'">'.
			$f_REQUIREMENTS.
			GetMessage('TR_REQ_MODULE_VERSION').$f_REQUIRED_MIN_MODULE_VERSION.
			((!empty($f_REQUIRED_MODULES))?GetMessage('TR_REQ_MODULES').implode(', ', $f_REQUIRED_MODULES):'').
			((!empty($f_REQUIRED_GEMS))?GetMessage('TR_REQ_GEMS').implode(', ',$f_REQUIRED_GEMS):'').
			'</font>'
		);
		$row->AddViewField("VERSION",$f_VERSION);
		$row->AddViewField("LIKES",$f_LIKES);
		$row->AddViewField("DOWNLOADS",$f_DOWNLOADS);
		$row->AddViewField("AUTHOR",((!empty( $f_AUTHOR_LINK ))?'<a href="'.$f_AUTHOR_LINK.'">'.$f_AUTHOR.'</a>':$f_AUTHOR));
		$sAction = '&nbsp;';
		if( $oGemManager->isVotingAllowed( $f_GEM ) ){
			$sAction = '<input type="image" src="/bitrix/images/iv.bitrixgems/star.png" class="gem_action" name="like" title="'.GetMessage('LIKE').'">';
		}
		switch( $f_STATE ){

			case 'NOT_INSTALLED':
				if( $bCanBeInstalled ){
					$sAction .= '<input type="image" src="/bitrix/images/iv.bitrixgems/ruby_add.png" class="gem_action" name="install" title="'.GetMessage('ACT_INSTALL').'">';
				}
				break;

			case 'INSTALLED':
				$sAction .= '<input type="image" src="/bitrix/images/iv.bitrixgems/ruby_delete.png"  class="gem_action" name="uninstall" title="'.GetMessage('ACT_UNINSTALL').'">';
				break;

			case 'INSTALLED_AVAIL_NEWER':
				if( $bCanBeInstalled ){
					$sAction .= '<input type="image" src="/bitrix/images/iv.bitrixgems/ruby_put.png" class="gem_action" name="update" title="'.GetMessage('ACT_UPDATE').'">';
				}
				$sAction .='<input type="image" src="/bitrix/images/iv.bitrixgems/ruby_delete.png" class="gem_action" name="uninstall" title="'.GetMessage('ACT_UNINSTALL').'">';
				break;

			case 'INSTALLED_AVAIL_OLDER':
				$sAction .= '<input type="image" src="/bitrix/images/iv.bitrixgems/ruby_go.png"  rel="'.$f_GEM.'" class="gem_action" name="upload" title="'.GetMessage('ACT_UPLOAD').'"><input type="image" src="/bitrix/images/iv.bitrixgems/ruby_delete.png"  name="uninstall" title="'.GetMessage('ACT_UNINSTALL').'" class="gem_action">';
				break;

			case 'INSTALLED_LOCAL':
				$sAction .= '<input type="image" src="/bitrix/images/iv.bitrixgems/ruby_delete.png"  class="gem_action"  name="uninstall" title="'.GetMessage('ACT_UNINSTALL').'"><input type="image" src="/bitrix/images/iv.bitrixgems/ruby_get.png" class="gem_action" rel="'.$f_GEM.'"  name="upload" title="'.GetMessage('ACT_UPLOAD').'">';
				break;

			case 'LOCAL':
				if( $bCanBeInstalled ){
					$sAction .= '<input type="image" src="/bitrix/images/iv.bitrixgems/ruby_add.png"  class="gem_action" name="install" title="'.GetMessage('ACT_INSTALL').'">';
				}
				$sAction .= '<input type="image" src="/bitrix/images/iv.bitrixgems/ruby_get.png" class="gem_action" rel="'.$f_GEM.'"  name="upload" title="'.GetMessage('ACT_UPLOAD').'">';
				break;

		}
		if( BITRIX_GEMS_MODE != 'CATALOG' ){
			$sAction .= '<input type="image" src="/bitrix/images/iv.bitrixgems/compress.png"  class="gem_action" name="compress" title="'.GetMessage('ACT_COMPRESS').'">';
		}

		$row->AddViewField(
			"NAME",
			$f_NAME.
			GetMessage( 'TR_NAME', array( '#VERSION#' => $f_VERSION, '#DATE#'=> $f_DATE) ).
			((!empty( $f_VERSION_INSTALLED ))?GetMessage( 'TR_NAME_INSTALLED', array( '#VERSION#' => $f_VERSION_INSTALLED ) ):'').
			((!empty( $f_VERSION_CATALOG ))?GetMessage( 'TR_NAME_CATALOG', array( '#VERSION#' => $f_VERSION_CATALOG ) ):'').
			((BITRIX_GEMS_MODE == 'CATALOG')?GetMessage( 'TR_NAME_CATALOG_LINK', array( '#GEM#' => $f_GEM, '#NAME#' => $f_NAME ) ):'').
			'<div style="border-top: 1px solid black; margin-top:5px; padding-top: 5px;text-align:left;" class="gem_form"><form class="Gem" method="post" action="">'.bitrix_sessid_post().'<input type="hidden" name="Gem" value="'.$f_GEM.'" />'.$sAction.'</form></div>'

		);

		unset( $f_VERSION_INSTALLED, $f_VERSION_CATALOG );
    }
	$lAdmin->AddFooter(
		array(
			array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
			array("counter"=>true, "title"=>GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"),
		)
	);

	$aContext = array();
	if( BITRIX_GEMS_MODE == 'CATALOG' ){
		$aContext[] = array(
			"TEXT" => GetMessage('ACT_UPDATE_CATALOG', array( '#DATE#' => date( 'd.m.Y H:i:s', $aRemoteCatalog['UPDATED'] ) ) ),
			"ICON" => "btn_new",
			"LINK" => 'bitrixgems_manager.php?updatecatalog',
		);
	}
	$aContext[] = Array(
		"ICON" 		=> "subscribe_menu_icon",
		"TEXT" 		=> GetMessage('FEEDBACK'),
		"LINK" 		=> "javascript:showFeedBack()",
		"TITLE" 	=> GetMessage('FEEDBACK'),
	);
	$lAdmin->AddAdminContextMenu($aContext);
	$lAdmin->CheckListMode();
	
	$aTables[ $aTab['DIV'] ] = $lAdmin;
}
require( $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php" );

if( !empty( $sErrorHTML ) ){
	CAdminMessage::ShowMessage(array( 'TYPE'=>'ERROR','MESSAGE' => $sErrorHTML, 'HTML' => true ));
}

if( !empty( $sError ) ){
	CAdminMessage::ShowMessage($sError);
}

if( !empty( $sMessage ) ){
	CAdminMessage::ShowMessage(array( 'TYPE'=>'OK','MESSAGE' => $sMessage, 'HTML' => true ));
}

if( isset( $_SESSION['GEMSMESSAGE'] ) && ( $_SESSION['GEMSMESSAGE'] != null )){
	CAdminMessage::ShowNote( $_SESSION['GEMSMESSAGE'] );
	unset( $_SESSION['GEMSMESSAGE'] );
}
?>
<script type="text/javascript">
	/*Dirty antivirushack: form_tbl_dump*/
	if( typeof jQuery == 'undefined' ) document.write('<?=GetMessage('JQUERY_WARNING')?>');
</script>
<div class="b-Gems">
<form name="form1" method="get" action="<?=$APPLICATION->GetCurPage()?>?">
<?
$sTableID = 'gems';
$oFilter = new CAdminFilter($sTableID."_filter", array(GetMessage('GEM'), GetMessage('TH_TYPE')));
$oFilter->Begin();?>
<tr>
	<td colspan="2"></td>
</tr>
<tr>
	<td><?=GetMessage('GEM')?></td>
	<td><input type="text" name="filter_by_gem" value="<?=htmlspecialcharsex($filter_by_gem)?>" /></td>
</tr>
<tr>
	<td><?=GetMessage('TH_TYPE')?></td>
	<td>
			<select name="filter_by_type">
				<option value="all" <?if($filter_by_type == 'all'):?> selected="selected"<?endif;?>><?=GetMessage('TYPE_ALL')?></option>
				<option value="functional"<?if($filter_by_type == 'functional'):?> selected="selected"<?endif;?>><?=GetMessage('GEM_TYPE_functional')?></option>
				<option value="component"<?if($filter_by_type == 'component'):?> selected="selected"<?endif;?>><?=GetMessage('GEM_TYPE_component')?></option>
				<option value="pack"<?if($filter_by_type == 'pack'):?> selected="selected"<?endif;?>><?=GetMessage('GEM_TYPE_pack')?></option>
			</select>
	</td>
</tr>
<?php
$oFilter->Buttons(array("table_id"=>$sTableID, "url"=>$APPLICATION->GetCurPage()."?lang=".LANGUAGE_ID));
$oFilter->End();
?>
</form>

<?php
$tabControl->Begin();
foreach( $aTabs as $aTab ){
    $tabControl->BeginNextTab();
	echo '<tr><td class="ignore_edit_table">';
	$aTables[ $aTab['DIV'] ]->DisplayList();
	echo '</td></tr>';
}
$tabControl->End();
?>
</div>

<?CUtil::InitJSCore(array('window'));?>
<script type="text/javascript">
if( typeof jQuery !== 'undefined' ){
	jQuery('table.edit-form').removeClass('edit-form');
	jQuery('table.edit-table').removeClass('edit-table');
	jQuery('.edit-tab').removeClass('edit-tab');
	$('.gem_action').not('input[name=upload]').live( 'click', function(){
		$('<form method="post" action=""></form>')
			.append($(this).parents('.gem_form').find('input'))
			.append( '<input type="hidden" name="'+$(this).attr('name')+'" />' )
			.appendTo('body')
			.submit();
		return false;
	} )
	$('form input[name=upload]').live( 'click', function(){
		var Dialog = new BX.CDialog({
			title: "<?=GetMessage('FU_TITLE')?>",
			head: '<?=GetMessage('FU_HEAD')?>',
			content: '<form rel="'+$(this).attr('rel')+'" method="POST" style="overflow:hidden;" action="" >\
				<textarea name="info" style="height: 178px; width: 474px;"></textarea>\
				<input type="hidden" name="upload" value="<?=GetMessage('FU_TITLE')?>">\
				<input type="hidden" name="Gem" value="'+$(this).attr('rel')+'"><?=bitrix_sessid_post()?>\
				</form>',
			icon: 'head-block',
			resizable: true,
			draggable: true,
			height: '338',
			width: '500',
			buttons: ['<input type="button" value="<?=GetMessage('FU_SEND')?>" onclick="$(\'form[rel='+$(this).attr('rel')+']\').submit()" />',BX.CDialog.btnCancel,]
		});
		Dialog.Show();
		return false;
	} );

	function showFeedBack(){
		var FeedBack = new BX.CDialog({
			title: "<?=GetMessage('FF_TITLE')?>",
			head: '<?=GetMessage('FF_HEAD')?>',
			content: '<form rel="_Gems_FEEDBACK" method="POST" style="overflow:hidden;" action="/bitrix/admin/bitrixgems_manager.php" >\
				Email: <br/><input type="text" name="mail" style="width: 474px;"><br /><?=GetMessage('FF_QUESTION')?>:\
				<textarea name="question" style="height: 128px; width: 474px;"></textarea>\
				<input type="hidden" name="feedback" value="������!"><?=bitrix_sessid_post()?>\
				</form>',
			icon: 'head-block',
			resizable: true,
			draggable: true,
			height: '338',
			width: '500',
			buttons: ['<input type="button" value="<?=GetMessage('FU_SEND')?>" onclick="$(\'form[rel=_Gems_FEEDBACK]\').submit()" />',BX.CDialog.btnCancel]
		});
		FeedBack.Show();
	}
	$('.bricskfeedback').live( 'click', function(){
		showFeedBack();
	})
	<?if( isset($_GET['feedback']) ):?>
	jQuery(function(){
		showFeedBack();
	})
	<?endif?>
	CloseWaitWindow()
}
</script>
<?php
require( $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php" );
?>

