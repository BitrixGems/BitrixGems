<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ivariable
 * Date: 20.02.11
 * Time: 13:49
 * To change this template use File | Settings | File Templates.
 */
IncludeModuleLangFile(__FILE__);
if( empty( $aOptions ) ){
	return CAdminMessage::ShowMessage(array( 'TYPE'=>'ERROR','MESSAGE' => GetMessage('ERROR_PARAMS'), 'HTML' => true ));
}
?>
<form method="post" action="">
	<?=bitrix_sessid_post()?>

	<?php
		$sTableID = $this->getCode();
		$lAdmin = new CAdminList($sTableID);

		$aHeaders = array(
			array(
				'id' 		=> 'name',
				'content' 	=> GetMessage('NAME'),
				'default'	=> true,
			),
			array(
				'id' 		=> 'value',
				'content' 	=> GetMessage('VALUE'),
				'default'	=> true,
			),
		);

		$lAdmin->AddHeaders($aHeaders);
		foreach( $aOptions as $sKey => $aDescription ){
			$row =& $lAdmin->AddRow($sKey, $aDescription);
			$row->AddViewField("name", $aDescription['name'] );
			$mValue = $aDescription['value'];
			//if( !is_array( $aDescription['value'] ) ) $aDescription['value'] = array( $aDescription['value'] );
			$sResult = '';
			$Name = $this->getCode().'['.$sKey.']'.(isset( $aDescription['multiple'] )?'[]':'');

			switch( $aDescription['type'] ){

				case 'select|usergroup':
					$sResult = BitrixGemsHelper::GetUserGroupSelect( $Name, $mValue, $aDescription['multiple'] );
					break;

				case 'select':
					if( $aDescription['multiple'] ){
						$sResult .= '<select name="'.$Name.'" multiple="multiple">';
					}else{
						$sResult .= '<select name="'.$Name.'">';
						if( !is_array( $mValue ) ) $mValue = array($mValue);
					}
					if( !empty( $aDescription['options'] ) ){
						foreach( $aDescription['options'] as $sKey => $sOption ){
							$sResult .= '<option value="'.$sKey.'" '.(( in_array( $sKey, $mValue ) )?'selected="selected"':'').' >'.$sOption.'</option>';
						}
					}
					$sResult .= '</select>';
					break;

				case "radio":
					if( !empty( $aDescription['options'] ) ){
						foreach( $aDescription['options'] as $sKey=>$sOption ){
							if( $aDescription['multiple'] ){
								$sResult.='<input type="radio" name="'.$Name.'" value="'.htmlspecialchars( $sKey ).'"'.((in_array( $sKey, $mValue ))?' checked="checked"':'').'/>'.$sOption.'<br />';
							}else{
								$sResult.='<input type="radio" name="'.$Name.'" value="'.htmlspecialchars( $sKey ).'"'.(( $sKey == $mValue )?' checked="checked"':'').'/>'.$sOption.'<br />';
							}
						}
					}else{
						$sResult.='<input type="checkbox" name="'.$Name.'" value="'.htmlspecialchars( $mValue ).'"/>';
					}
					break;

				case "checkbox":
					if( !empty( $aDescription['options'] ) ){
						foreach( $aDescription['options'] as $sKey=>$sOption ){
							if( $aDescription['multiple'] ){
								$sResult.='<input type="checkbox" name="'.$Name.'" value="'.htmlspecialchars( $sKey ).'"'.((in_array( $sKey, $mValue ))?' checked="checked"':'').'/>'.$sOption.'<br />';
							}else{
								$sResult.='<input type="checkbox" name="'.$Name.'" value="'.htmlspecialchars( $sKey ).'"'.(( $sKey == $mValue )?' checked="checked"':'').'/>'.$sOption.'<br />';
							}
						}
					}else{
						$sResult.='<input type="checkbox" name="'.$Name.'" value="'.htmlspecialchars( $mValue ).'"/>';
					}
					break;

				case 'textarea':
					if( !$aDescription['multiple'] ){
						if( !is_array( $mValue ) ) $mValue = array($mValue);
					}
					foreach( $mValue as $sValue ){
						$sResult.='<textarea name="'.$Name.'" cols="60" rows="6">'.htmlspecialchars( $sValue ).'</textarea><br />';
					}
					break;

				case "text":
				default:
					if( !$aDescription['multiple'] ){
						 if( !is_array( $mValue ) ) $mValue = array($mValue);
					}
					foreach( $mValue as $sValue ){
						$sResult.='<input type="text" name="'.$Name.'" value="'.htmlspecialchars( $sValue ).'"/> <br />';
					}
					break;
			}
			$row->AddViewField("value", $sResult );
		}

		$lAdmin->AddFooter(
			array(
				array("title"=>"", "value"=>'<input type="submit" value="'.GetMessage('SAVE').'" />'),
			)
		);

		$lAdmin->CheckListMode();
		$lAdmin->DisplayList();


	?>
</form>
