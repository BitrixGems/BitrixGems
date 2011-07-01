<?php
/**
 * Различные хелперы для админки
 * Просто либа.
 */
class BitrixGemsHelper{
	/**
	 * Дропдаун список инфоблоков.
	 * Просто обернул стандартную одноименную функцию
	 *
	 * Стандартная не подошла, потому что у нее стоит совершенно ненужное для админской функции
	 * кеширование выбранного инфоблока в статик переменную
	 * @param $IBLOCK_ID
	 * @param $strTypeName
	 * @param $strIBlockName
	 * @param $arFilter
	 */
	public static function GetIBlockDropDownList($IBLOCK_ID, $strTypeName, $strIBlockName, $arFilter = false){
		$html = '';
	
		$arTypes = false;
		$arIBlocks = false;
	
		if(!$arTypes)
		{
			$arTypes = array(''=>GetMessage("IBLOCK_CHOOSE_IBLOCK_TYPE"));
			$arIBlocks = array(''=>array(''=>GetMessage("IBLOCK_CHOOSE_IBLOCK")));
	
			$IBLOCK_TYPE = false;
	
			if(!is_array($arFilter))
				$arFilter = array();
			$arFilter["MIN_PERMISSION"] = "W";
	
			$rsIBlocks = CIBlock::GetList(array("IBLOCK_TYPE" => "ASC", "NAME" => "ASC"), $arFilter);
			while($arIBlock = $rsIBlocks->Fetch())
			{
				if($IBLOCK_ID == $arIBlock["ID"])
					$IBLOCK_TYPE = $arIBlock["IBLOCK_TYPE_ID"];
				if(!array_key_exists($arIBlock["IBLOCK_TYPE_ID"], $arTypes))
				{
					$arType = CIBlockType::GetByIDLang($arIBlock["IBLOCK_TYPE_ID"], LANG);
					$arTypes[$arType["~ID"]] = $arType["~NAME"]." [".$arType["~ID"]."]";
					$arIBlocks[$arType["~ID"]] = array(''=>GetMessage("IBLOCK_CHOOSE_IBLOCK"));
				}
				$arIBlocks[$arIBlock["IBLOCK_TYPE_ID"]][$arIBlock["ID"]] = $arIBlock["NAME"]." [".$arIBlock["ID"]."]";
			}
	
			$html .= '
			<script language="JavaScript">
			function OnTypeChanged(typeSelect, iblockSelectID)
			{
				var arIBlocks = '.CUtil::PhpToJSObject($arIBlocks).';
				var iblockSelect = document.getElementById(iblockSelectID);
				if(iblockSelect)
				{
					for(var i=iblockSelect.length-1; i >= 0; i--)
						iblockSelect.remove(i);
					var n = 0;
					for(var j in arIBlocks[typeSelect.value])
					{
						var newoption = new Option(arIBlocks[typeSelect.value][j], j, false, false);
						iblockSelect.options[n]=newoption;
						n++;
					}
				}
			}
			</script>
			';
		}
	
		$html .= '<select name="'.htmlspecialchars($strTypeName).'" id="'.htmlspecialchars($strTypeName).'" OnChange="OnTypeChanged(this, \''.CUtil::JSEscape($strIBlockName).'\')">'."\n";
		foreach($arTypes as $key => $value)
		{
			if($IBLOCK_TYPE === false)
				$IBLOCK_TYPE = $key;
			$html .= '<option value="'.htmlspecialchars($key).'"'.($IBLOCK_TYPE===$key? ' selected': '').'>'.htmlspecialchars($value).'</option>'."\n";
		}
		$html .= "</select>\n";
	
		$html .= "&nbsp;\n";
	
		$html .= '<select name="'.htmlspecialchars($strIBlockName).'" id="'.htmlspecialchars($strIBlockName).'">'."\n";
		foreach($arIBlocks[$IBLOCK_TYPE] as $key => $value)
		{
			$html .= '<option value="'.htmlspecialchars($key).'"'.($IBLOCK_ID==$key? ' selected': '').'>'.htmlspecialchars($value).'</option>'."\n";
		}
		$html .= "</select>\n";
	
		return $html;
	}

	/**
	 * Селектор юзергроупов
	 * @static
	 * @param  $sSelectName
	 * @param  $aSelectedValues
	 * @param  $bIsMultiple
	 * @return void
	 */
	public static function GetUserGroupSelect( $sSelectName, $aSelectedValues = array(), $bIsMultiple = true ){
		if( !is_array( $aSelectedValues ) ) $aSelectedValues = array( $aSelectedValues );
		static $aUserGroups = array();
		if( empty( $aUserGroups ) ){
			$oUserGroups = CGroup::GetDropDownList();
			while( $aUG = $oUserGroups->Fetch() ){
				$aUserGroups[ $aUG['REFERENCE_ID'] ] = $aUG['REFERENCE'];
			}
		}
		$sResult = '';
		if( $bIsMultiple ){
			$sResult .= '<select name="'.$sSelectName.'" multiple="multiple">';
		}else{
			$sResult .= '<select name="'.$sSelectName.'">';
		}
		if( !empty( $aUserGroups ) ){
			foreach( $aUserGroups as $sKey => $sOption ){
				$sResult .= '<option value="'.$sKey.'" '.(( in_array( $sKey, $aSelectedValues ) )?'selected="selected"':'').' >'.$sOption.'</option>';
			}
		}
		$sResult .= '</select>';
		return $sResult;
	}
}
?>