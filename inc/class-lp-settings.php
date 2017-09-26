<?php

/**
 * Class LP_Settings
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LP_Settings {
	/**
	 * @var array
	 */
	protected $_options = array();

	/**
	 * @var string
	 */
	protected $_prefix = '';

	/**
	 * @var bool
	 */
	static protected $_instance = false;

	/**
	 * Constructor.
	 *
	 * @param array|mixed $data
	 * @param string      $prefix
	 *
	 */
	public function __construct( $data = false, $prefix = 'learn_press_' ) {

		$this->_prefix = $prefix;

		if ( ! $data ) {
			$this->_load_options();
		} else {
			settype( $data, 'array' );
			$this->_options = $data;
		}
	}

	/**
	 * @param string $group
	 * @param string $prefix
	 *
	 * @return LP_Settings
	 */
	public function get_group( $group, $prefix = '' ) {
		return new LP_Settings( $this->get( $group ), $prefix );
	}

	/**
	 * Load options from database.
	 */
	protected function _load_options() {
		if ( false === ( $_options = wp_cache_get( 'options', 'lp-options' ) ) ) {
			global $wpdb;
			$query = $wpdb->prepare( "
				SELECT option_name, option_value
				FROM {$wpdb->options}
				WHERE option_name LIKE %s
			", 'learn_press_%' );
			if ( $options = $wpdb->get_results( $query ) ) {
				foreach ( $options as $option ) {
					$this->_options[ $option->option_name ] = maybe_unserialize( $option->option_value );
					//wp_cache_add( $option->option_name, $this->_options[ $option->option_name ], 'options' );
				}
			}
			foreach ( array( 'learn_press_permalink_structure', 'learn_press_install' ) as $option ) {
				if ( empty( $this->_options[ $option ] ) ) {
					$this->_options[ $option ] = '';
					//wp_cache_add( $option, '', 'options' );
				}
			}
			wp_cache_set( 'options', $this->_options, 'lp-options' );
		} else {
			$this->_options = $_options;
		}
	}

	/**
	 * Set new value for a key
	 *
	 * @param $name
	 * @param $value
	 */
	public function set( $name, $value ) {
		$this->_set_option( $this->_options, $name, $value );
	}

	private function _set_option( &$obj, $var, $value ) {
		$var         = (array) explode( '.', $var );
		$current_var = array_shift( $var );
		if ( is_object( $obj ) ) {
			if ( isset( $obj->{$current_var} ) ) {
				if ( count( $var ) ) {
					$this->_set_option( $obj->{$current_var}, join( '.', $var ), $value );
				} else {
					$obj->{$current_var} = $value;
				}
			} else {
				$obj->{$current_var} = $value;
			}
		} else {
			if ( isset( $obj[ $current_var ] ) ) {
				if ( count( $var ) ) {
					$this->_set_option( $obj[ $current_var ], join( '.', $var ), $value );
				} else {
					$obj[ $current_var ] = $value;
				}
			} else {
				$obj[ $current_var ] = $value;
			}
		}
	}

	/**
	 * Get option recurse separated by DOT
	 *
	 * @param      $var
	 * @param null $default
	 *
	 * @return null
	 */
	public function get( $var, $default = null ) {
		if ( $this->_prefix && strpos( $var, $this->_prefix ) === false ) {
			$var = $this->_prefix . $var;
		}
		$segs   = explode( '.', $var );
		$return = $this->_get_option( $this->_options, $var, $default );
		if ( $return == '' || is_null( $return ) ) {
			$return = $default;
		}

		return $return;
	}

	public function _get_option( $obj, $var, $default = null ) {
		$var         = (array) explode( '.', $var );
		$current_var = array_shift( $var );
		if ( is_object( $obj ) ) {
			if ( isset( $obj->{$current_var} ) ) {
				if ( count( $var ) ) {
					return $this->_get_option( $obj->{$current_var}, join( '.', $var ), $default );
				} else {
					return $obj->{$current_var};
				}
			} else {
				return $default;
			}
		} else {
			if ( isset( $obj[ $current_var ] ) ) {
				if ( count( $var ) ) {
					return $this->_get_option( $obj[ $current_var ], join( '.', $var ), $default );
				} else {
					return $obj[ $current_var ];
				}
			} else {
				return $default;
			}
		}
	}

	public static function instance() {
		if ( empty( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

return LP_Settings::instance();