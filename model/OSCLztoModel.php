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
	
	public function save($settings){
		return osc_set_preference('lxto_theme_settings', serialize($settings) );
	}
}