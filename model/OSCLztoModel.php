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
		parent::__construct();
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
	
	public function updateUserSettings( $ip, $settings ){
		
		if( !isset($settings['ip'])){
			return false;
		}
		if( !isset($settings['s_settings'])){
			return false;
		}
		return $this->dao->replace( $this->getTableName(), $settings );
		
	}
	
	public function saveUserSettings( $ip, $name, $settings  ){
		if( !filter_var( $ip, FILTER_VALIDATE_IP ) ){
			return false;
		}
		if( !filter_var( $name, FILTER_SANITIZE_STRING ) ){
			return false;
		}
		if( !is_array($settings) ){
			return false;
		}
		$values = array(
			's_ip' => "INET_ATON('$ip')",
			's_name' => str_replace(' ', '_', strtolower($name)),
			's_settings' => serialize($settings)
				
		);
		return $this->dao->insert( $this->getTableName(), $settings );
	}
	
	public function getUserSettings($ip ){
		$this->dao->from( $this->getTableName() );
		$this->dao->select('s_settings');
		$this->dao->where( "INET_NTOA(s_ip) = '$ip'" );
		$rs = $this->dao->get();
		if( !empty($rs) ){
			return $rs->resultArray;
		}
		return false;
	}
	
	public function install(){
		
	}
	
	public function unistall(){
		
	}
}