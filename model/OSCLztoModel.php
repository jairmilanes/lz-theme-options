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

	
	public function __construct(){
		$this->setTableName('t_lzto_user_settings');
		$this->setPrimaryKey('s_ip');
		$this->setFields(array('s_ip','s_settings'));
		parent::__construct();
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
	
	public function updateUserSettings( $ip, $settings ){
		
		if( !isset($settings['ip'])){
			return false;
		}
		if( !isset($settings['s_settings'])){
			return false;
		}
		return $this->dao->replace( $this->getTableName(), $settings );
		
	}
	
	public function saveUserSettings( $ip, $settings  ){
		return $this->dao->insert( $this->getTableName(), $settings );
	}
	
	public function getUserSettings($ip){
		
		$this->select('s_settings');
		$this->dao->from($this->getTableName());
		$this->dao->where('s_ip', $ip );
		
		$rs = $this->dao->get();
		
		if( !empty($rs) ){
			return $rs->resultArray[0];
		}
		return false;
	}
	
	public function install(){
		
	}
	
	public function unistall(){
		
	}
}