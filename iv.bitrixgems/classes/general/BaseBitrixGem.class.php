<?php
/**
 * Основной класс-родитель всех гемов
 * Он же - декларирует интерфейс.
 * @TODO - вынести интерфейс :) Ток нафига?
 *
 * @author		Vladimir Savenkov <iVariable@gmail.com>
 */
class BaseBitrixGem{
	
	protected $oGemsManager = null;
	
	//=============USAGE
	
	public final function __construct( $oGemsManager ){
		$this->oGemsManager = $oGemsManager;
	}
	
	//=============Events
	
	/**
	 * Инициализация при подключении модуля
	 * Выполняется автоматически по событию OnPageStart. Сюда удобно выносить подключения своих обработчиков и т.п.
	 * $USER еще не доступен
	 */
	public function initGem(){}

	//=============INSTALLATION
	/*
	 * LSB не везде еще есть (
	 *
	 * Шаблон информации о геме
	 *
	protected $aGemInfo = array(
	 	'GEM'			=> 'BaseBitrixGem',
		'AUTHOR'		=> 'Владимир Савенков',
	    'AUTHOR_LINK'	=> 'http://bitrixgems.ru',
		'DATE'			=> '25.01.2011',
		'VERSION'		=> '0.0.0',
		'NAME' 			=> 'BaseBitrixGem',
		'DESCRIPTION' 	=> 'Это - прототип гема, декларирующий интерфейс, для взаимодействия с менеджером :)',
	    'DESCRIPTION_FULL'	=> 'Детальное описание',
		'REQUIREMENTS'	=> 'Ничего не требует :)',
	    'REQUIRED_MODULES' => array('main'),
		'REQUIRED_GEMS'	=> array(),
	 	'REQUIRED_MIN_MODULE_VERSION' => '1.0.0',
	);*/

	protected function _getInfo(){
		return $this->aGemInfo;
	}

	/**
	 * Получение информации о геме.
	 * Объявлена финальной, поэтому для выставления информации используем свойство $aGemInfo
	 * @return array
	 */
	public final function getInfo(){
		static $aDefaultInfo = array(
			'TYPE'			=> 'functional',
			'PICTURE'		=> '',
			'GEM'			=> 'SimpleBitrixGem',
			'AUTHOR'		=> 'Владимир Савенков',
			'AUTHOR_LINK'	=> '',
			'DATE'			=> '25.01.2011',
			'VERSION'		=> '0.0.0',
			'NAME' 			=> 'SimpleBitrixGem',
			'DESCRIPTION' 	=> 'Это - прототип гема, декларирующий интерфейс, для взаимодействия с менеджером :)',
			'DESCRIPTION_FULL' => '',
			'REQUIREMENTS'	=> '',
			'LIKES'			=> 0,
			'DOWNLOADS'		=> 0,
			'REQUIRED_MODULES' => array(),
			'REQUIRED_GEMS' => array(),
			'REQUIRED_MIN_MODULE_VERSION' => '1.0.0',
		);
		return array_merge( $aDefaultInfo, $this->_getInfo() );
	}

	/**
	 * Получение папки, в которой находится гем
	 * @return string
	 */
	public function getGemFolder(){
		return dirname( dirname(__FILE__).'/../../gems/'.$this->getCode().'/gem.php' );
	}

	/**
	 * Возвращает имя гема.
	 *
	 * @return string
	 */
	public function getName(){
		$aInfo = $this->getInfo();
		return $aInfo['NAME'];
	}

	/**
	 * Возвращает код гема
	 * @return string
	 */
	public function getCode(){
		$aInfo = $this->getInfo();
		return $aInfo['GEM'];
	}

	/**
	 * Проверка окружения. Выполняется гемом при установке.
	 * @return bool
	 */
	public function checkRequirements(){
		return true;
	}

	/**
	 * Метод, выполняющийся при установке гема. По умолчанию инсталлирует папки
	 * js (в /bitrix/js/iv.bitrixgems/КОД ГЕМА/) и images (в /bitrix/images/iv.bitrixgems/КОД ГЕМА/)
	 *
	 * Естесственно при условии, что они есть в геме :)
	 *
	 * @return bool
	 */
	public function installGem(){
		if( is_readable( $this->getGemFolder().'/js/' ) ) CopyDirFiles( $this->getGemFolder().'/js/', $_SERVER["DOCUMENT_ROOT"]."/bitrix/js/iv.bitrixgems/".$this->getCode()."/", true, true);
		if( is_readable( $this->getGemFolder().'/images/' ) ) CopyDirFiles( $this->getGemFolder().'/images/', $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/iv.bitrixgems/".$this->getCode()."/", true, true);
		return true;
	}

	/**
	 * Метод вызываемый при обновлении гема из каталога.
	 *
	 * По умочанию переустанаваливает установленные в installGem папки.
	 *
	 * @param array $aOldGemInfo - getInfo() о старой версии гема, которая была установлена ранее.
	 * @return bool
	 */
	public function updateGem( $aOldGemInfo = array() ){
		DeleteDirFilesEx("/bitrix/js/iv.bitrixgems/".$this->getCode().'/');
		DeleteDirFilesEx("/bitrix/images/iv.bitrixgems/".$this->getCode().'/');
		if( is_readable( $this->getGemFolder().'/js/' ) ) CopyDirFiles( $this->getGemFolder().'/js/', $_SERVER["DOCUMENT_ROOT"]."/bitrix/js/iv.bitrixgems/".$this->getCode()."/", true, true);
		if( is_readable( $this->getGemFolder().'/images/' ) ) CopyDirFiles( $this->getGemFolder().'/images/', $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/iv.bitrixgems/".$this->getCode()."/", true, true);
		return true;
	}

	/**
	 * Метод, вызываемый при удалении гема.
	 * По умочанию сносит установленные в installGem папки.
	 * @return bool
	 */
	public function unInstallGem(){
		DeleteDirFilesEx("/bitrix/js/iv.bitrixgems/".$this->getCode().'/');
		DeleteDirFilesEx("/bitrix/images/iv.bitrixgems/".$this->getCode().'/');
		return true;
	}

	//=============AJAX

	/**
	 * Метод на который передается управление при обращении на страницу
	 * /bitrix/admin/bitrixgems_simpleresponder.php?gem=КОД ГЕМА & AJAXREQUEST
	 *
	 * @param  $aOptions - $_REQUEST массив
	 * @return void
	 */
	public function processAjaxRequest( $aOptions ){}

	//=============OPTIONS

	/**
	 * Метод, сигнализирующий о том, нужна ли гему автоматическая отстройка административного пункта меню.
	 * Если	возвращает true, то будет добавлен одноименный пункт меню со ссылкой
	 * на страницу /bitrix/admin/bitrixgems_simpleresponder.php?gem=КОД ГЕМА.
	 * @return bool
	 */
	public function needAdminPage(){
		return false;
	}

	/**
	 * Метод вызываемый при обращении к странице /bitrix/admin/bitrixgems_simpleresponder.php?gem=КОД ГЕМА
	 * @return void
	 */
	public function showAdminPage(){
		$aOptionsRaw	= $this->getOptions();
		$aOptions 		= $this->getDefaultOptions();
		foreach( $aOptions as $sKey => &$aValue ){
			$aValue['value'] = $aOptionsRaw[ $sKey ];
		}
		if( is_readable($this->getGemFolder().'/options/adminOptionPage.php') ){
			include( $this->getGemFolder().'/options/adminOptionPage.php' );
		}elseif( is_readable(dirname(__FILE__).'/../../admin/adminOptionPageDefault.php') ){
			include( dirname(__FILE__).'/../../admin/adminOptionPageDefault.php' );
		}
	}

	/**
	 * Метод, вызываемый перед показом админской страницы, до начала вывода хидера.
	 * @param  $aRequest
	 * @return void
	 */
	public function beforeShowAdminPage( $aRequest ){}

	/**
	 * Метод автоматически вызываемый при пост запросе на странице /bitrix/admin/bitrixgems_simpleresponder.php?gem=КОД ГЕМА
	 *
	 * @param  $aOptions $_REQUEST
	 * @return void
	 */
	public function processAdminPage( $aOptions ){
		if( !empty( $aOptions[ $this->getCode() ] ) ){
			$this->setOptions( $aOptions[ $this->getCode() ] );
		}
	}

	/**
	 * Опции по умолчанию
	 * @return array
	 */
	protected function getDefaultOptions(){
		return array();
	}

	/**
	 * Возвращает опции гема
	 * @return array
	 */
	protected function getOptions(){
		$aOptions = array();
		if( is_readable( $this->getGemFolder().'/options/options.php' ) ){
			$aOptions = include( $this->getGemFolder().'/options/options.php' );
		}
		$aDefaultOptions = array();
		$aTDefaultOptions = $this->getDefaultOptions();
		if( !empty( $aTDefaultOptions ) ){
			foreach( $aTDefaultOptions as $sKey => $aOption ){
				$aDefaultOptions[ $sKey ] = @$aOption['value'];
			}
		}		
		if( !is_array( $aOptions ) ) $aOptions = $aDefaultOptions;
		$aOptions = array_merge( $aDefaultOptions, $aOptions );
		return $aOptions;
	}

	/**
	 * Дефолтное сохранение опций.
	 * @param  $aOptions
	 * @return int
	 */
	protected function setOptions( $aOptions ){
		if( !file_exists( $this->getGemFolder().'/options/' ) ){
			if( !mkdir( $this->getGemFolder().'/options/', 0777 ) ){
				throw new Exception('Can\'t create options directory! '.$this->getGemFolder().'/options/');
			}
		}
		return file_put_contents(
			$this->getGemFolder().'/options/options.php',
			'<?php return '.var_export( $aOptions, true ).';?>'
		);
	}

	//==============Error Messages handling

	protected $aMessages = array();

	/**
	 * Добавляем сообщение в очередь.
	 * @param  $sMessage сообщение
	 * @param string $sType тип ошибки: OK | ERROR
	 * @return void
	 */
	public function addMessage( $sMessage, $sType = 'OK' ){
		$this->aMessages[] = array(
			'sMessage' 	=> $sMessage,
			'sType' 	=> $sType
		);
	}

	/**
	 * Возвращает все ошибки
	 * @return array
	 */
	public function getMessages(){
		return $this->aMessages;
	}

	/**
	 * Устанавливает ошибки
	 * @param  $aMessages
	 * @return void
	 */
	public function setMessages( $aMessages ){
		$this->aMessages = $aMessages;
	}

	/**
	 * Очищает очередь ошибок
	 * @return void
	 */
	public function clearMessages(){
		$this->setMessages(array());
	}

	/**
	 * Отображает ошибки в админке стандартным образом, через CAdminMessage
	 * @return void
	 */
	public function showMessages(){
		$aMessages = $this->getMessages();
		if( !empty( $aMessages ) ){
			foreach( $aMessages as $aMessage ){
				switch( $aMessage['sType'] ){
					case 'OK':
						echo CAdminMessage::ShowMessage(array( 'TYPE'=>'OK','MESSAGE' => $aMessage['sMessage'], 'HTML' => true ));
						break;

					case 'ERROR':
						echo CAdminMessage::ShowMessage(array( 'TYPE'=>'ERROR','MESSAGE' => $aMessage['sMessage'], 'HTML' => true ));
						break;
				}
			}
		}
	}
}
?>