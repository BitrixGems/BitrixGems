<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ivariable
 * Date: 06.03.11
 * Time: 11:10
 * To change this template use File | Settings | File Templates.
 */
class BaseComponentGem extends BaseBitrixGem{

	/*
	 * LSB не везде еще есть (
	 *
	 * Шаблон информации о компоненте
	 *
	protected $aComponentInfo = array(
		'NAME'			=> 'mock',
		'NAMESPACE' 	=> 'iv.bitrixgems',
	);*/

	protected function _getComponentInfo(){
		return $this->aComponentInfo;
	}

	/**
	 * Получение информации о геме.
	 * Объявлена финальной, поэтому для выставления информации используем свойство $aComponentInfo
	 * @return array
	 */
	public final function getComponentInfo(){
		static $aDefaultComponentInfo = array(
			'NAME'			=> 'mock',
			'NAMESPACE' 	=> 'iv.bitrixgems',
		);
		return array_merge( $aDefaultComponentInfo, $this->_getComponentInfo() );
	}

	public function installGem(){
		$aInfo = $this->getComponentInfo();
		CopyDirFiles( $this->getGemFolder().'/component/', $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/".$aInfo['NAMESPACE'].'/'.$aInfo['NAME'].'/', true, true);
		return true;
	}

	public function unInstallGem(){
		$aInfo = $this->getComponentInfo();
		DeleteDirFilesEx("/bitrix/components/".$aInfo['NAMESPACE'].'/'.$aInfo['NAME'].'/');
		return true;
	}

}
