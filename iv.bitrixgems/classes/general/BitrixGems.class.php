<?php
/**
 * Манагер кирпичиков для битрикса
 * @author		Vladimir Savenkov <me@ivariable.ru>
 *
 * Спасибо товарищам <____> за наше счастливое детство:
 *  http://alexvaleev.ru/popup-window-bitrix/
 *  http://alexvaleev.ru/work-with-tar-gz-in-php/
 *
 *  http://www.fatcow.com/free-icons
 *
 * @TODO		указать лицензию :) 
 */
class BitrixGems{

	protected $aLoadedGems = array();

	protected $sDataTableName = 'bg_bitrixgems_custom_data';
	
	protected $aGems = array();
	protected $aGemsInfo = array();
	protected static $oInstance = null;
	protected $aInstalledGems = array();
	protected $aInstalledGemsNames = array();
	
	protected $sInstalledGemsOptionKey = 'INSTALLED_Gems';
	
	/******************************************************/
	/*   				ПУБЛИЧНЫЕ МЕТОДЫ				  */
	/******************************************************/
	/**
	 * @static
	 * @param  string $sGemName
	 * @return bool
	 */
	public static function isGemInstalled( $sGemName ){		
		return self::getInstance()->GemInstalled( $sGemName );
	}

	/**
	 * @static
	 * @param  string $sGemName
	 * @return BaseBitrixGem
	 */
	public static function getBitrixGem( $sGemName ){
		return self::getInstance()->getInstalledGem( $sGemName );
	}

	/**
	 * @static
	 * @param  $sGemName
	 * @return BaseBitrixGem
	 */
	public static function getGem( $sGemName ){
		return self::getInstance()->getLoadedGem( $sGemName );
	}

	/******************************************************/
	/*   				СЛУЖЕБНЫЕ МЕТОДЫ				  */
	/* 													  */
	/* Все что ниже этого комментария - служебные методы. */ 
	/* Интереса для широкой публики не представляют :)    */
	/******************************************************/
	
	
	//=================ИНСТАНЦИРОВАНИЕ

	/**
	 * @static
	 * @return BitrixGems
	 */
	public static function getInstance(){
		if (!(self::$oInstance instanceof self)){
			try{
				self::$oInstance = new self();
				self::$oInstance->initInstalledGems();
			}catch(Exception $e){
				//На случай хитрожопого кирпичика.
				if(defined('ADMIN_SECTION')){
					var_dump( $e->getMessage() );
				}
			}
		};
		return self::$oInstance;
	}
	
	protected function __construct(){
		$this->handleGemUpdate();
		$this->getInstalledGems( false );
	}
	protected function __clone(){}	
	
	/**
	 * Инициализация костыликов. Выполняется в include.php
	 */
	protected function initInstalledGems(){
		$aGems = $this->getInstalledGems( $bReturnGems = false );
		foreach( $aGems as $sGemName => $aGemOptions ){
			try{
				$oGem = $this->loadGem( $sGemName );
				if( $oGem ){
					//Тут можно добавить self::checkRequirements( $oGem->getInfo() );
					$this->initGemEvents( $oGem, $aGemOptions );
					$oGem->initGem();
					$this->aLoadedGems[ $sGemName ] = $oGem;
				}
			}catch( Exception $e ){
				//@TODO: Тут нать буит прикрутить запись в журнал
			}
		}
		return true;
	}
	
	public function GemInstalled( $sGemName, $bStrongCheck = false ){
		$bResult = in_array( $sGemName, $this->aInstalledGemsNames );
		if( $bStrongCheck && $bResult){
			if( empty( $this->aGems ) ) $this->collectGems();
			$bResult = isset( $this->aGems[ $sGemName ] );
			if( !$bResult ){
				$aInstalledGems = $this->getInstalledGems( false );
				unset( $aInstalledGems[ $sGemName ] );
				$this->setInstalledGems($aInstalledGems);				
			}
		}
		return $bResult;
	}

	//============Права доступа

	public function userHasAccess2Gem( $aUG, $sGemName ){
		$aAllowedUG = $this->getGemAccessRights( $sGemName );
		if( !is_array( $aUG ) ) $aUG = array();
		$aIntersection = array_intersect( $aUG, $aAllowedUG );
		return !empty( $aIntersection );
	}

	public function setGemAccessRights( $sGemName, $aAllowedUG ){
		if( !isset( $this->aOptions['ACCESS'] ) ) $this->aOptions['ACCESS'] = array();
		$this->aOptions['ACCESS'][ $sGemName ] = $aAllowedUG;
		return $this->saveGemsConfigFile();
	}

	public function getGemAccessRights( $sGemName ){
		$aResult = $this->aOptions[ 'ACCESS' ][ $sGemName ];
		if( !is_array( $aResult ) ) $aResult = array();
		return $aResult;
	}
	
	//============Отечественный NoSQL
	
	public function saveData( $sGem, $mID, $mData ){
		global $DB;
		$mData = $DB->Query('REPLACE INTO `'.$this->sDataTableName.'` SET gem="'.$DB->ForSQL( $sGem ).'", id="'.$DB->ForSQL( $mID ).'", data="'.$DB->ForSQL( serialize( $mData ) ).'" ');
		return true;
	}
	
	public function removeData( $sGem, $mID ){
		global $DB;
		$mData = $DB->Query('DELETE FROM `'.$this->sDataTableName.'` WHERE gem="'.$DB->ForSQL( $sGem ).'" AND id="'.$DB->ForSQL( $mID ).'"');
		return true;
	}
	
	public function getData( $sGem, $mID ){
		global $DB;
		$mData = $DB->Query('SELECT data FROM `'.$this->sDataTableName.'` WHERE gem="'.$DB->ForSQL( $sGem ).'" AND id="'.$DB->ForSQL( $mID ).'"')->Fetch();
		if( !$mData ) return null;
		return unserialize( $mData['data'] );
	}

	//============Ивенты гемов

	protected function initGemEvents( BaseBitrixGem $oGem, $aGemConfig ){
		if( empty( $aGemConfig['events'] ) || !is_array( $aGemConfig['events'] ) ) return true;
		foreach( $aGemConfig['events'] as $sModuleName => $aEvent ){
			if( !is_array( $aEvent ) ) continue;
			foreach( $aEvent as $sEventName => $aCallBackFunctions ){
				if( !is_array($aCallBackFunctions) ) continue;
				foreach( $aCallBackFunctions as $sCallBackFunction ){
					AddEventHandler(
						$sModuleName,
						$sEventName,
						array( $oGem, $sCallBackFunction )
					);
				}
			}
		}
	}

	protected function collectGemEvents( BaseBitrixGem $oGem ){
		$aResult = array();
		$oReflection = new ReflectionObject( $oGem );
		$aMethods = $oReflection->getMethods( ReflectionMethod::IS_PUBLIC );
		if( !empty( $aMethods ) ){
			foreach( $aMethods as $oMethod ){
				if( preg_match( '~^event_(.*?)_(.*?)_(.*)$~isu', $oMethod->getName(), $aDetails ) ){
					if( !isset( $aResult[ $aDetails[1] ] ) ) $aResult[ $aDetails[1] ] = array();
					if( !isset( $aResult[ $aDetails[1] ][ $aDetails[2] ] ) ) $aResult[ $aDetails[1] ][ $aDetails[2] ] = array();
					$aResult[ $aDetails[1] ][ $aDetails[2] ][] = $oMethod->getName();
				}
			}
		}
		return $aResult;
	}
	
	//============ИНСТАЛЛЯЦИЯ

	/**
	 * Получаем список костыликов
	 */
	protected function collectGems(){
		clearstatcache();
		$aFoundGems = glob( dirname(__FILE__).'/../../gems/*/gem.php' );
		foreach( $aFoundGems as $sGemInstallPath ){
			$sGemName = basename( dirname( $sGemInstallPath ) );
			$oGem = $this->loadGem( $sGemName );
			if( $oGem !== null ) {
				$this->aGems[ $sGemName ] = $oGem;
				$this->aGemsInfo[ $sGemName ] = $oGem->getInfo();
			}
		}
		return $this->aGems;
	}
	
	/**
	 * Загрузка инсталлера костылика
	 * @return BaseBitrixGem
	 */
	public function loadGem( $sGemName ){
		
		$oGem 		= null;
		$bRealGem 	= false;
		$sGemClass 	= 'BitrixGem_'.$sGemName;
		
		$bRealGem = class_exists( $sGemClass );
			
		if( !$bRealGem ){
			
			$sGemInstallPath = dirname(__FILE__).'/../../gems/'.$sGemName.'/gem.php';
			if( !is_readable( $sGemInstallPath ) ) return null;
			
			require_once( $sGemInstallPath );
			$bRealGem = class_exists( $sGemClass );
		}
		
		if( $bRealGem ){
			$oGem = new $sGemClass( $this );
			$bRealGem = is_a( $oGem, 'BaseBitrixGem' );
		}
		if( !$bRealGem ) $oGem = null;
		
		return $oGem;
	}
	
	
	public function installGem( $sGemName ){
		$this->collectGems();
		if( $this->GemInstalled( $sGemName, true ) ) throw new Exception( 'Gem '.$sGemName.' already installed!' );
		$oGem = $this->loadGem( $sGemName );
		if( $oGem === null ){ //Нать скачать :)
			$this->downloadGemFromCatalog( $sGemName );
			$this->extractGem( $sGemName );			
			$oGem = $this->loadGem( $sGemName );
		}
		if( $oGem !== null ){
			if( !self::checkRequirements( $oGem->getInfo() ) ){
				throw new Exception('Не выполняются минимальные требования гема! Установка отменена.');
			}
			$oGem->checkRequirements();
			$oGem->installGem();
			$aGemOptions = array(
				'events' => $this->collectGemEvents( $oGem ),
			);
			$aInstalledGems = $this->getInstalledGems( false );
			$aInstalledGems[ $sGemName ] = $aGemOptions;
			$this->setInstalledGems($aInstalledGems);
		}
		return true;
	}

	public static function checkRequirements( $aGemInfo ){
		$bResult = true;

		if( isset( $aGemInfo['REQUIRED_MIN_MODULE_VERSION'] ) ){
			$oModule = CModule::CreateModuleObject('iv.bitrixgems');
			if( $oModule->MODULE_VERSION <  $aGemInfo['REQUIRED_MIN_MODULE_VERSION'] ) $bResult = false;
		}

		if( isset( $aGemInfo['REQUIRED_MODULES'] ) && $bResult ){
			foreach( $aGemInfo['REQUIRED_MODULES'] as $sModule ){
				$sModule = trim( $sModule );
				if( empty( $sModule ) ) continue;
				if( !CModule::IncludeModule( $sModule ) ) $bResult = false;
			}
		}

		if( isset( $aGemInfo['REQUIRED_GEMS'] ) && $bResult ){
			foreach( $aGemInfo['REQUIRED_GEMS'] as $sGem ){
				$sGem = trim( $sGem );
				if( empty( $sGem ) ) continue;
				if( !self::isGemInstalled( $sGem ) ) $bResult = false;
			}
		}
		return $bResult;
	}
	
	public function unInstallGem( $sGemName ){
		if( !$this->GemInstalled( $sGemName ) ) throw new Exception( 'Gem '.$sGemName.' not installed!' );
		$oGem = $this->loadGem( $sGemName );
		if( $oGem !== null ){
			$oGem->unInstallGem();
			$aInstalledGems = $this->getInstalledGems( false );
			unset( $aInstalledGems[ $sGemName ] );			
			$this->setInstalledGems($aInstalledGems);
		}
	}

	public function updateGem( $sGemName ){
		$this->collectGems();
		if( !$this->GemInstalled( $sGemName, true ) ) return $this->installGem( $sGemName );
		$oGem = $this->loadGem( $sGemName );
		$aInfo = $oGem->getInfo();
		$_SESSION['BITRIXGEMS_UPDATE'][ $sGemName ] = $aInfo;
		LocalRedirect( $_SERVER['REQUEST_URI'] );		
	}

	public function handleGemUpdate(){
		if( !empty( $_SESSION['BITRIXGEMS_UPDATE'] ) ){
			foreach( $_SESSION['BITRIXGEMS_UPDATE'] as $sGemName => $aInfo ){
				try{
					$this->downloadGemFromCatalog( $sGemName );
					$this->extractGem( $sGemName );
					$oGem = $this->loadGem( $sGemName );
					$oGem->updateGem( $aInfo );
					$aGemOptions = array(
						'events' => $this->collectGemEvents( $oGem ),
					);
					$aInstalledGems = $this->getInstalledGems( false );
					$aInstalledGems[ $sGemName ] = $aGemOptions;
					$this->setInstalledGems($aInstalledGems);
				}catch( Exception $e ){
					unset( $_SESSION['BITRIXGEMS_UPDATE'][ $sGemName ] );
					//@TODO: Еманарот чо делать то???7
				}
			}
			unset( $_SESSION['BITRIXGEMS_UPDATE'] );
			LocalRedirect( $_SERVER['REQUEST_URI'] );
		}
	}

	//================УПРАВЛЕНИЕ МОДУЛЕМ

	protected $aOptions;

	protected function saveGemsConfigFile(){
		return file_put_contents( $this->getGemsConfigFile(), '<?php return '.var_export( $this->aOptions, true ).'?>' );
	}

	protected function getGemsConfigFile(){
		$sConfigFile = dirname(__FILE__).'/../../gems_options.php';
		//if( !is_readable( $sConfigFile ) ) throw new Exception('Файл '. $sConfigFile.' должен быть доступен для чтения!');
		if( !is_writeable( dirname($sConfigFile) ) ) throw new Exception('Файл '. dirname($sConfigFile).' должен быть доступен для записи!');
		if( file_exists( $sConfigFile ) ){
			if( !is_writeable( $sConfigFile ) ) throw new Exception('Файл '. $sConfigFile.' должен быть доступен для записи!');
		}
		return $sConfigFile;
	}

	public function setInstalledGems( $aGems ){
		$this->aOptions['GEMS'] = $aGems;
		return $this->saveGemsConfigFile();
	}
	
	public function getInstalledGems( $bReturnGems = false ){
	
		$this->aOptions = include( $this->getGemsConfigFile() );
		if( !isset( $this->aOptions['GEMS'] ) ) $this->aOptions['GEMS'] = $this->aOptions;
		$mResult = $this->aOptions['GEMS'];
		$this->aInstalledGems = $mResult;
		$this->aInstalledGemsNames = array_keys( $mResult );
		if( $bReturnGems === true  ){
			$this->collectGems();
			$mTResult = array();
			foreach( $this->aGems as $sGemName => $oInstaller ){
				if( in_array( $sGemName, $this->aInstalledGemsNames ) ) $mTResult[ $sGemName ] = $oInstaller;
			}
			$mResult = $mTResult;
		}
		if( $bReturnGems === 'info' ){
			$this->collectGems();
			$mTResult = array();
			foreach( $this->aGems as $sGemName => $oInstaller ){
				if( in_array( $sGemName, $this->aInstalledGemsNames ) ) $mTResult[ $sGemName ] = $oInstaller->getInfo();
			}
			$mResult = $mTResult;
		}
		return $mResult;
	}

	//===============Всякая служебка
	/**
	 * @param  $sGemName
	 * @return BaseBitrixGem
	 */
	public function getInstalledGem( $sGemName ){
		$mResult = null;
		if( $this->GemInstalled($sGemName) && isset( $this->aLoadedGems[ $sGemName ] )){
			$mResult = $this->loadGem($sGemName);
		}
		return $mResult;
	}

	public function getLoadedGem( $sGemName ){
		$mResult = null;
		if( $this->GemInstalled($sGemName) && isset( $this->aLoadedGems[ $sGemName ] )){
			$mResult = $this->aLoadedGems[ $sGemName ];
		}
		return $mResult;
	}

	public function getLocalGems( $bReturnGems = false ){
		$this->collectGems();
		if( !$bReturnGems ) return $this->aGemsInfo;
		return $this->aGems;
	}

	/**
	 * @param  $sGemName
	 * @return BaseBitrixGem
	 */
	public function getGemInstance( $sGemName ){
		if( empty( $this->aGems ) ) $this->collectGems();
		$mResult = null;		
		if( isset( $this->aGems[ $sGemName ] ) ) $mResult = $this->aGems[ $sGemName ];
		return $mResult;
	}

	private $aVotedGems = null;

	protected function getLocalVotings(){
		$aResult = @include( dirname(__FILE__).'/../../gems_votings.php' );
		if( !is_array( $aResult ) ) $aResult = array();
		return $aResult;
	}

	protected function saveLocalVotings(){
		return @file_put_contents( dirname(__FILE__).'/../../gems_votings.php', '<?php return '.var_export( $this->aVotedGems, true ).' ?>' );
	}

	public function isVotingAllowed( $sGemName ){
		if( !is_array( $this->aVotedGems ) ){
			$this->aVotedGems = $this->getLocalVotings();
		}
		return !isset( $this->aVotedGems[ $sGemName ] );
	}


	//===============CATALOG
	
	/**
	 * Решено отдавать тупо сериализованную строку. Ибо эбо.
	 */
	private $sUpdateSite 			= 'http://bitrixgems.ru/api/catalog_for_module.php';
	private $sCatalogGemURL 		= 'http://bitrixgems.ru/api/get_gem.php?gem=';
	private $sCatalogGemUploadURL 	= 'http://bitrixgems.ru/api/upload_gem.php';
	
	protected function getLocalCatalogName(){
		return dirname( __FILE__ ).'/../../gems_catalog.php';
	}

	public function getGemsFromCatalog( $bAsSimpleArray = false, $bForceUpdate = false ){
		$aGemsCatalog = @include( $this->getLocalCatalogName() );
		if( ( date('d-m-Y',$aGemsCatalog['UPDATED']) != date( 'd-m-Y' ) ) || empty( $aGemsCatalog ) || $bForceUpdate ){
			try{
				$aTGemsCatalog = $this->getGemsCatalogUpdate();
				$aGemsCatalog = $aTGemsCatalog;
			}catch( Exception $e ){
				global $APPLICATION;
				$APPLICATION->ThrowException( $e->getMessage() );
			}
		}
		if( !isset( $aGemsCatalog[ 'ALL_GEMS' ] ) ) $aGemsCatalog[ 'ALL_GEMS' ] = array();

		if( $bAsSimpleArray ){
			$aGemsCatalog = $aGemsCatalog[ 'ALL_GEMS' ];
		}

		return $aGemsCatalog;
	}

	public function getGemsCatalogUpdate(){
		$mCatalog = @file_get_contents( $this->sUpdateSite );
		if( empty( $mCatalog ) ) throw new Exception( 'Can\'t get catalog update from '.$this->sUpdateSite );
				
		$mCatalog = @unserialize( $mCatalog );
		if( empty( $mCatalog ) ) throw new Exception( 'Can\'t unserialize new catalog Т_Т' );

		if( strtoupper( LANG_CHARSET) != 'UTF-8' ){
			$mCatalog = $this->convertValueToSiteEncoding($mCatalog);		
		}

		
		$mCatalog['UPDATED'] = time();
		$mCatalog['CATALOG'] = array();
		$mCatalog['ALL_GEMS'] = array_keys( $mCatalog['GEMS_CATALOG']['ITEMS'] );

		foreach( $mCatalog['GEMS_CATALOG']['ITEMS'] as $sGemID => $aGem ){			
			foreach( $aGem['CATEGORY'] as $sCategory ){
				if( !isset( $mCatalog['CATALOG'][ $sCategory ] ) ){
					if( !isset($mCatalog['GEMS_CATALOG']['CATEGORIES'][ $sCategory ]) ) continue;
					$mCatalog['CATALOG'][ $sCategory ] = $mCatalog['GEMS_CATALOG']['CATEGORIES'][ $sCategory ];
					$mCatalog['CATALOG'][ $sCategory ]['ITEMS'] = array();
				}
				$mCatalog['CATALOG'][ $sCategory ]['ITEMS'][ $sGemID ] = $aGem;
			}
		}

		//unset( $mCatalog['GEMS_CATALOG'] );

		$sCatalogName = $this->getLocalCatalogName();
		if( !file_exists( $sCatalogName ) ){
            @file_put_contents( $sCatalogName, '<?php return '.var_export( $mCatalog, true ).';?>' );
        }
		if(!is_writeable( $sCatalogName )) throw new Exception( 'File "'.$sCatalogName.'" must be writeable!' );
		
		file_put_contents( $sCatalogName, '<?php return '.var_export( $mCatalog, true ).';?>' );
		return $mCatalog;
	}

	public function downloadGemFromCatalog( $sGemName ){
		$aCatalogGems = $this->getGemsFromCatalog( $bOnlyNames = true, $bForceUpdate = true );
		if( !in_array( $sGemName, $aCatalogGems ) ) throw new Exception( 'Gem '.$sGemName.' not found in catalog!' );
		$sArchiveName = $this->getGemArchieveNameForDownload( $sGemName );
		if( file_exists( $sArchiveName ) ){
			if( !unlink( $sArchiveName ) ){
				throw new Exception( "Can't remove previous version of file ".$sArchiveName );
			}
		}
		$sArchiveFile = file_get_contents( $this->sCatalogGemURL.$sGemName.'&for='.$_SERVER['HTTP_HOST'] ); //Так делать плохо, но связываться с курлом и прочим вгетом ради мелких кирпичичков - не стоит :)
		if( empty( $sArchiveFile ) ) throw new Exception( 'Can\'t get Gem from '.$this->sCatalogGemURL.$sGemName );
		$bResult = file_put_contents( $sArchiveName, $sArchiveFile );
		return $bResult;
	}

	public function likeGem( $sGemCode ){
		$bResult = false;
		if( $this->isVotingAllowed( $sGemCode ) ){
			$sResult = @file_get_contents( $this->sCatalogGemURL.$sGemCode.'&for='.$_SERVER['HTTP_HOST'].'&like='.md5( $sGemCode.$_SERVER['HTTP_HOST'].'piuPIUOLOLoLolOlo!@ ^_^ DEFENCE EPIC FAIL ^))))' ) );
			if( $sResult == 'Success!' ) $bResult = true;
			$this->aVotedGems[ $sGemCode ] = $sGemCode;
			$this->saveLocalVotings();
			$this->aVotedGems = null;
		}
		return $bResult;
	}

	//===========UPLOAD

	public function uploadGemToCatalog( $sGemName, $aInfo ){
		if( !function_exists( 'curl_init' ) )throw new Exception( 'Sorry, need CURL lib for uploading.' );
		$mResult = $this->archiveGem( $sGemName );

		$aInfo = array(
			'DESCRIPTION' => $aInfo,
			'SENDER_INFO' => array(
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
			),
		);

		$ch = curl_init( $this->sCatalogGemUploadURL );
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
		curl_setopt($ch, CURLOPT_POST, true);
		$post = array(
			'gemname' => $sGemName,
			"gem" => '@'.$mResult,
			'info'  => var_export( $aInfo, true )
		);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		$response = curl_exec($ch);		
		return $response;
	}

	protected function getGemArchieveNameForUpload( $sGemName ){
		if( !is_readable( dirname( __FILE__ ).'/../../exchange/upload/' ) ){
			mkdir(  dirname( __FILE__ ).'/../../exchange/upload/', 0777, true );
		}
		return ( dirname( __FILE__ ).'/../../exchange/upload/'.$sGemName.'.tgz' );
	}

	protected function getGemArchieveNameForDownload( $sGemName ){
		if( !is_readable( dirname( __FILE__ ).'/../../exchange/download/' ) ){
			mkdir(  dirname( __FILE__ ).'/../../exchange/download/', 0777, true );
		}
		return ( dirname( __FILE__ ).'/../../exchange/download/'.$sGemName.'.tgz' );
	}

	public function archiveGem( $sGemName ){
		if( !($oGem = $this->getGemInstance( $sGemName )) )throw new Exception( 'Gem '.$sGemName.' not found!' );
		require_once( $_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/tar_gz.php" );
		$sArchiveName = $this->getGemArchieveNameForUpload( $sGemName );
		if( file_exists( $sArchiveName ) ){
			if( !unlink( $sArchiveName ) ){
				throw new Exception( "Can't remove previous version of file ".$sArchiveName );
			}
		}
		$sGemFolder = $oGem->getGemFolder();

		$oArchiver = new CArchiver($sArchiveName, true);
		$this->convertGemCharset( $sGemFolder, false );
		$bResult = $oArchiver->add( $sGemFolder, $sGemName, $sGemFolder );		
		$this->convertGemCharset( $sGemFolder, true );
		$arErrors = &$oArchiver->GetErrors();
		$aErrors = array();
		if(count($arErrors)>0){
			foreach ($arErrors as $value){
				$aErrors[] = "[".$value[0]."] ".$value[1];
			}
		}
		if( !empty( $aErrors ) ) throw new Exception( 'Errors during archiving Gem "'.$sGemName.'"! '.implode(PHP_EOL, $aErrors) );
		return $sArchiveName;
	}

	public function extractGem( $sGemName ){
		require_once( $_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/tar_gz.php" );
		$sArchiveName = $this->getGemArchieveNameForDownload( $sGemName );
		if( !is_readable( $sArchiveName ) ) throw new Exception( 'Gem archieve for '.$sGemName.' not found in '.$sArchiveName );

		$sToFolder = dirname(__FILE__).'/../../gems/';
		$oArchiver = new CArchiver( $sArchiveName );
		$bResult = $oArchiver->extractFiles($sToFolder);
		$arErrors = &$oArchiver->GetErrors();
		$aErrors = array();
		if(count($arErrors)>0){
			foreach ($arErrors as $value){
				$aErrors[] = "[".$value[0]."] ".$value[1];
			}
		}
		if( !empty( $aErrors ) ) throw new Exception( 'Errors during extracting Gem "'.$sGemName.'"! '.implode(PHP_EOL, $aErrors) );
		$this->convertGemCharset( dirname(__FILE__).'/../../gems/'.$sGemName.'/', $bToSite = true );
		return $sToFolder.$sGemName.'/';
	}

	//==========Charset encoding shit 
	/**
	 * Я что, пьяный был? Что вообщзе делает это дерьмо в классе?
	 * @TODO выкинуть в либы нафиг.
	 */

	protected function convertGemCharset( $mFile, $bToSite = true ){
		if( strtoupper(LANG_CHARSET) != 'UTF-8' ){			
			if( is_dir( $mFile ) ){
				$aPatternsToConvert = array( '*.php', '*.js' );			
				$aResult = array();
				foreach( $aPatternsToConvert as $sPattern ){
					$aResult = array_merge( $aResult, $this->rglob( $sPattern, 0, $mFile ));
				}				
				$mFile = $aResult;
			}
			if( !is_array( $mFile ) ) $mFile = array( $mFile );
			if( !empty( $mFile ) ){
				foreach( $mFile as $sPhpFile ){
					if( $bToSite ){
						file_put_contents( $sPhpFile, iconv( 'UTF-8', LANG_CHARSET.'//IGNORE', file_get_contents( $sPhpFile ) ) );		
					}else{
						file_put_contents( $sPhpFile, iconv( LANG_CHARSET, 'UTF-8//IGNORE', file_get_contents( $sPhpFile ) ) );		
					};					
				}
			}
		}
		return true;
	}


	protected function convertValueToSiteEncoding( $mArray ){
		if( is_array( $mArray ) ){
			$aTArray = array();
			foreach( $mArray as $mKey => $mValue ){
				$mKey = iconv( 'UTF-8', LANG_CHARSET.'//IGNORE', $mKey );
				$aTArray[ $mKey ] = $this->convertValueToSiteEncoding( $mValue );
			}
			return $aTArray;
		}else{
			return iconv( 'UTF-8', LANG_CHARSET.'//IGNORE', $mArray );
		}
	}
	
	protected function rglob($pattern='*', $flags = 0, $path=''){
		$paths=glob($path.'*', GLOB_MARK|GLOB_ONLYDIR|GLOB_NOSORT);
		$files=glob($path.$pattern, $flags);
		foreach ($paths as $path) { $files=array_merge($files,$this->rglob($pattern, $flags, $path)); }
		return $files;
	}
}
?>