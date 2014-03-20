<?php

class OSCLztoModel extends DAO {
	
	/**
	 * It references to self object: ModelProducts.
	 * It is used as a singleton
	 *
	 * @access private
	 * @since 3.0
	 * @var ModelProducts
	 */
	private static $instance ;

	protected $log_file;
	
	public function __construct(){
		parent::__construct();
		$this->log_file = osc_plugins_path(__FILE__).'logs/database.log';
		$this->setTableName('t_lzto_user_settings');
		$this->setPrimaryKey('s_ip');
		$this->setFields(array('s_ip', 's_name', 's_settings'));
		
	}
	
	/**
	 * It creates a new ModelProducts object class ir if it has been created
	 * before, it return the previous object
	 *
	 * @access public
	 * @since 3.0
	 * @return ModelProducts
	 */
	public static function newInstance(){
		if( !self::$instance instanceof self ) {
			self::$instance = new self;
		}
		return self::$instance ;
	}
	
	public function saveSettings($settings){
		return osc_set_preference('lzto_theme_settings', serialize($settings) );
	}
	
	
	/************************************************************
	 * USER SETTINGS
	 */
	public function updateUserSettings( $ip, $settings ){
		if( !isset($settings['s_ip']) || $ip !== $settings['s_ip'] ){
			return false;
		}
		if( !isset($settings['s_name']) ){
			return false;
		}
		if( !isset($settings['s_settings'])){
			return false;
		}
		$now = date('Y/m/y H:i:s');
		$sql = sprintf(
			'REPLACE INTO %s ( s_ip, s_name, s_settings, dt_updated ) VALUES ( %s,\'%s\',\'%s\',\'%s\' )', 
			$this->getTableName(),
			$settings['s_ip'],
			$settings['s_name'],
			$settings['s_settings'],
			$now
		);
		if( $this->dao->query( $sql ) ){
			$sql = 'UPDATE %s SET dt_updated = \'%s\' WHERE s_ip = %s AND s_name != \'%s\'';
			$this->dao->query( sprintf($sql, $this->getTableName(), $now, DEMO_USER_IP, osc_current_web_theme() ) );
			return true;
		}
		return false;
	}
	
	public function createUserSettings($ip, $settings, $files){
		$s_name = '';
		
		$theme_name = osc_current_web_theme();
		$rs = $this->saveUserSettings( $ip, $theme_name, $settings );
		if( $rs ){
			if( count($files) > 0 ){
				foreach( $files as $field => $file ){
					if( $this->saveUserSettings( $ip, $theme_name.'_'.$field, $file['s_value'] ) ){
						
						$name = explode('||', $file['s_value']);
						$session_name = $name[0];
						$filename = $name[1];
						
						if( file_exists(LZO_UPLOAD_PATH.$filename) ){
							copy( LZO_UPLOAD_PATH.$filename, LZO_DEMO_USER_PATH.$filename );
						}
						if( file_exists(LZO_THUMB_PATH.$filename) ){
							copy( LZO_THUMB_PATH.$filename, LZO_DEMO_USER_THUMB_PATH.$filename );
						}
						
					}
				}
			}
			$this->log('DEMO USER CREATED IP( '.long2ip(DEMO_USER_IP).' )' ); 
			return $settings;
		}
		$this->log('FAILED TO CREATE DEMO - IP( '.long2ip(DEMO_USER_IP).' ) ERROR ( '.$this->dao->errorDesc().' )' );
		return false;
	}
	
	public function saveUserSettings( $ip, $name, $settings  ){
		
		if( !filter_var( $ip, FILTER_VALIDATE_INT ) ){
			return false;
		}
		
		if( !filter_var( $name, FILTER_SANITIZE_STRING ) ){
			return false;
		}
		
		if( empty($settings) ){
			return false;
		}
		
		if( is_array($settings) ){
			$settings = $settings['s_value'];
		}

		$date = date('Y/m/d H:i:s');
		
		$sql = sprintf('INSERT INTO %s ( s_ip, s_name, s_settings, dt_updated ) VALUES ( %s, \'%s\', \'%s\', \'%s\' )', $this->getTableName(), $ip, str_replace(' ', '_', strtolower($name)), $settings, $date);

		$rs = $this->dao->query($sql);
		return ( $rs )? true : false;
	}
	
	public function getUserSettings($ip){
		$this->dao->from( $this->getTableName() );
		$this->dao->select('s_settings');
		$this->dao->where( sprintf("s_ip = %s AND s_name = '%s'", $ip, osc_current_web_theme() ) );
		$rs = $this->dao->get();
		if( $rs && $rs->numRows() > 0 ){
			$settings = $rs->resultArray();
			return $settings[0]['s_settings'];
		}
		return false;
	}
	
	public function getUserUploads($ip){
		$this->dao->from( $this->getTableName() );
		$this->dao->select('s_name, s_settings');
		$this->dao->where( sprintf("s_ip = %s AND s_name != '%s'", $ip, osc_current_web_theme() ) );
		$rs = $this->dao->get();
		if( $rs && $rs->numRows() > 0 ){
			return $rs->resultArray();
		}
		return false;
	}
	
	public function getUserFileByName($ip, $name){
		$this->dao->from( $this->getTableName() );
		$this->dao->select('s_settings');
		$this->dao->where( sprintf("s_ip = %s AND s_name = '%s'", $ip, $name ) );
		$rs = $this->dao->get();
		if( $rs && $rs->numRows() > 0 ){
			$user = $rs->firstRow();
			return $user['s_settings'];
		}
		return false;
	}
	
	public function cleanUpUsersSettings(){

		$this->dao->select('s_ip, s_name, s_settings, dt_updated');
		$this->dao->from( $this->getTableName() );
		$this->dao->where('dt_updated < ADDTIME(NOW(), INTERVAL -1HOURS)');
		$rs = $this->dao->get();
		$files = Session::newInstance()->_drop('ajax_files');
		if( $rs->numRows() > 0 ){
			foreach( $rs as $user ){
				if( $user['s_name'] !== osc_current_web_theme() 
						&& isset( $user['s_setting'] ) ){
					
					$file = explode('||', $user['s_setting']);
					
					if( isset($files[$file[0]])){
						unset($files[$file[0]]);
					}
					
					if( file_exists(LZO_DEMO_USER_PATH.$file[1]) ){
						@unlink(LZO_DEMO_USER_PATH.$file[1]);
					}
					
					if( file_exists(LZO_DEMO_USER_THUMB_PATH.$file[1]) ){
						@unlink(LZO_DEMO_USER_THUMB_PATH.$file[1]);
					}	
					
				}
			}
		}
		Session::newInstance()->_set('ajax_files', $files);
		return $this->dao->delete($this->getTableName(), 'dt_updated < ADDTIME(NOW(), INTERVAL -1HOURS)');
	}
	
	public function deleteUserFileByName($ip, $name){
		return $this->dao->delete( $this->getTableName(), sprintf("s_ip = %s AND s_name = '%s'", $ip, $name ) );
	}
	
	
	/***************************************************************
	 * INSTALL & UNISTALL
	 */
	public function install(){
		$path = osc_plugin_resource('lz_theme_options/struct.sql');
		$sql = file_get_contents($path);
		if(! $this->dao->importSQL($sql) ){
			throw new Exception( $this->dao->getErrorLevel().' - '.$this->dao->getErrorDesc() ) ;
		}
		return true;
	}
	
	public function uninstall(){
		Preference::newInstance()->dao->delete(Preference::newInstance()->getTableName(),'s_section = \'lz_theme_options\'');
		Preference::newInstance()->dao->delete(Preference::newInstance()->getTableName(),'s_section = \'lz_theme_options_uploads\'');
		$this->dao->query(sprintf('DROP TABLE %s', $this->getTableName() ));
		$error_num = $this->dao->getErrorLevel() ;
		if( $error_num > 0 ) {
			throw new Exception($this->dao->getErrorLevel().' - '.$this->dao->getErrorDesc());
		}
		return true;
	}
	
	protected function log( $msg )
	{
		$fd = fopen( $this->log_file, "a+" );
		$str = "[" . date("Y/m/d h:i:s", mktime()) . "] " . $msg;
		fwrite($fd, $str . "\r\n\r\n");
		fclose($fd);
	}
}